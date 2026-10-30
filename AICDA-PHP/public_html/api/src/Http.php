<?php
declare(strict_types=1);

// An error that becomes a JSON response. By default the body is
// { success: false, message }, like the Express error handler; `payload`
// replaces it for responses with a different shape (e.g. validation errors).
final class HttpError extends Exception
{
    public int $status;
    public ?array $payload;

    public function __construct(int $status, string $message, ?array $payload = null)
    {
        parent::__construct($message);
        $this->status = $status;
        $this->payload = $payload;
    }
}

function json_reply(int $status, array $body, array $headers = []): array
{
    return ['status' => $status, 'body' => $body, 'headers' => $headers];
}

final class Request
{
    public string $method = 'GET';
    public string $path = '/';
    public array $query = [];
    public array $body = [];
    public array $files = [];
    public array $params = [];
    public ?array $file = null;   // set by the upload middleware (like multer's req.file)
    public ?array $admin = null;  // set by Auth::required (like req.admin)

    public static function capture(): self
    {
        $request = new self();
        $request->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $request->query = $_GET;

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $prefix = strpos($path, '/api/v1');

        if ($prefix !== false) {
            $route = substr($path, $prefix + strlen('/api/v1'));
            $request->path = $route === '' ? '/' : $route;
        } elseif (preg_match('#/api/?$#', $path)) {
            $request->path = '__root__';
        } else {
            $request->path = $path;
        }

        if ($request->path !== '/' && $request->path !== '__root__') {
            $request->path = rtrim($request->path, '/') ?: '/';
        }

        return $request;
    }

    public static function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        if ($key === 'HTTP_AUTHORIZATION') {
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            }
            if (function_exists('apache_request_headers')) {
                foreach (apache_request_headers() as $header => $value) {
                    if (strcasecmp($header, 'Authorization') === 0) {
                        return $value;
                    }
                }
            }
        }

        if ($key === 'HTTP_CONTENT_TYPE' && isset($_SERVER['CONTENT_TYPE'])) {
            return $_SERVER['CONTENT_TYPE'];
        }

        return null;
    }

    // Body parsing equivalent to express.json(), express.urlencoded() and multer.
    // PHP only parses multipart bodies for POST, so PUT/PATCH are parsed here.
    public function parseBody(): void
    {
        $contentType = strtolower((string) self::header('Content-Type'));

        if (str_contains($contentType, 'application/json')) {
            $raw = (string) file_get_contents('php://input');
            if (trim($raw) === '') {
                return;
            }
            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                throw new HttpError(400, 'Invalid JSON body');
            }
            $this->body = $decoded;
            return;
        }

        if (str_contains($contentType, 'multipart/form-data')) {
            if ($this->method === 'POST') {
                $this->body = $_POST;
                foreach ($_FILES as $field => $file) {
                    if (is_array($file['name'] ?? null)) {
                        continue;
                    }
                    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $this->files[$field] = $file + ['uploaded' => true];
                }
            } else {
                // The boundary is case-sensitive, so use the original header.
                [$this->body, $this->files] = Multipart::parse((string) self::header('Content-Type'));
            }
            return;
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            if ($this->method === 'POST') {
                $this->body = $_POST;
            } else {
                parse_str((string) file_get_contents('php://input'), $parsed);
                $this->body = $parsed;
            }
        }
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body);
    }

    public function input(string $key, $default = null)
    {
        return array_key_exists($key, $this->body) ? $this->body[$key] : $default;
    }
}

// Minimal multipart/form-data parser for PUT/PATCH requests.
final class Multipart
{
    public static function parse(string $contentType): array
    {
        if (!preg_match('/boundary=(?:"([^"]+)"|([^;\s]+))/i', $contentType, $match)) {
            return [[], []];
        }

        $boundary = $match[1] !== '' ? $match[1] : $match[2];
        $raw = (string) file_get_contents('php://input');
        $fields = [];
        $files = [];

        foreach (explode('--' . $boundary, $raw) as $part) {
            if ($part === '' || str_starts_with($part, '--')) {
                continue;
            }

            $part = preg_replace('/^\r\n/', '', $part, 1);
            $split = strpos($part, "\r\n\r\n");
            if ($split === false) {
                continue;
            }

            $headerText = substr($part, 0, $split);
            $content = substr($part, $split + 4);
            if (str_ends_with($content, "\r\n")) {
                $content = substr($content, 0, -2);
            }

            if (!preg_match('/name="([^"]*)"/i', $headerText, $nameMatch)) {
                continue;
            }
            $name = $nameMatch[1];

            if (preg_match('/filename="([^"]*)"/i', $headerText, $fileMatch)) {
                if ($fileMatch[1] === '') {
                    continue;
                }
                $type = preg_match('/Content-Type:\s*([^\r\n]+)/i', $headerText, $typeMatch)
                    ? trim($typeMatch[1])
                    : 'application/octet-stream';
                $tmp = tempnam(sys_get_temp_dir(), 'aicda');
                file_put_contents($tmp, $content);
                $files[$name] = [
                    'name' => $fileMatch[1],
                    'type' => $type,
                    'tmp_name' => $tmp,
                    'size' => strlen($content),
                    'error' => UPLOAD_ERR_OK,
                    'uploaded' => false,
                ];
            } else {
                $fields[$name] = $content;
            }
        }

        return [$fields, $files];
    }
}

final class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, callable ...$handlers): void
    {
        $regex = preg_replace('#:([A-Za-z_]+)#', '(?P<$1>[^/]+)', $pattern);
        $this->routes[] = [$method, '#^' . $regex . '$#i', $handlers];
    }

    public function dispatch(Request $request): ?array
    {
        foreach ($this->routes as [$method, $regex, $handlers]) {
            if ($method !== $request->method || !preg_match($regex, $request->path, $match)) {
                continue;
            }

            foreach ($match as $key => $value) {
                if (is_string($key)) {
                    $request->params[$key] = rawurldecode($value);
                }
            }

            // Middleware returns null to continue, or a reply to stop.
            foreach ($handlers as $handler) {
                $reply = $handler($request);
                if ($reply !== null) {
                    return $reply;
                }
            }

            return null;
        }

        return null;
    }
}
