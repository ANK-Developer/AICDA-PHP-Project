<?php
declare(strict_types=1);

// Important dates are not stored: they come from each member's and partner's
// dateOfBirth and specialDates. Port of importantDate.service.js.
final class ImportantDateController
{
    private const DAY = 86400;
    private const OCCASIONS = ['birthday', 'special'];
    private const TYPES = ['member', 'partner'];
    private const PERIOD_DAYS = ['today' => 0, 'week' => 7, 'month' => 30];

    // Today's calendar date in IST, as a UTC-midnight timestamp (seconds).
    private static function todayInIST(): int
    {
        $ist = Dates::now()->setTimezone(Dates::ist());
        return gmmktime(0, 0, 0, (int) $ist->format('n'), (int) $ist->format('j'), (int) $ist->format('Y'));
    }

    // Next time the month/day of `date` occurs on or after `today`.
    private static function nextOccurrence($date, int $today): int
    {
        $source = Dates::parse($date);
        $month = (int) $source->format('n');
        $day = (int) $source->format('j');
        $year = (int) gmdate('Y', $today);

        $next = gmmktime(0, 0, 0, $month, $day, $year);
        if ($next < $today) {
            $next = gmmktime(0, 0, 0, $month, $day, $year + 1);
        }
        return $next;
    }

    private static function entries(array $record, string $type, string $name, string $code, int $today): array
    {
        $raw = [['entryKey' => 'birthday', 'occasion' => 'birthday', 'date' => $record['dateOfBirth'], 'note' => null]];
        foreach (SpecialDates::asList($record['specialDates'] ?? null) as $special) {
            $raw[] = [
                'entryKey' => 'special-' . ($special['id'] ?? ''),
                'occasion' => 'special',
                'date' => $special['date'] ?? null,
                'note' => $special['note'] ?? null,
            ];
        }

        $isActive = Membership::withStatus($record)['isActive'];
        $result = [];

        foreach ($raw as $entry) {
            if (!$entry['date'] || !Dates::parse($entry['date'])) {
                continue;
            }
            $next = self::nextOccurrence($entry['date'], $today);
            $result[] = [
                'key' => "$type-{$record['id']}-{$entry['entryKey']}",
                'id' => $record['id'],
                'type' => $type,
                'name' => $name,
                'code' => $code,
                'photo' => $record['photo'],
                'isActive' => $isActive,
                'occasion' => $entry['occasion'],
                'date' => $entry['date'],
                'note' => $entry['note'],
                'nextDate' => gmdate('Y-m-d\TH:i:s.000\Z', $next),
                'daysLeft' => (int) round(($next - $today) / self::DAY),
            ];
        }

        return $result;
    }

    // GET /api/v1/important-dates
    // Query: occasion, type, search, status, period (today|week|month|all), page, limit
    public static function index(Request $request): array
    {
        $query = $request->query;
        $term = isset($query['search']) && is_string($query['search']) ? trim($query['search']) : '';
        $isNumeric = $term !== '' && preg_match('/^\d+$/', $term);
        $today = self::todayInIST();
        $type = $query['type'] ?? null;
        $period = $query['period'] ?? 'today';

        $statusFilter = in_array($query['status'] ?? null, ['active', 'inactive'], true)
            ? Membership::filter($query['status'], 7, 't')
            : null;

        $fetch = function (string $table, array $searchColumns, string $nameColumn, string $codeColumn) use ($term, $isNumeric, $statusFilter) {
            $where = [];
            $params = [];
            if ($statusFilter) {
                $where[] = $statusFilter[0];
                array_push($params, ...$statusFilter[1]);
            }
            if ($term !== '') {
                $or = [];
                foreach ($searchColumns as $column) {
                    $or[] = "t.$column LIKE ?";
                    $params[] = DB::like($term);
                }
                if ($isNumeric && $table === 'Member') {
                    $or[] = 't.memberId = ?';
                    $params[] = $term;
                }
                $where[] = '(' . implode(' OR ', $or) . ')';
            }
            $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            return DB::all(
                "SELECT t.id, t.photo, t.isActive, t.validityTo, t.dateOfBirth, t.specialDates, t.$codeColumn, t.$nameColumn
                 FROM `$table` t $whereSql",
                $params
            );
        };

        $entries = [];

        if (!in_array($type, self::TYPES, true) || $type === 'member') {
            foreach ($fetch('Member', ['memberName'], 'memberName', 'memberId') as $member) {
                array_push($entries, ...self::entries($member, 'member', $member['memberName'], (string) $member['memberId'], $today));
            }
        }
        if (!in_array($type, self::TYPES, true) || $type === 'partner') {
            foreach ($fetch('Partner', ['partnerName', 'partnerId'], 'partnerName', 'partnerId') as $partner) {
                array_push($entries, ...self::entries($partner, 'partner', $partner['partnerName'], (string) $partner['partnerId'], $today));
            }
        }

        $maxDays = self::PERIOD_DAYS[$period] ?? null;
        if ($maxDays !== null) {
            $entries = array_values(array_filter($entries, fn ($e) => $e['daysLeft'] <= $maxDays));
        }

        $counts = [
            'birthday' => count(array_filter($entries, fn ($e) => $e['occasion'] === 'birthday')),
            'special' => count(array_filter($entries, fn ($e) => $e['occasion'] === 'special')),
        ];

        $occasion = $query['occasion'] ?? null;
        $visible = in_array($occasion, self::OCCASIONS, true)
            ? array_values(array_filter($entries, fn ($e) => $e['occasion'] === $occasion))
            : $entries;

        usort($visible, fn ($a, $b) => ($a['daysLeft'] <=> $b['daysLeft']) ?: strcasecmp($a['name'], $b['name']));

        $page = max((int) ($query['page'] ?? 1) ?: 1, 1);
        $limit = min(max((int) ($query['limit'] ?? 10) ?: 10, 1), 100);
        $total = count($visible);

        return json_reply(200, [
            'success' => true,
            'data' => array_slice($visible, ($page - 1) * $limit, $limit),
            'counts' => $counts,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => max((int) ceil($total / $limit), 1),
            ],
        ]);
    }
}
