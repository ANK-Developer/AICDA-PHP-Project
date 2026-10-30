<?php
declare(strict_types=1);

// Port of partner.controller.js + partner.service.js.
final class PartnerController
{
    private const SORTABLE = ['createdAt', 'updatedAt', 'partnerName', 'partnerId', 'dateOfJoining', 'validityFrom', 'validityTo'];
    private const MAX_PARTNER_ID_ATTEMPTS = 5;

    // Public-safe fields only (no PAN / Aadhaar / date of birth).
    private const PUBLIC_COLUMNS = [
        'id', 'partnerId', 'partnerName', 'fatherName', 'designation', 'companyName', 'companyAddress',
        'companyTelephone', 'residentialAddress', 'residentialTelephone', 'packetNo', 'dateOfJoining',
        'validityFrom', 'validityTo', 'mobile', 'photo', 'isActive', 'state', 'city',
    ];

    // 1 → A, 26 → Z, 27 → AA … (spreadsheet column letters)
    private static function numberToLetters(int $n): string
    {
        $letters = '';
        while ($n > 0) {
            $n -= 1;
            $letters = chr(65 + ($n % 26)) . $letters;
            $n = intdiv($n, 26);
        }
        return $letters;
    }

    // Admin screens use the numeric id; the generated "123A" partnerId works too.
    private static function findRecord($identifier): ?array
    {
        $identifier = (string) $identifier;
        return preg_match('/^\d+$/', $identifier)
            ? DB::one('SELECT * FROM `Partner` WHERE id = ?', [(int) $identifier])
            : DB::one('SELECT * FROM `Partner` WHERE partnerId = ?', [$identifier]);
    }

    private static function findOrFail($identifier): array
    {
        $partner = self::findRecord($identifier);
        if (!$partner) {
            throw new HttpError(404, 'Partner not found');
        }
        return $partner;
    }

    // Loads a partner with its member summary attached (Prisma `include: { member }`).
    private static function withMember(int $id, array $memberColumns = ['id', 'memberId', 'memberName']): array
    {
        $partner = DB::one('SELECT * FROM `Partner` WHERE id = ?', [$id]);
        $member = DB::one(
            'SELECT ' . implode(', ', array_map(fn ($c) => "`$c`", $memberColumns)) . ' FROM `Member` WHERE id = ?',
            [$partner['memberId']]
        );
        $partner['member'] = $member;
        return $partner;
    }

    private static function trimText($value)
    {
        return is_string($value) ? trim($value) : $value;
    }

    private static function amount($value)
    {
        return $value !== null && $value !== '' ? $value : null;
    }

