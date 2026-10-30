<?php
declare(strict_types=1);

// isAuthenticated + authorizeRoles from AICDA-Backend/src/middlewares.
final class Auth
{
    public static function required(Request $request): ?array
    {
        try {
            $authorization = (string) Request::header('Authorization');
            $bearer = str_starts_with($authorization, 'Bearer ') ? substr($authorization, 7) : null;
            $token = ($_COOKIE['token'] ?? '') ?: $bearer;

            if (!$token) {
                return json_reply(401, ['success' => false, 'message' => 'Authentication required']);
            }

            $claims = Crypto::verifyToken($token);

            $admin = DB::one(
                'SELECT id, firstName, lastName, email, phone, role, isActive FROM `Admin` WHERE id = ?',
                [(string) ($claims['id'] ?? '')]
            );

            if (!$admin) {
                return json_reply(401, ['success' => false, 'message' => 'Admin account no longer exists']);
            }
            if (!$admin['isActive']) {
                return json_reply(403, ['success' => false, 'message' => 'Your account has been deactivated']);
            }

            $request->admin = $admin;
            return null;
        } catch (Throwable $error) {
            error_log('Authentication error: ' . $error->getMessage());
            return json_reply(401, ['success' => false, 'message' => 'Invalid or expired token']);
        }
    }

    public static function role(string ...$roles): callable
    {
        return function (Request $request) use ($roles): ?array {
            if (!$request->admin) {
                return json_reply(401, ['success' => false, 'message' => 'Authentication required']);
            }
            if (!in_array($request->admin['role'], $roles, true)) {
                return json_reply(403, ['success' => false, 'message' => 'You are not authorized to perform this action']);
            }
            return null;
        };
    }

    public static function setCookie(string $token): void
    {
        $production = Config::isProduction();
        setcookie('token', $token, [
            'expires' => time() + 7 * 24 * 60 * 60,
            'path' => '/',
            'httponly' => true,
            'secure' => $production,
            'samesite' => $production ? 'None' : 'Lax',
        ]);
    }

    public static function clearCookie(): void
    {
        $production = Config::isProduction();
        setcookie('token', '', [
            'expires' => 1,
            'path' => '/',
            'httponly' => true,
            'secure' => $production,
            'samesite' => $production ? 'None' : 'Lax',
        ]);
    }
}
