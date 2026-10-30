<?php
declare(strict_types=1);

final class App
{
    // Origins allowed to call the API with cookies (same as app.js). On
    // public_html the site and API share one origin, so CORS rarely applies.
    private static function originAllowed(?string $origin): bool
    {
        if (!$origin) {
            return true;
        }
        // The site and API share one domain on public_html, and browsers send
        // Origin even on same-site POSTs — always allow our own host.
        $originHost = parse_url($origin, PHP_URL_HOST) . (parse_url($origin, PHP_URL_PORT) ? ':' . parse_url($origin, PHP_URL_PORT) : '');
        if (strcasecmp($originHost, (string) ($_SERVER['HTTP_HOST'] ?? '')) === 0) {
            return true;
        }
        $frontend = trim((string) Config::get('frontend_url', ''));
        return $origin === 'http://localhost:5173'
            || ($frontend !== '' && $origin === rtrim($frontend, '/'))
            || (bool) preg_match('#^http://192\.168\.\d{1,3}\.\d{1,3}:8080$#', $origin);
    }

    public static function run(): void
    {
        $request = null;

        try {
            $request = Request::capture();
            $origin = Request::header('Origin');

            if (!self::originAllowed($origin)) {
                throw new HttpError(500, 'Not allowed by CORS');
            }
            if ($origin) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Credentials: true');
                header('Vary: Origin');
            }
            if ($request->method === 'OPTIONS') {
                header('Access-Control-Allow-Methods: GET,POST,PUT,PATCH,DELETE,OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type,Authorization');
                http_response_code(204);
                return;
            }

            if ($request->path === '__root__') {
                self::send(json_reply(200, ['success' => true, 'message' => 'AICDA Backend API is running']));
                return;
            }

            $request->parseBody();
            $reply = build_router()->dispatch($request);

            if ($reply === null) {
                self::notFound($request);
                return;
            }

            self::send($reply);
        } catch (Throwable $error) {
            self::send(self::errorReply($error, $request));
        } finally {
            // Temp files of PUT/PATCH uploads that were not used.
            foreach ($request->files ?? [] as $file) {
                if (empty($file['uploaded']) && is_file($file['tmp_name'] ?? '')) {
                    @unlink($file['tmp_name']);
                }
            }
        }
    }

    // Same as the fallback error handler in app.js.
    private static function errorReply(Throwable $error, ?Request $request): array
    {
        if ($error instanceof HttpError) {
            return json_reply($error->status, $error->payload ?? ['success' => false, 'message' => $error->getMessage()]);
        }

        error_log((string) $error);

        // Unique-constraint violation → name the field instead of a raw DB error.
        if (DB::isDuplicate($error)) {
            $key = preg_match("/for key '([^']+)'/", $error->getMessage(), $m) ? $m[1] : '';
            $key = substr($key, (int) strrpos($key, '.') + (str_contains($key, '.') ? 1 : 0));
            $field = preg_match('/_([A-Za-z0-9]+)_key$/', $key, $mm) ? $mm[1] : ($key ?: 'value');
            $labels = ['memberId' => 'Member ID', 'partnerId' => 'Partner ID', 'email' => 'Email', 'phone' => 'Phone'];
            $label = $labels[$field] ?? $field;
            $value = $request ? ($request->body[$field] ?? null) : null;

            return json_reply(409, [
                'success' => false,
                'message' => $value
                    ? "$label \"$value\" is already in use — please use a different $label."
                    : "This $label is already in use — please use a different $label.",
            ]);
        }

        if ($error instanceof PDOException && (int) ($error->errorInfo[1] ?? 0) === 1451) {
            $constraint = preg_match('/CONSTRAINT `([^`]+)`/', $error->getMessage(), $m) ? $m[1] : 'foreign key';
            return json_reply(500, ['success' => false, 'message' => "Foreign key constraint violated on the constraint: `$constraint`"]);
        }

        // PHP engine errors (TypeError etc.) and DB errors never expose details.
        if ($error instanceof PDOException || $error instanceof Error) {
            return json_reply(500, ['success' => false, 'message' => 'Internal Server Error']);
        }

        return json_reply(500, ['success' => false, 'message' => $error->getMessage() ?: 'Internal Server Error']);
    }

    private static function notFound(Request $request): void
    {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Error</title></head><body><pre>Cannot '
            . htmlspecialchars($request->method . ' /api/v1' . $request->path)
            . '</pre></body></html>';
    }

    private static function send(array $reply): void
    {
        http_response_code($reply['status']);
        header('Content-Type: application/json; charset=utf-8');
        foreach ($reply['headers'] as $name => $value) {
            header("$name: $value");
        }
        echo json_encode($reply['body'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
