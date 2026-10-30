<?php
declare(strict_types=1);

// Port of AICDA-Backend/src/utils/membership.js.
//  - membership: calculated from the plan dates (VALID / EXPIRED / PENDING), in IST.
//  - status: the admin's manual decision (ACTIVE / INACTIVE) with reason.
// `isActive` in responses is the combined, effective value.
final class Membership
{
    public static function build(array $record): array
    {
        $validTo = $record['validityTo'] ?? null;
        $nextValidFrom = Validity::istDay($validTo ?: ($record['createdAt'] ?? null));

        if (!$validTo) {
            return [
                'status' => 'PENDING',
                'isValid' => false,
                'validFrom' => $record['validityFrom'] ?? null,
                'validTo' => null,
                'daysRemaining' => null,
                'nextValidFrom' => $nextValidFrom,
            ];
        }

        $daysRemaining = Validity::daysRemaining($validTo);
        $isValid = $daysRemaining !== null && $daysRemaining >= 0;

        return [
            'status' => $isValid ? 'VALID' : 'EXPIRED',
            'isValid' => $isValid,
            'validFrom' => $record['validityFrom'] ?? null,
            'validTo' => $validTo,
            'daysRemaining' => $daysRemaining,
            'nextValidFrom' => $nextValidFrom,
        ];
    }

    public static function withStatus(?array $record): ?array
    {
        if (!$record) {
            return $record;
        }

        $reason = $record['statusReason'] ?? null;
        $actionBy = $record['statusActionBy'] ?? null;
        $actionAt = $record['statusActionAt'] ?? null;
        unset($record['statusReason'], $record['statusActionBy'], $record['statusActionAt']);

        $membership = self::build($record);
        $manuallyActive = ($record['isActive'] ?? null) !== false;

        $record['isActive'] = $manuallyActive && $membership['isValid'];
        $record['membership'] = $membership;
        $record['status'] = [
            'status' => $manuallyActive ? 'ACTIVE' : 'INACTIVE',
            'reason' => $reason,
            'actionBy' => $actionBy,
            'actionAt' => $actionAt,
        ];

        return $record;
    }

    public static function parseExpiringDays($value): int
    {
        // Number(undefined) is NaN → default; Number("") is 0.
        if ($value === null) {
            return 7;
        }
        $text = is_string($value) ? trim($value) : $value;
        if ($text === '') {
            return 0;
        }
        if (!is_numeric($text)) {
            return 7;
        }
        $days = (int) floor((float) $text);
        return $days >= 0 ? min($days, 365) : 7;
    }

    // SQL fragment for a `status` filter value, or null for "all" / unknown.
    // The four basic states never overlap: blocked, expired, pending, active.
    public static function filter(?string $status, int $expiringDays, string $alias): ?array
    {
        $today = Validity::startOfTodayIST();
        $a = $alias;

        switch ($status) {
            case 'active':
                return ["($a.isActive = 1 AND $a.validityTo >= ?)", [$today]];
            case 'blocked':
                return ["($a.isActive = 0)", []];
            case 'expired':
                return ["($a.isActive = 1 AND $a.validityTo < ?)", [$today]];
            case 'pending':
                return ["($a.isActive = 1 AND $a.validityTo IS NULL)", []];
            case 'inactive':
                return ["($a.isActive = 0 OR $a.validityTo IS NULL OR $a.validityTo < ?)", [$today]];
            case 'expiring':
                $end = $today->modify('+' . ($expiringDays + 1) . ' days')->modify('-1 millisecond');
                return ["($a.isActive = 1 AND $a.validityTo >= ? AND $a.validityTo <= ?)", [$today, $end]];
        }

        return null;
    }

    // Directory-wide counts for the summary cards, independent of filters.
    public static function counts(string $table, int $expiringDays): array
    {
        $count = function (string $status) use ($table, $expiringDays): int {
            [$sql, $params] = self::filter($status, $expiringDays, 't');
            return (int) DB::scalar("SELECT COUNT(*) FROM `$table` t WHERE $sql", $params);
        };

        $active = $count('active');
        $blocked = $count('blocked');
        $expired = $count('expired');
        $pending = $count('pending');

        return [
            'total' => $active + $blocked + $expired + $pending,
            'active' => $active,
            'inactive' => $blocked + $expired + $pending,
            'blocked' => $blocked,
            'expired' => $expired,
            'pending' => $pending,
            'expiring' => $count('expiring'),
        ];
    }

    // Normalises the body of a manual status change. Deactivating needs a reason.
    public static function parseStatusChange(array $body, ?array $admin): array
    {
        $isActive = $body['isActive'] ?? null;

        if (!is_bool($isActive)) {
            throw new HttpError(400, 'isActive must be true or false');
        }

        $reason = isset($body['reason']) && is_string($body['reason']) ? trim($body['reason']) : '';

        if (!$isActive && mb_strlen($reason) < 3) {
            throw new HttpError(400, 'A reason (at least 3 characters) is required to deactivate');
        }
        if (mb_strlen($reason) > 500) {
            throw new HttpError(400, 'Reason must be 500 characters or fewer');
        }

        $name = trim(implode(' ', array_filter([$admin['firstName'] ?? null, $admin['lastName'] ?? null])));

        return [
            'isActive' => $isActive,
            'statusReason' => $reason !== '' ? $reason : null,
            'statusActionBy' => $name !== '' ? $name : ($admin['email'] ?? null),
            'statusActionAt' => Dates::now(),
        ];
    }
}
