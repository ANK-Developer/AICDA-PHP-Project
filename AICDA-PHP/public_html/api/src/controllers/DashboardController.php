<?php
declare(strict_types=1);

final class DashboardController
{
    // GET /api/v1/dashboard — latest event, image and political achievement.
    public static function index(Request $request): array
    {
        try {
            $latest = fn (string $category) => DB::one(
                'SELECT * FROM `Gallery` WHERE category = ? ORDER BY createdAt DESC LIMIT 1',
                [$category]
            );

            return json_reply(200, [
                'success' => true,
                'message' => 'Dashboard data fetched successfully',
                'data' => [
                    'event' => $latest('ASSOCIATION'),
                    'image' => $latest('IMAGE'),
                    'politicalAchievement' => $latest('POLITICAL_ACHIEVEMENT'),
                ],
            ]);
        } catch (Throwable $error) {
            error_log('Dashboard Error: ' . $error->getMessage());
            return json_reply(500, [
                'success' => false,
                // Never send database / server details to the browser.
                'message' => 'Failed to fetch dashboard data',
            ]);
        }
    }
}
