<?php
declare(strict_types=1);

final class AuthController
{
    // POST /api/v1/auth/login
    public static function login(Request $request): array
    {
        try {
            $email = $request->input('email');
            $password = $request->input('password');

            if (!$email || !$password) {
                return json_reply(400, ['success' => false, 'message' => 'Email and password are required']);
            }

            $admin = DB::one('SELECT * FROM `Admin` WHERE email = ?', [strtolower(trim((string) $email))]);

            if (!$admin || !Crypto::verifyPassword((string) $password, $admin['password'])) {
                return json_reply(401, ['success' => false, 'message' => 'Invalid email or password']);
            }
            if (!$admin['isActive']) {
                return json_reply(403, ['success' => false, 'message' => 'Your account has been deactivated']);
            }
            // Only SUPER_ADMIN can access this panel
            if ($admin['role'] !== 'SUPER_ADMIN') {
                return json_reply(403, ['success' => false, 'message' => 'Only Super Admin can access this panel']);
            }

            $token = Crypto::signToken($admin['id']);
            Auth::setCookie($token);

            return json_reply(200, [
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'admin' => [
                    'id' => $admin['id'],
                    'firstName' => $admin['firstName'],
                    'lastName' => $admin['lastName'],
                    'email' => $admin['email'],
                    'phone' => $admin['phone'],
                    'role' => $admin['role'],
                    'isActive' => $admin['isActive'],
                ],
            ]);
        } catch (Throwable $error) {
            error_log('Login error: ' . $error->getMessage());
            return json_reply(500, ['success' => false, 'message' => 'Internal server error']);
        }
    }

    // POST /api/v1/auth/logout
    public static function logout(Request $request): array
    {
        Auth::clearCookie();
        return json_reply(200, ['success' => true, 'message' => 'Logged out successfully']);
    }

    // GET /api/v1/auth/me
    public static function me(Request $request): array
    {
        return json_reply(200, ['success' => true, 'admin' => $request->admin]);
    }

    // POST /api/v1/auth/change-password
    public static function changePassword(Request $request): array
    {
        try {
            $newPassword = $request->input('newPassword');
            $confirmPassword = $request->input('confirmPassword');

            if (!$newPassword || !$confirmPassword) {
                return json_reply(400, ['success' => false, 'message' => 'New password and confirm password are required']);
            }
            if (mb_strlen((string) $newPassword) < 6) {
                return json_reply(400, ['success' => false, 'message' => 'New password must be at least 6 characters long']);
            }
            if ($newPassword !== $confirmPassword) {
                return json_reply(400, ['success' => false, 'message' => 'New password and confirm password do not match']);
            }

            DB::update(
                'Admin',
                Crypto::passwordFields((string) $newPassword) + ['updatedAt' => Dates::now()],
                'id = ?',
                [$request->admin['id']]
            );

            return json_reply(200, ['success' => true, 'message' => 'Password updated successfully']);
        } catch (Throwable $error) {
            error_log('Change password error: ' . $error->getMessage());
            return json_reply(500, ['success' => false, 'message' => 'Internal server error']);
        }
    }
}