    // POST /api/v1/partners (multipart)
    public static function create(Request $request): array
    {
        $data = $request->body;
        foreach (['state', 'district', 'city'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = self::trimText($data[$field]);
            }
        }
        $has = fn (string $key) => array_key_exists($key, $data) && $data[$key] !== null;
        $value = fn (string $key) => $data[$key] ?? null;

        // Partner's own state/city if given, otherwise the member's.
        $locationGiven = $has('state') || $has('city');
        $dateOfJoining = !empty($data['dateOfJoining']) ? Dates::parse($data['dateOfJoining']) : null;
        $period = !empty($data['validityTo'])
            ? Validity::resolveRenewalPeriod(null, Dates::now(), $data['validityFrom'] ?? null, $data['validityTo'])
            : null;
        $photo = Upload::publicPath($request->file);

        for ($attempt = 1; ; $attempt++) {
            try {
                $partner = DB::transaction(function () use ($data, $has, $value, $locationGiven, $dateOfJoining, $period, $photo) {
                    $member = DB::one('SELECT * FROM `Member` WHERE memberId = ?', [trim((string) $value('memberId'))]);
                    if (!$member) {
                        throw new HttpError(404, 'Member not found');
                    }

                    $last = DB::scalar('SELECT MAX(partnerNumber) FROM `Partner` WHERE memberId = ?', [$member['id']]);
                    $partnerNumber = ($last === null || $last === false) ? 1 : (int) $last + 1;
                    $now = Dates::now();

                    $id = (int) DB::insert('Partner', [
                        'partnerId' => $member['memberId'] . self::numberToLetters($partnerNumber),
                        'partnerNumber' => $partnerNumber,
                        'memberId' => $member['id'],
                        'partnerName' => $value('partnerName'),
                        'fatherName' => $value('fatherName'),
                        'dateOfBirth' => !empty($data['dateOfBirth']) ? Dates::parse($data['dateOfBirth']) : null,
                        'specialDates' => SpecialDates::parse($data['specialDates'] ?? null) ?? [],
                        'photo' => $photo,
                        'residentialAddress' => $value('residentialAddress'),
                        'mobile' => $value('mobile'),
                        'residentialTelephone' => $value('residentialTelephone'),
                        'panCardNo' => $value('panCardNo'),
                        'aadharNo' => $value('aadharNo'),
                        'designation' => $value('designation'),
                        // Common fields: the partner's value if sent, else the member's.
                        'companyName' => $has('companyName') ? $data['companyName'] : $member['companyName'],
                        'companyAddress' => $has('companyAddress') ? $data['companyAddress'] : $member['companyAddress'],
                        'companyTelephone' => $has('companyTelephone') ? $data['companyTelephone'] : $member['companyTelephone'],
                        'packetNo' => $has('packetNo') ? $data['packetNo'] : $member['packetNo'],
                        'state' => $locationGiven ? ($value('state') ?: null) : $member['state'],
                        'district' => array_key_exists('district', $data) ? ($data['district'] ?: null) : $member['district'],
                        'city' => $locationGiven ? ($value('city') ?: null) : $member['city'],
                        'dateOfJoining' => $dateOfJoining,
                        'validityFrom' => $period['validityFrom'] ?? null,
                        'validityTo' => $period['validityTo'] ?? null,
                        'isActive' => true,
                        'createdAt' => $now,
                        'updatedAt' => $now,
                    ]);

                    if ($period) {
                        DB::insert('PartnerRenewal', [
                            'partnerId' => $id,
                            'amount' => self::amount($data['amount'] ?? null),
                            'paymentDate' => $period['paymentDate'],
                            'validityFrom' => $period['validityFrom'],
                            'validityTo' => $period['validityTo'],
                            'note' => ($data['note'] ?? '') !== '' ? $data['note'] : null,
                            'createdAt' => $now,
                        ]);
                    }

                    return DB::one('SELECT * FROM `Partner` WHERE id = ?', [$id]);
                });
                break;
            } catch (Throwable $error) {
                // Two admins adding a partner to the same member at once: retry with a fresh number.
                if (!DB::isDuplicate($error) || $attempt >= self::MAX_PARTNER_ID_ATTEMPTS) {
                    throw $error;
                }
            }
        }

        return json_reply(201, [
            'success' => true,
            'message' => 'Partner created successfully',
            'data' => Membership::withStatus($partner),
        ]);
    }

    // GET /api/v1/partners/:partnerId — with member summary and renewals.
    public static function show(Request $request): array
    {
        $record = self::findOrFail($request->params['partnerId']);
        $partner = self::withMember($record['id']);
        $partner['renewals'] = DB::all(
            'SELECT * FROM `PartnerRenewal` WHERE partnerId = ? ORDER BY validityTo DESC, paymentDate DESC',
            [$record['id']]
        );

        return json_reply(200, [
            'success' => true,
            'message' => 'Partner fetched successfully',
            'data' => Membership::withStatus($partner),
        ]);
    }

