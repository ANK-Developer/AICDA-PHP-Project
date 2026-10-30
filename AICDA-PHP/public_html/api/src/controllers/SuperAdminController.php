<?php
declare(strict_types=1);

final class SuperAdminController
{
    private const COLUMNS = 'id, firstName, lastName, email, phone, role, isActive, createdAt';

    private static function find(string $id): ?array
    {
        return DB::one('SELECT ' . self::COLUMNS . ' FROM `Admin` WHERE id = ?', [$id]);
    }

    private static function findOrFail(string $id): array
    {
        $admin = self::find($id);
        if (!$admin) {
            throw new HttpError(404, 'Super admin not found');
        }
        return $admin;
    }

    private static function duplicate(): array
    {
        return json_reply(409, ['success' => false, 'message' => 'Email or phone already in use']);
    }

    // GET /api/v1/super-admin
    public static function index(Request $request): array
    {
        return json_reply(200, [
            'success' => true,
            'message' => 'Super admins fetched successfully',
            'data' => DB::all('SELECT ' . self::COLUMNS . " FROM `Admin` WHERE role = 'SUPER_ADMIN' ORDER BY createdAt DESC"),
        ]);
    }

    // GET /api/v1/super-admin/:id
    public static function show(Request $request): array
    {
        $admin = self::find($request->params['id']);
        if (!$admin) {
            return json_reply(404, ['success' => false, 'message' => 'Super admin not found']);
        }
        return json_reply(200, ['success' => true, 'data' => $admin]);
    }

    // POST /api/v1/super-admin/create
    public static function create(Request $request): array
    {
        $body = $request->body;
        $id = Crypto::cuid();
        $now = Dates::now();

        try {
            DB::insert('Admin', [
                'id' => $id,
                'firstName' => $body['firstName'],
                'lastName' => $body['lastName'] ?? '',
                'email' => strtolower(trim($body['email'])),
                'phone' => ($body['phone'] ?? '') !== '' ? $body['phone'] : null,
                'role' => 'SUPER_ADMIN',
                'isActive' => true,
                'createdById' => $request->admin['id'],
                'createdAt' => $now,
                'updatedAt' => $now,
            ] + Crypto::passwordFields($body['password']));
        } catch (Throwable $error) {
            if (DB::isDuplicate($error)) {
                return self::duplicate();
            }
            throw $error;
        }

        return json_reply(201, ['success' => true, 'message' => 'Super admin created successfully', 'data' => self::find($id)]);
    }

    // PATCH /api/v1/super-admin/:id
    public static function update(Request $request): array
    {
        $id = $request->params['id'];
        self::findOrFail($id);
        $body = $request->body;

        $data = [];
        foreach (['firstName', 'lastName', 'phone'] as $field) {
            if (array_key_exists($field, $body)) {
                $data[$field] = $body[$field];
            }
        }
        if (!empty($body['email'])) {
            $data['email'] = strtolower(trim($body['email']));
        }

        try {
            DB::update('Admin', $data + ['updatedAt' => Dates::now()], 'id = ?', [$id]);
        } catch (Throwable $error) {
            if (DB::isDuplicate($error)) {
                return self::duplicate();
            }
            throw $error;
        }

        return json_reply(200, ['success' => true, 'message' => 'Super admin updated successfully', 'data' => self::find($id)]);
    }

    // PATCH /api/v1/super-admin/:id/status — toggles; not allowed on yourself.
    public static function toggleStatus(Request $request): array
    {
        $id = $request->params['id'];
        if ($id === $request->admin['id']) {
            return json_reply(400, ['success' => false, 'message' => 'You cannot change your own status']);
        }

        $admin = self::findOrFail($id);
        DB::update('Admin', ['isActive' => !$admin['isActive'], 'updatedAt' => Dates::now()], 'id = ?', [$id]);
        $updated = self::find($id);

        return json_reply(200, [
            'success' => true,
            'message' => $updated['isActive'] ? 'Super admin activated successfully' : 'Super admin deactivated successfully',
            'data' => $updated,
        ]);
    }

    // DELETE /api/v1/super-admin/:id
    public static function destroy(Request $request): array
    {
        $id = $request->params['id'];
        if ($id === $request->admin['id']) {
            return json_reply(400, ['success' => false, 'message' => 'You cannot delete your own account']);
        }

        self::findOrFail($id);
        DB::run('DELETE FROM `Admin` WHERE id = ?', [$id]);

        return json_reply(200, ['success' => true, 'message' => 'Super admin deleted successfully']);
    }

    // POST /api/v1/super-admin/:id/reset-password
    public static function resetPassword(Request $request): array
    {
        $id = $request->params['id'];
        self::findOrFail($id);
        DB::update('Admin', Crypto::passwordFields((string) $request->input('newPassword')) + ['updatedAt' => Dates::now()], 'id = ?', [$id]);

        return json_reply(200, ['success' => true, 'message' => 'Password reset successfully']);
    }

    // POST /api/v1/super-admin/:id/reveal-password
    public static function revealPassword(Request $request): array
    {
        $id = $request->params['id'];
        $admin = DB::one('SELECT passwordEnc FROM `Admin` WHERE id = ?', [$id]);

        if (!$admin) {
            throw new HttpError(404, 'Super admin not found');
        }
        if (!$admin['passwordEnc']) {
            throw new HttpError(404, 'Password is not viewable for this admin. Reset it to enable viewing.');
        }

        $password = Crypto::decrypt($admin['passwordEnc']);
        error_log("[AUDIT] {$request->admin['id']} revealed the password of admin $id");

        return json_reply(200, ['success' => true, 'data' => ['password' => $password]], ['Cache-Control' => 'no-store']);
    }
}
