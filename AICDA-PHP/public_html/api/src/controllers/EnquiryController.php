<?php
declare(strict_types=1);

final class EnquiryController
{
    public const STATUSES = ['NEW', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'];

    // POST /api/v1/enquiries (public "Become a Member" form)
    public static function submit(Request $request): array
    {
        try {
            $body = $request->body;
            $optional = fn (string $key) => ($body[$key] ?? '') !== '' ? $body[$key] : null;
            $now = Dates::now();

            $id = DB::insert('Enquiry', [
                'requestType' => $body['requestType'],
                'fullName' => $body['fullName'],
                'mobile' => $body['mobile'],
                'email' => $optional('email'),
                'companyName' => $optional('companyName'),
                'city' => $optional('city'),
                'state' => $optional('state'),
                'message' => $optional('message'),
                'status' => 'NEW',
                'createdAt' => $now,
                'updatedAt' => $now,
            ]);

            return json_reply(201, [
                'success' => true,
                'message' => 'Enquiry submitted successfully',
                'data' => DB::one('SELECT * FROM `Enquiry` WHERE id = ?', [(int) $id]),
            ]);
        } catch (Throwable $error) {
            error_log('Submit enquiry error: ' . $error->getMessage());
            return json_reply(500, ['success' => false, 'message' => 'Failed to submit enquiry']);
        }
    }

    // GET /api/v1/enquiries   Query: page, limit, search, status
    // `stats` ignores search and status so the header counters show all enquiries.
    public static function index(Request $request): array
    {
        try {
            $query = $request->query;
            $term = isset($query['search']) && is_string($query['search']) ? trim($query['search']) : '';
            $status = in_array($query['status'] ?? null, self::STATUSES, true) ? $query['status'] : null;
            $page = max((int) ($query['page'] ?? 1) ?: 1, 1);
            $limit = min(max((int) ($query['limit'] ?? 10) ?: 10, 1), 100);

            $where = [];
            $params = [];
            if ($status) {
                $where[] = 'status = ?';
                $params[] = $status;
            }
            if ($term !== '') {
                $columns = ['fullName', 'mobile', 'email', 'companyName', 'city', 'state', 'message'];
                $where[] = '(' . implode(' OR ', array_map(fn ($c) => "$c LIKE ?", $columns)) . ')';
                array_push($params, ...array_fill(0, count($columns), DB::like($term)));
            }
            $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            $offset = ($page - 1) * $limit;

            $enquiries = DB::all("SELECT * FROM `Enquiry` $whereSql ORDER BY createdAt DESC LIMIT $limit OFFSET $offset", $params);
            $total = (int) DB::scalar("SELECT COUNT(*) FROM `Enquiry` $whereSql", $params);

            $stats = ['total' => 0, 'NEW' => 0, 'IN_PROGRESS' => 0, 'RESOLVED' => 0, 'CLOSED' => 0];
            foreach (DB::all('SELECT status, COUNT(*) AS c FROM `Enquiry` GROUP BY status') as $row) {
                $stats[$row['status']] = (int) $row['c'];
                $stats['total'] += (int) $row['c'];
            }

            return json_reply(200, [
                'success' => true,
                'count' => $total,
                'enquiries' => $enquiries,
                'stats' => $stats,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'totalPages' => max((int) ceil($total / $limit), 1),
                ],
            ]);
        } catch (Throwable $error) {
            error_log('Get enquiries error: ' . $error->getMessage());
            return json_reply(500, ['success' => false, 'message' => 'Failed to load enquiries']);
        }
    }

    // PATCH /api/v1/enquiries/:id/status   body: { status }
    public static function changeStatus(Request $request): array
    {
        try {
            $id = preg_match('/^\d+$/', $request->params['id']) ? (int) $request->params['id'] : 0;
            $existing = DB::one('SELECT * FROM `Enquiry` WHERE id = ?', [$id]);

            if (!$existing) {
                return json_reply(404, ['success' => false, 'message' => 'Enquiry not found']);
            }

            $status = $request->input('status');
            if ($existing['status'] !== $status) {
                DB::update('Enquiry', ['status' => $status, 'updatedAt' => Dates::now()], 'id = ?', [$id]);
                $existing = DB::one('SELECT * FROM `Enquiry` WHERE id = ?', [$id]);
            }

            return json_reply(200, [
                'success' => true,
                'message' => 'Enquiry status updated successfully',
                'data' => $existing,
            ]);
        } catch (Throwable $error) {
            error_log('Update enquiry status error: ' . $error->getMessage());
            return json_reply(500, ['success' => false, 'message' => 'Failed to update enquiry status']);
        }
    }
}