    // GET /api/v1/partners
    // Query: search, status, state, district, city, memberId, page, limit, sortBy, order, expiringDays
    public static function index(Request $request): array
    {
        $query = $request->query;
        $where = [];
        $params = [];

        $search = isset($query['search']) && is_string($query['search']) ? trim($query['search']) : '';
        if ($search !== '') {
            $or = [];
            foreach (['p.partnerName', 'p.partnerId', 'p.mobile', 'p.panCardNo', 'p.companyName', 'p.designation', 'm.memberName'] as $column) {
                $or[] = "$column LIKE ?";
                $params[] = DB::like($search);
            }
            if (preg_match('/^\d+$/', $search)) {
                $or[] = 'm.memberId = ?';
                $params[] = $search;
            }
            $where[] = '(' . implode(' OR ', $or) . ')';
        }

        $expiringDays = Membership::parseExpiringDays($query['expiringDays'] ?? null);
        $statusFilter = Membership::filter($query['status'] ?? null, $expiringDays, 'p');
        if ($statusFilter) {
            $where[] = $statusFilter[0];
            array_push($params, ...$statusFilter[1]);
        }

        foreach (['state', 'district', 'city'] as $column) {
            if (!empty($query[$column])) {
                $where[] = "p.$column = ?";
                $params[] = $query[$column];
            }
        }

        if (!empty($query['memberId'])) {
            $memberPk = DB::scalar('SELECT id FROM `Member` WHERE memberId = ?', [trim((string) $query['memberId'])]);
            // No such member → empty result instead of ignoring the filter.
            $where[] = 'p.memberId = ?';
            $params[] = $memberPk === false ? -1 : (int) $memberPk;
        }

        $page = max((int) ($query['page'] ?? 1) ?: 1, 1);
        $limit = min(max((int) ($query['limit'] ?? 10) ?: 10, 1), 100);
        $sortField = in_array($query['sortBy'] ?? '', self::SORTABLE, true) ? $query['sortBy'] : 'createdAt';
        $sortOrder = ($query['order'] ?? '') === 'asc' ? 'ASC' : 'DESC';
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $limit;
        $from = 'FROM `Partner` p LEFT JOIN `Member` m ON m.id = p.memberId';

        $rows = DB::all(
            "SELECT p.*, m.id AS __mId, m.memberId AS __mMemberId, m.memberName AS __mName
             $from $whereSql ORDER BY p.`$sortField` $sortOrder LIMIT $limit OFFSET $offset",
            $params
        );
        $total = (int) DB::scalar("SELECT COUNT(*) $from $whereSql", $params);

        $partners = array_map(function (array $row) {
            // Some old partners have no member: member is then null.
            $row['member'] = $row['__mId'] === null ? null : ['id' => $row['__mId'], 'memberId' => $row['__mMemberId'], 'memberName' => $row['__mName']];
            unset($row['__mId'], $row['__mMemberId'], $row['__mName']);
            return Membership::withStatus($row);
        }, $rows);

        return json_reply(200, [
            'success' => true,
            'count' => count($partners),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => max((int) ceil($total / $limit), 1),
            ],
            'stats' => Membership::counts('Partner', $expiringDays),
            'data' => $partners,
        ]);
    }

    public static function publicSelect(string $where, array $params): array
    {
        $columns = implode(', ', array_map(fn ($c) => "p.`$c`", self::PUBLIC_COLUMNS));
        $rows = DB::all(
            "SELECT $columns, m.id AS __mId, m.memberId AS __mMemberId, m.memberName AS __mName
             FROM `Partner` p LEFT JOIN `Member` m ON m.id = p.memberId $where",
            $params
        );

        return array_map(function (array $row) {
            // Some old partners have no member: member is then null.
            $row['member'] = $row['__mId'] === null ? null : ['id' => $row['__mId'], 'memberId' => $row['__mMemberId'], 'memberName' => $row['__mName']];
            unset($row['__mId'], $row['__mMemberId'], $row['__mName']);
            return Membership::withStatus($row);
        }, $rows);
    }

    // GET /api/v1/partners/public
    public static function publicIndex(Request $request): array
    {
        $partners = self::publicSelect('WHERE p.isShow = 1 ORDER BY p.createdAt DESC', []);

        return json_reply(200, [
            'success' => true,
            'message' => 'Public partners fetched successfully',
            'count' => count($partners),
            'data' => $partners,
        ]);
    }

    // GET /api/v1/partners/public/:id
    public static function publicShow(Request $request): array
    {
        $identifier = $request->params['id'];
        $partners = preg_match('/^\d+$/', $identifier)
            ? self::publicSelect('WHERE p.isShow = 1 AND p.id = ?', [(int) $identifier])
            : self::publicSelect('WHERE p.isShow = 1 AND p.partnerId = ?', [$identifier]);

        if (!$partners) {
            return json_reply(404, ['success' => false, 'message' => 'Partner not found']);
        }

        return json_reply(200, ['success' => true, 'data' => $partners[0]]);
    }

    // GET /api/v1/partners/member/:memberId
    public static function byMember(Request $request): array
    {
        $member = DB::one('SELECT id FROM `Member` WHERE memberId = ?', [$request->params['memberId']]);
        if (!$member) {
            return json_reply(404, ['success' => false, 'message' => 'Member not found']);
        }

        $partners = array_map(
            [Membership::class, 'withStatus'],
            DB::all('SELECT * FROM `Partner` WHERE memberId = ? ORDER BY partnerNumber ASC', [$member['id']])
        );

        return json_reply(200, ['success' => true, 'count' => count($partners), 'data' => $partners]);
    }

    // PATCH /api/v1/partners/:partnerId (multipart). Validity only changes via /renew.
    public static function update(Request $request): array
    {
        $existing = self::findOrFail($request->params['partnerId']);
        $body = $request->body;
        foreach (['state', 'district', 'city'] as $field) {
            if (array_key_exists($field, $body)) {
                $body[$field] = self::trimText($body[$field]);
            }
        }
        $has = fn (string $key) => array_key_exists($key, $body) && $body[$key] !== null;

        $data = [];
        foreach ([
            'partnerName', 'fatherName', 'residentialAddress', 'mobile', 'residentialTelephone', 'panCardNo',
            'aadharNo', 'designation', 'companyName', 'companyAddress', 'companyTelephone', 'packetNo', 'district',
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
        // An empty string clears the value; not sent leaves it unchanged.
        if ($has('state')) {
            $data['state'] = $body['state'] ?: null;
        }
        if ($has('city')) {
            $data['city'] = $body['city'] ?: null;
        }

        $data['updatedAt'] = Dates::now();
        DB::update('Partner', $data, 'id = ?', [$existing['id']]);

        return json_reply(200, [
            'success' => true,
            'message' => 'Partner updated successfully',
            'data' => Membership::withStatus(self::withMember($existing['id'], ['memberId', 'memberName'])),
        ]);
    }

    // PATCH /api/v1/partners/:partnerId/visibility   body: { isShow }
    // Shows or hides the partner on the public website.
    public static function setVisibility(Request $request): array
    {
        $isShow = $request->input('isShow');
        if (!is_bool($isShow)) {
            return json_reply(400, ['success' => false, 'message' => 'isShow must be true or false']);
        }

        $partner = self::findOrFail($request->params['partnerId']);
        DB::update('Partner', ['isShow' => $isShow, 'updatedAt' => Dates::now()], 'id = ?', [$partner['id']]);

        return json_reply(200, [
            'success' => true,
            'message' => $isShow ? 'Partner is now shown on the website' : 'Partner is now hidden from the website',
            'data' => Membership::withStatus(self::withMember($partner['id'])),
        ]);
    }

    // PATCH /api/v1/partners/:partnerId/status
    public static function setStatus(Request $request): array
    {
        $change = Membership::parseStatusChange($request->body, $request->admin);
        $partner = self::findOrFail($request->params['partnerId']);

        DB::update('Partner', $change + ['updatedAt' => Dates::now()], 'id = ?', [$partner['id']]);
        $updated = Membership::withStatus(self::withMember($partner['id']));

        return json_reply(200, [
            'success' => true,
            'message' => $updated['status']['status'] === 'ACTIVE' ? 'Partner activated successfully' : 'Partner deactivated successfully',
            'data' => $updated,
        ]);
    }

    // PATCH /api/v1/partners/:partnerId/renew
    public static function renew(Request $request): array
    {
        $partner = self::findOrFail($request->params['partnerId']);
        $body = $request->body;

        $period = Validity::resolveRenewalPeriod(
            $partner['validityTo'],
            $partner['createdAt'],
            $body['validityFrom'] ?? null,
            $body['validityTo'] ?? null
        );

        $renewed = DB::transaction(function () use ($partner, $body, $period) {
            $now = Dates::now();
            DB::update('Partner', [
                'validityFrom' => $period['validityFrom'],
                'validityTo' => $period['validityTo'],
                'updatedAt' => $now,
            ], 'id = ?', [$partner['id']]);

            DB::insert('PartnerRenewal', [
                'partnerId' => $partner['id'],
                'amount' => self::amount($body['amount'] ?? null),
                'paymentDate' => $period['paymentDate'],
                'validityFrom' => $period['validityFrom'],
                'validityTo' => $period['validityTo'],
                'note' => ($body['note'] ?? '') !== '' ? $body['note'] : null,
                'createdAt' => $now,
            ]);

            return self::withMember($partner['id']);
        });

        return json_reply(200, [
            'success' => true,
            'message' => 'Partner renewed successfully',
            'data' => Membership::withStatus($renewed),
        ]);
    }

    // DELETE /api/v1/partners/:partnerId
    public static function destroy(Request $request): array
    {
        $partner = self::findOrFail($request->params['partnerId']);
        DB::run('DELETE FROM `Partner` WHERE id = ?', [$partner['id']]);

        return json_reply(200, ['success' => true, 'message' => 'Partner deleted successfully']);
    }

    // ---------- Special dates ----------

    private static function saveSpecialDates(int $id, array $list): array
    {
        DB::update('Partner', ['specialDates' => $list, 'updatedAt' => Dates::now()], 'id = ?', [$id]);
        return SpecialDates::asList(DB::one('SELECT specialDates FROM `Partner` WHERE id = ?', [$id])['specialDates'] ?? null);
    }

    public static function addSpecialDate(Request $request): array
    {
        $partner = self::findOrFail($request->params['partnerId']);
        $list = SpecialDates::asList($partner['specialDates'] ?? null);
        $list[] = SpecialDates::build($request->body);

        return json_reply(201, [
            'success' => true,
            'message' => 'Special date added successfully',
            'data' => self::saveSpecialDates($partner['id'], $list),
        ]);
    }

    public static function updateSpecialDate(Request $request): array
    {
        $partner = self::findOrFail($request->params['partnerId']);
        $updated = SpecialDates::update(
            SpecialDates::asList($partner['specialDates'] ?? null),
            $request->params['dateId'],
            $request->body
        );

        if ($updated === null) {
            throw new HttpError(404, 'Special date not found');
        }

        return json_reply(200, [
            'success' => true,
            'message' => 'Special date updated successfully',
            'data' => self::saveSpecialDates($partner['id'], $updated),
        ]);
    }

    public static function deleteSpecialDate(Request $request): array
    {
        $partner = self::findOrFail($request->params['partnerId']);
        $list = SpecialDates::asList($partner['specialDates'] ?? null);
        $remaining = array_values(array_filter($list, fn ($entry) => ($entry['id'] ?? null) !== $request->params['dateId']));

        if (count($remaining) === count($list)) {
            throw new HttpError(404, 'Special date not found');
        }

        return json_reply(200, [
            'success' => true,
            'message' => 'Special date deleted successfully',
            'data' => self::saveSpecialDates($partner['id'], $remaining),
        ]);
    }
}
