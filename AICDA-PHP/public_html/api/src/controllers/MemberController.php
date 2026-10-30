<?php
declare(strict_types=1);

// Port of member.controller.js + member.service.js.
final class MemberController
{
    private const SORTABLE = ['createdAt', 'updatedAt', 'memberId', 'memberName', 'dateOfJoining', 'validityFrom', 'validityTo'];
    // Public pages never get PAN / Aadhaar / date of birth.
    private const PUBLIC_COLUMNS = [
        'id', 'memberId', 'memberName', 'fatherName', 'designation', 'companyName', 'companyAddress',
        'companyTelephone', 'residentialAddress', 'residentialTelephone', 'packetNo', 'dateOfJoining',
        'validityFrom', 'validityTo', 'mobile', 'photo', 'isActive', 'state', 'city',
    ];

    private const PUBLIC_PARTNER_COLUMNS = [
        'id', 'partnerId', 'partnerName', 'designation', 'companyName', 'mobile', 'photo', 'isActive', 'validityTo',
    ];

    private static function columns(array $columns, string $alias = ''): string
    {
        return implode(', ', array_map(fn ($c) => ($alias ? "$alias." : '') . "`$c`", $columns));
    }

    private static function numericId($id): int
    {
        if (!preg_match('/^\d+$/', (string) $id)) {
            throw new HttpError(404, 'Member not found');
        }
        return (int) $id;
    }

