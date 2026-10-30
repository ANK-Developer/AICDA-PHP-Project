<?php
declare(strict_types=1);

// Equivalent of uploadGalleryMedia (multer disk storage): one file per request,
// images and videos only, 100 MB, saved as "<timestamp>-<random><ext>" in
// public_html/uploads and served at /uploads/<name>.
final class Upload
{
    private const MAX_BYTES = 100 * 1024 * 1024;

    private const EXTENSIONS = [
        'jpg', 'jpeg', 'jfif', 'png', 'gif', 'webp', 'bmp', 'svg', 'avif', 'heic', 'heif', 'tif', 'tiff', 'ico',
        'mp4', 'webm', 'mov', 'm4v', 'avi', 'mkv', 'ogv', '3gp',
    ];

    public static function single(string $field): callable
    {
        return function (Request $request) use ($field): ?array {
            $file = $request->files[$field] ?? null;
            if (!$file) {
                return null;
            }

            if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $tooLarge = in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
                throw new HttpError(500, $tooLarge ? 'File too large' : 'File upload failed');
            }
            if ($file['size'] > self::MAX_BYTES) {
                throw new HttpError(500, 'File too large');
            }

            // Checks both the type the browser declared (like multer) and the real content.
            $declared = strtolower((string) $file['type']);
            $detected = strtolower((string) (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']));
            $isMedia = fn (string $type) => str_starts_with($type, 'image/') || str_starts_with($type, 'video/');

            if (!$isMedia($declared) || !$isMedia($detected)) {
                @unlink($file['tmp_name']);
                throw new HttpError(500, 'Only images and videos are allowed');
            }

            $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, self::EXTENSIONS, true)) {
                $extension = explode('/', $detected)[1] ?? 'bin';
                $extension = in_array($extension, self::EXTENSIONS, true) ? $extension : 'bin';
            }

            $directory = Config::uploadDir();
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $filename = (int) floor(microtime(true) * 1000) . '-' . random_int(0, 999999999) . '.' . $extension;
            $target = $directory . '/' . $filename;

            $moved = !empty($file['uploaded'])
                ? move_uploaded_file($file['tmp_name'], $target)
                : rename($file['tmp_name'], $target);

            if (!$moved) {
                throw new HttpError(500, 'Unable to save the uploaded file');
            }
            @chmod($target, 0644);

            $request->file = [
                'filename' => $filename,
                'path' => $target,
                'mimetype' => $declared,
                'originalname' => (string) $file['name'],
            ];

            return null;
        };
    }

    public static function publicPath(?array $file): ?string
    {
        return $file ? '/uploads/' . $file['filename'] : null;
    }

    public static function discard(Request $request): void
    {
        if ($request->file && is_file($request->file['path'])) {
            @unlink($request->file['path']);
        }
    }

    // Deletes a locally stored upload ("/uploads/<name>"); links are ignored.
    public static function removeLocal(?string $url): void
    {
        if (!$url || !str_starts_with($url, '/uploads/')) {
            return;
        }
        $path = Config::uploadDir() . '/' . basename($url);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
