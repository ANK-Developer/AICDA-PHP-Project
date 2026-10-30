<?php
declare(strict_types=1);

final class Dates
{
    private static ?DateTimeZone $utc = null;
    private static ?DateTimeZone $ist = null;

    public static function utc(): DateTimeZone
    {
        return self::$utc ??= new DateTimeZone('UTC');
    }

    public static function ist(): DateTimeZone
    {
        return self::$ist ??= new DateTimeZone('Asia/Kolkata');
    }

    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', self::utc());
    }

    // JavaScript `new Date(value)` for the values this API receives: a Date,
    // "YYYY-MM-DD" (UTC midnight) or an ISO timestamp. Null when not a date.
    public static function parse($value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }
        if (!is_string($value)) {
            return null;
        }

        $text = trim($value);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $text, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1])
                ? new DateTimeImmutable($text . 'T00:00:00', self::utc())
                : null;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}/', $text)) {
            return null;
        }

        try {
            return (new DateTimeImmutable($text, self::utc()))->setTimezone(self::utc());
        } catch (Exception $e) {
            return null;
        }
    }

    public static function iso(?DateTimeInterface $date): ?string
    {
        return $date ? DateTimeImmutable::createFromInterface($date)->setTimezone(self::utc())->format('Y-m-d\TH:i:s.v\Z') : null;
    }

    public static function toDb(DateTimeInterface $date): string
    {
        return DateTimeImmutable::createFromInterface($date)->setTimezone(self::utc())->format('Y-m-d H:i:s.v');
    }

    public static function fromDb(string $value): string
    {
        return self::iso(new DateTimeImmutable($value, self::utc()));
    }

    public static function isIso8601($value): bool
    {
        return is_string($value)
            && (bool) preg_match('/^\d{4}-\d{2}-\d{2}(?:T\d{2}:\d{2}(?::\d{2}(?:\.\d{1,9})?)?(?:Z|[+-]\d{2}(?::?\d{2})?)?)?$/i', $value);
    }
}

// Port of AICDA-Backend/src/utils/validity.js — all membership dates are
// calendar days in IST; validity runs through the whole last day.
final class Validity
{
    // toISTDay: "YYYY-MM-DD" in IST, or null when the value is not a real date.
    public static function istDayOf($value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value)->setTimezone(Dates::ist())->format('Y-m-d');
        }

        $text = trim((string) $value);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $text, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $text : null;
        }

        $parsed = Dates::parse($text);
        return $parsed ? $parsed->setTimezone(Dates::ist())->format('Y-m-d') : null;
    }

    public static function istDay($value): ?string
    {
        return $value ? self::istDayOf($value) : null;
    }

    public static function endOfDayIST($value): ?DateTimeImmutable
    {
        if (!$value) {
            return null;
        }
        $day = self::istDayOf($value);
        if (!$day) {
            throw new HttpError(400, 'Invalid validity date');
        }
        return new DateTimeImmutable($day . 'T23:59:59.999+05:30');
    }

    public static function startOfDayIST($value): DateTimeImmutable
    {
        $day = $value === null ? null : self::istDayOf($value);
        if (!$day) {
            throw new HttpError(400, 'Invalid validity date');
        }
        return new DateTimeImmutable($day . 'T00:00:00.000+05:30');
    }

    public static function startOfTodayIST(): DateTimeImmutable
    {
        return self::startOfDayIST(Dates::now());
    }

    public static function daysRemaining($validityTo): ?int
    {
        $target = $validityTo ? self::istDayOf($validityTo) : null;
        if (!$target) {
            return null;
        }
        $today = self::istDayOf(Dates::now());
        return (int) round((strtotime($target . 'T00:00:00Z') - strtotime($today . 'T00:00:00Z')) / 86400);
    }

    // en-GB "05 Oct 2026" (Node's ICU writes September as "Sept").
    private static function formatDay(string $day): string
    {
        $formatted = (new DateTimeImmutable($day . 'T00:00:00Z'))->format('d M Y');
        return str_replace(' Sep ', ' Sept ', $formatted);
    }

    // See resolveRenewalPeriod in validity.js for the rules.
    public static function resolveRenewalPeriod($previousValidityTo, $createdAt, $validityFrom, $validityTo): array
    {
        $fail = function (string $message): void {
            throw new HttpError(400, $message);
        };

        if (!$validityTo) {
            $fail('Validity To date is required');
        }

        $to = self::endOfDayIST($validityTo);
        $from = $validityFrom
            ? self::startOfDayIST($validityFrom)
            : self::startOfDayIST($previousValidityTo ?: $createdAt);

        $today = self::istDayOf(Dates::now());
        $fromDay = self::istDayOf($from);
        $toDay = self::istDayOf($to);

        if ($toDay < $fromDay) {
            $fail('Validity To cannot be before Valid From.');
        }

        if ($previousValidityTo) {
            $previousDay = self::istDayOf($previousValidityTo);

            if ($fromDay < $previousDay) {
                $fail('Valid From cannot be before the last valid-to date (' . self::formatDay($previousDay) . ').');
            }

            if ($previousDay >= $today) {
                if ($fromDay !== $previousDay) {
                    $date = self::formatDay($previousDay);
                    $fail("The current plan runs until $date. Valid From must be $date, so the new plan continues from it.");
                }
            } elseif ($fromDay > $today) {
                $fail('Valid From cannot be in the future.');
            }
        } elseif ($fromDay > $today) {
            $fail('Valid From cannot be in the future.');
        }

        return ['validityFrom' => $from, 'validityTo' => $to, 'paymentDate' => Dates::now()];
    }
}
