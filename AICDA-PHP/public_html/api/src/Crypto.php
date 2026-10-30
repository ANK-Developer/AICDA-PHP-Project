<?php
declare(strict_types=1);

final class Crypto
{
    private const TOKEN_TTL = 7 * 24 * 60 * 60; // 7 days, as generateToken.js

    // ---------- JWT (HS256, compatible with jsonwebtoken) ----------

    public static function signToken(string $adminId): string
    {
        $now = time();
        $header = self::b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = self::b64(json_encode(['id' => $adminId, 'iat' => $now, 'exp' => $now + self::TOKEN_TTL]));
        $signature = self::b64(hash_hmac('sha256', "$header.$payload", self::secret(), true));
        return "$header.$payload.$signature";
    }

    public static function verifyToken(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('jwt malformed');
        }

        [$header, $payload, $signature] = $parts;
        $decodedHeader = json_decode(self::unb64($header), true);
        if (($decodedHeader['alg'] ?? null) !== 'HS256') {
            throw new RuntimeException('invalid algorithm');
        }

        $expected = self::b64(hash_hmac('sha256', "$header.$payload", self::secret(), true));
        if (!hash_equals($expected, $signature)) {
            throw new RuntimeException('invalid signature');
        }

        $claims = json_decode(self::unb64($payload), true);
        if (!is_array($claims) || (isset($claims['exp']) && time() >= (int) $claims['exp'])) {
            throw new RuntimeException('jwt expired');
        }

        return $claims;
    }

    private static function secret(): string
    {
        $secret = (string) Config::get('jwt_secret', '');
        if ($secret === '') {
            throw new RuntimeException('jwt_secret is not configured');
        }
        return $secret;
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function unb64(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }

    // ---------- Passwords (see passwordCrypto.js) ----------
    // `password` is a bcrypt hash used for login; `passwordEnc` is AES-256-GCM
    // ("iv:authTag:ciphertext", base64) so a Super Admin can reveal it.

    public static function hashPassword(string $plain): string
    {
        // $2b$ prefix so the Node backend can verify hashes written by PHP too.
        $hash = password_hash($plain, PASSWORD_BCRYPT, ['cost' => 10]);
        return '$2b$' . substr($hash, 4);
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function passwordFields(string $plain): array
    {
        return ['password' => self::hashPassword($plain), 'passwordEnc' => self::encrypt($plain)];
    }

    private static function key(): string
    {
        $hex = (string) Config::get('password_encryption_key', '');
        if (!preg_match('/^[0-9a-fA-F]{64}$/', $hex)) {
            throw new RuntimeException('PASSWORD_ENCRYPTION_KEY must be set to 64 hex characters (32 bytes) in .env');
        }
        return hex2bin($hex);
    }

    public static function encrypt(string $plain): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $encrypted = openssl_encrypt($plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        return implode(':', array_map('base64_encode', [$iv, $tag, $encrypted]));
    }

    public static function decrypt(string $payload): string
    {
        [$iv, $tag, $encrypted] = array_map('base64_decode', explode(':', $payload) + [null, null, null]);
        $plain = openssl_decrypt((string) $encrypted, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, (string) $iv, (string) $tag);
        if ($plain === false) {
            throw new RuntimeException('Unsupported state or unable to authenticate data');
        }
        return $plain;
    }

    // ---------- IDs ----------

    private static int $counter = 0;

    // Same shape as Prisma's cuid(): "c" + time + counter + fingerprint + random (25 chars).
    public static function cuid(): string
    {
        $pad = fn (string $s, int $n) => substr(str_pad($s, $n, '0', STR_PAD_LEFT), -$n);
        $time = base_convert((string) (int) floor(microtime(true) * 1000), 10, 36);
        self::$counter = (self::$counter + 1) % 1679616;
        $counter = $pad(base_convert((string) (mt_rand(0, 1000) + self::$counter), 10, 36), 4);
        $fingerprint = $pad(base_convert((string) (getmypid() ?: random_int(0, 9999)), 10, 36), 2)
            . $pad(base_convert((string) crc32(php_uname('n')), 10, 36), 2);
        $random = $pad(base_convert((string) random_int(0, 1679615), 10, 36), 4)
            . $pad(base_convert((string) random_int(0, 1679615), 10, 36), 4);
        return 'c' . $pad($time, 8) . $counter . $fingerprint . $random;
    }

    public static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
