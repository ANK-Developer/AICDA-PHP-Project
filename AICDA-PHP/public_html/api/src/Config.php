<?php
declare(strict_types=1);

// Settings come from a PHP file that returns an array (see config.example.php).
// Looked up first one level ABOVE public_html (never web-reachable), then in api/.
final class Config
{
    private static ?array $values = null;

    public static function all(): array
    {
        if (self::$values !== null) {
            return self::$values;
        }

        $candidates = [
            dirname(__DIR__, 3) . '/aicda-config.php',
            dirname(__DIR__) . '/config.php',
        ];

        foreach ($candidates as $file) {
            if (is_file($file)) {
                $values = require $file;
                if (is_array($values)) {
                    return self::$values = $values;
                }
            }
        }

        throw new RuntimeException('API configuration file not found (aicda-config.php or api/config.php)');
    }

    public static function get(string $key, $default = null)
    {
        return self::all()[$key] ?? $default;
    }

    public static function isProduction(): bool
    {
        return self::get('env', 'production') === 'production';
    }

    public static function uploadDir(): string
    {
        return rtrim((string) (self::get('upload_dir') ?: dirname(__DIR__, 2) . '/uploads'), '/\\');
    }
}