    private static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM `Member` WHERE id = ?', [$id]);
    }

    private static function findOrFail($id): array
    {
        $member = self::find(self::numericId($id));
        if (!$member) {
            throw new HttpError(404, 'Member not found');
        }
        return $member;
    }

    private static function orNull($value)
    {
        return $value === null || $value === '' ? null : $value;
    }

    private static function amount($value)
    {
        return $value !== null && $value !== '' ? $value : null;
    }

    // POST /api/v1/members (multipart)
    public static function create(Request $request): array
    {
        $body = $request->body;
        $photo = Upload::publicPath($request->file);
        $dateOfJoining = !empty($body['dateOfJoining']) ? Dates::parse($body['dateOfJoining']) : null;

        // A member created without payment has no validity yet.
        $period = !empty($body['validityTo'])
            ? Validity::resolveRenewalPeriod(null, Dates::now(), $body['validityFrom'] ?? null, $body['validityTo'])
            : null;

        $member = DB::transaction(function () use ($body, $photo, $dateOfJoining, $period) {
            $now = Dates::now();

            $id = (int) DB::insert('Member', [
                'memberId' => trim((string) $body['memberId']),
                'memberName' => $body['memberName'],
                'fatherName' => self::orNull($body['fatherName'] ?? null),
                'dateOfBirth' => !empty($body['dateOfBirth']) ? Dates::parse($body['dateOfBirth']) : null,
                'specialDates' => SpecialDates::parse($body['specialDates'] ?? null) ?? [],
                'photo' => $photo,
                'residentialAddress' => self::orNull($body['residentialAddress'] ?? null),
                'mobile' => self::orNull($body['mobile'] ?? null),
                'residentialTelephone' => self::orNull($body['residentialTelephone'] ?? null),
                'panCardNo' => self::orNull($body['panCardNo'] ?? null),
                'designation' => self::orNull($body['designation'] ?? null),
                'companyName' => self::orNull($body['companyName'] ?? null),
                'companyAddress' => self::orNull($body['companyAddress'] ?? null),
                'companyTelephone' => self::orNull($body['companyTelephone'] ?? null),
                'packetNo' => self::orNull($body['packetNo'] ?? null),
                'dateOfJoining' => $dateOfJoining,
                'validityFrom' => $period['validityFrom'] ?? null,
                'validityTo' => $period['validityTo'] ?? null,
                'aadharNo' => self::orNull($body['aadharNo'] ?? null),
                'state' => self::orNull($body['state'] ?? null),
                'district' => self::orNull($body['district'] ?? null),
                'city' => self::orNull($body['city'] ?? null),
                'isActive' => true,
                'createdAt' => $now,
                'updatedAt' => $now,
            ]);

            if ($period) {
                DB::insert('MemberRenewal', [
                    'memberId' => $id,
                    'amount' => self::amount($body['amount'] ?? null),
                    'paymentDate' => $period['paymentDate'],
                    'validityFrom' => $period['validityFrom'],
                    'validityTo' => $period['validityTo'],
                    'note' => self::orNull($body['note'] ?? null),
                    'createdAt' => $now,
                ]);
            }

            return self::find($id);
        });

        return json_reply(201, [
            'success' => true,
            'message' => 'Member created successfully',
            'data' => Membership::withStatus($member),
        ]);
    }

    // GET /api/v1/members
    // Query: search, status, state, district, city, page, limit, sortBy, order, expiringDays
    public static function index(Request $request): array
    {
        $query = $request->query;
        $where = [];
        $params = [];

        $search = isset($query['search']) && is_string($query['search']) ? $query['search'] : '';
        if ($search !== '') {
            $or = [];
            if (preg_match('/^\d+$/', trim($search))) {
                $or[] = 'm.memberId = ?';
                $params[] = trim($search);
            }
            foreach (['memberName', 'companyName', 'mobile', 'designation'] as $column) {
                $or[] = "m.$column LIKE ?";
                $params[] = DB::like($search);
            }
            $where[] = '(' . implode(' OR ', $or) . ')';
        }

        $expiringDays = Membership::parseExpiringDays($query['expiringDays'] ?? null);
        $statusFilter = Membership::filter($query['status'] ?? null, $expiringDays, 'm');
        if ($statusFilter) {
            $where[] = $statusFilter[0];
            array_push($params, ...$statusFilter[1]);
        }

        foreach (['state', 'district', 'city'] as $column) {
            if (!empty($query[$column])) {
                $where[] = "m.$column = ?";
                $params[] = $query[$column];
            }
        }

        $page = max((int) ($query['page'] ?? 1) ?: 1, 1);
        $limit = min(max((int) ($query['limit'] ?? 10) ?: 10, 1), 100);
        $sortField = in_array($query['sortBy'] ?? '', self::SORTABLE, true) ? $query['sortBy'] : 'createdAt';
        $sortOrder = ($query['order'] ?? '') === 'asc' ? 'ASC' : 'DESC';
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $limit;
        // Member IDs are text (they keep leading zeros, e.g. 0560), so sort them by number first.
        $orderBy = $sortField === 'memberId'
            ? "CAST(m.memberId AS UNSIGNED) $sortOrder, m.memberId $sortOrder"
            : "m.`$sortField` $sortOrder";

        $rows = DB::all(
            "SELECT m.*, (SELECT COUNT(*) FROM `Partner` p WHERE p.memberId = m.id) AS __partners
             FROM `Member` m $whereSql ORDER BY $orderBy LIMIT $limit OFFSET $offset",
            $params
        );
        $total = (int) DB::scalar("SELECT COUNT(*) FROM `Member` m $whereSql", $params);

        $members = array_map(function (array $row) {
            $row['_count'] = ['partners' => (int) $row['__partners']];
            unset($row['__partners']);
            return Membership::withStatus($row);
        }, $rows);

        return json_reply(200, [
            'success' => true,
            'count' => count($members),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => max((int) ceil($total / $limit), 1),
            ],
            'stats' => Membership::counts('Member', $expiringDays),
            'data' => $members,
        ]);
    }

    // GET /api/v1/members/public
    public static function publicIndex(Request $request): array
    {
        $members = DB::all('SELECT ' . self::columns(self::PUBLIC_COLUMNS) . ' FROM `Member` WHERE isShow = 1 ORDER BY createdAt DESC');

        return json_reply(200, [
            'success' => true,
            'data' => array_map([Membership::class, 'withStatus'], $members),
        ]);
    }

    // GET /api/v1/management?page=&limit=&search=
    // The public Management page: members and partners in ONE list, in ID order, a page at a time.
    // `search` matches IDs only: a member ID (also finds that member's partners) or a partner ID.
    public static function publicPage(Request $request): array
    {
        $page = max((int) ($request->query['page'] ?? 1) ?: 1, 1);
        $limit = min(max((int) ($request->query['limit'] ?? 10) ?: 10, 1), 50);
        $search = is_string($request->query['search'] ?? null) ? trim($request->query['search']) : '';

        $source = "(SELECT 'member' AS kind, id, memberId AS code, memberId AS owner FROM `Member` WHERE isShow = 1
                    UNION ALL
                    SELECT 'partner', p.id, p.partnerId, m.memberId FROM `Partner` p
                    LEFT JOIN `Member` m ON m.id = p.memberId WHERE p.isShow = 1) t";
        $where = $search === '' ? '' : 'WHERE code = ? OR owner = ?';
        $params = $search === '' ? [] : [$search, $search];
        $offset = ($page - 1) * $limit;

        $total = (int) DB::scalar("SELECT COUNT(*) FROM $source $where", $params);
        $rows = DB::all(
            "SELECT kind, id FROM $source $where
             ORDER BY CAST(code AS UNSIGNED), kind, code LIMIT $limit OFFSET $offset",
            $params
        );

        // Load the full public record of each row on this page, then put them back in order.
        $found = ['member' => [], 'partner' => []];
        foreach (['member', 'partner'] as $kind) {
            $ids = array_map(fn ($r) => (int) $r['id'], array_filter($rows, fn ($r) => $r['kind'] === $kind));
            if (!$ids) {
                continue;
            }
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $records = $kind === 'member'
                ? array_map([Membership::class, 'withStatus'], DB::all('SELECT ' . self::columns(self::PUBLIC_COLUMNS) . " FROM `Member` WHERE id IN ($marks)", $ids))
                : PartnerController::publicSelect("WHERE p.id IN ($marks)", $ids);
            foreach ($records as $record) {
                $found[$kind][(int) $record['id']] = ['type' => $kind] + $record;
            }
        }

        return json_reply(200, [
            'success' => true,
            'data' => array_values(array_filter(array_map(fn ($r) => $found[$r['kind']][(int) $r['id']] ?? null, $rows))),
            'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'totalPages' => max((int) ceil($total / $limit), 1)],
        ]);
    }
    // GET /api/v1/members/public/:id — plus the member's partners.
    public static function publicShow(Request $request): array
    {
        $id = preg_match('/^\d+$/', $request->params['id']) ? (int) $request->params['id'] : 0;
        $member = DB::one('SELECT ' . self::columns(self::PUBLIC_COLUMNS) . ' FROM `Member` WHERE id = ? AND isShow = 1', [$id]);

        if (!$member) {
            return json_reply(404, ['success' => false, 'message' => 'Member not found']);
        }

        $partners = DB::all(
            'SELECT ' . self::columns(self::PUBLIC_PARTNER_COLUMNS) . ' FROM `Partner` WHERE memberId = ? AND isShow = 1 ORDER BY partnerNumber ASC',
            [$id]
        );

        $result = Membership::withStatus($member);
        $result['partners'] = array_map([Membership::class, 'withStatus'], $partners);

        return json_reply(200, ['success' => true, 'data' => $result]);
    }

    // GET /api/v1/members/:id — with partners and renewal (payment) history.
    public static function show(Request $request): array
    {
        $id = preg_match('/^\d+$/', $request->params['id']) ? (int) $request->params['id'] : 0;
        $member = self::find($id);

        if (!$member) {
            return json_reply(404, ['success' => false, 'message' => 'Member not found']);
        }

        $member['partners'] = array_map(
            [Membership::class, 'withStatus'],
            DB::all('SELECT * FROM `Partner` WHERE memberId = ? ORDER BY partnerNumber ASC', [$id])
        );
        $member['renewals'] = DB::all(
            'SELECT * FROM `MemberRenewal` WHERE memberId = ? ORDER BY validityTo DESC, paymentDate DESC',
            [$id]
        );

        return json_reply(200, ['success' => true, 'data' => Membership::withStatus($member)]);
    }

    // PUT /api/v1/members/:id (multipart). Validity and payments only change via /renew.
    public static function update(Request $request): array
    {
        $existing = self::findOrFail($request->params['id']);
        $body = $request->body;
        $has = fn (string $key) => array_key_exists($key, $body);

        $data = [];

        if ($has('memberId')) {
            $data['memberId'] = trim((string) $body['memberId']);
        }
        foreach ([
            'memberName', 'fatherName', 'residentialAddress', 'mobile', 'residentialTelephone', 'panCardNo',
            'designation', 'companyName', 'companyAddress', 'companyTelephone', 'packetNo', 'aadharNo', 'district',
        ] as $field) {
            if ($has($field)) {
                $data[$field] = $body[$field];
            }
        }
        if ($has('dateOfBirth')) {
            $data['dateOfBirth'] = $body['dateOfBirth'] ? Dates::parse($body['dateOfBirth']) : null;
        }
        if ($has('dateOfJoining')) {
            $data['dateOfJoining'] = $body['dateOfJoining'] ? Dates::parse($body['dateOfJoining']) : null;
        }
        $specialDates = SpecialDates::parse($body['specialDates'] ?? null);
        if ($specialDates !== null) {
            $data['specialDates'] = $specialDates;
        }
        if ($request->file) {
            $data['photo'] = Upload::publicPath($request->file);
        }
        if ($has('state')) {
            $data['state'] = self::orNull($body['state']);
        }
        if ($has('city')) {
            $data['city'] = self::orNull($body['city']);
        }

        $data['updatedAt'] = Dates::now();
        DB::update('Member', $data, 'id = ?', [$existing['id']]);

        return json_reply(200, [
            'success' => true,
            'message' => 'Member updated successfully',
            'data' => Membership::withStatus(self::find($existing['id'])),
        ]);
    }

    // PATCH /api/v1/members/:id/visibility   body: { isShow }
    // Shows or hides the member on the public website (Management page and profile link).
    public static function setVisibility(Request $request): array
    {
        $isShow = $request->input('isShow');
        if (!is_bool($isShow)) {
            return json_reply(400, ['success' => false, 'message' => 'isShow must be true or false']);
        }

        $member = self::findOrFail($request->params['id']);
        DB::update('Member', ['isShow' => $isShow, 'updatedAt' => Dates::now()], 'id = ?', [$member['id']]);

        return json_reply(200, [
            'success' => true,
            'message' => $isShow ? 'Member is now shown on the website' : 'Member is now hidden from the website',
            'data' => Membership::withStatus(self::find($member['id'])),
        ]);
    }

    // PATCH /api/v1/members/:id/status   body: { isActive, reason }
    public static function setStatus(Request $request): array
    {
        $change = Membership::parseStatusChange($request->body, $request->admin);
        $member = self::findOrFail($request->params['id']);

        DB::update('Member', $change + ['updatedAt' => Dates::now()], 'id = ?', [$member['id']]);
        $updated = Membership::withStatus(self::find($member['id']));

        return json_reply(200, [
            'success' => true,
            'message' => $updated['status']['status'] === 'ACTIVE' ? 'Member activated successfully' : 'Member deactivated successfully',
            'data' => $updated,
        ]);
    }

    // PATCH /api/v1/members/:id/renew   body: { validityFrom?, validityTo, amount?, note? }
    public static function renew(Request $request): array
    {
        $member = self::findOrFail($request->params['id']);
        $body = $request->body;

        $period = Validity::resolveRenewalPeriod(
            $member['validityTo'],
            $member['createdAt'],
            $body['validityFrom'] ?? null,
            $body['validityTo'] ?? null
        );

        $renewed = DB::transaction(function () use ($member, $body, $period) {
            $now = Dates::now();
            DB::update('Member', [
                'validityFrom' => $period['validityFrom'],
                'validityTo' => $period['validityTo'],
                'updatedAt' => $now,
            ], 'id = ?', [$member['id']]);

            DB::insert('MemberRenewal', [
                'memberId' => $member['id'],
                'amount' => self::amount($body['amount'] ?? null),
                'paymentDate' => $period['paymentDate'],
                'validityFrom' => $period['validityFrom'],
                'validityTo' => $period['validityTo'],
                'note' => self::orNull($body['note'] ?? null),
                'createdAt' => $now,
            ]);

            return self::find($member['id']);
        });

        return json_reply(200, [
            'success' => true,
            'message' => 'Member renewed successfully',
            'data' => Membership::withStatus($renewed),
        ]);
    }

    // DELETE /api/v1/members/:id
    public static function destroy(Request $request): array
    {
        $member = self::findOrFail($request->params['id']);
        DB::run('DELETE FROM `Member` WHERE id = ?', [$member['id']]);

        return json_reply(200, ['success' => true, 'message' => 'Member deleted successfully']);
    }

    // ---------- Special dates ----------

    private static function specialDatesOf($id): array
    {
        $member = self::findOrFail($id);
        return [$member['id'], SpecialDates::asList($member['specialDates'] ?? null)];
    }

    private static function saveSpecialDates(int $id, array $list): array
    {
        DB::update('Member', ['specialDates' => $list, 'updatedAt' => Dates::now()], 'id = ?', [$id]);
        return SpecialDates::asList(self::find($id)['specialDates'] ?? null);
    }

    // POST /api/v1/members/:id/special-dates
    public static function addSpecialDate(Request $request): array
    {
        [$id, $list] = self::specialDatesOf($request->params['id']);
        $list[] = SpecialDates::build($request->body);

        return json_reply(201, [
            'success' => true,
            'message' => 'Special date added successfully',
            'data' => self::saveSpecialDates($id, $list),
        ]);
    }

    // PUT /api/v1/members/:id/special-dates/:dateId
    public static function updateSpecialDate(Request $request): array
    {
        [$id, $list] = self::specialDatesOf($request->params['id']);
        $updated = SpecialDates::update($list, $request->params['dateId'], $request->body);

        if ($updated === null) {
            throw new HttpError(404, 'Special date not found');
        }

        return json_reply(200, [
            'success' => true,
            'message' => 'Special date updated successfully',
            'data' => self::saveSpecialDates($id, $updated),
        ]);
    }

    // DELETE /api/v1/members/:id/special-dates/:dateId
    public static function deleteSpecialDate(Request $request): array
    {
        [$id, $list] = self::specialDatesOf($request->params['id']);
        $remaining = array_values(array_filter($list, fn ($entry) => ($entry['id'] ?? null) !== $request->params['dateId']));

        if (count($remaining) === count($list)) {
            throw new HttpError(404, 'Special date not found');
        }

        return json_reply(200, [
            'success' => true,
            'message' => 'Special date deleted successfully',
            'data' => self::saveSpecialDates($id, $remaining),
        ]);
    }
}
