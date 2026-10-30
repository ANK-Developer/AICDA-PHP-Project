<?php
declare(strict_types=1);

final class GalleryController
{
    public const CATEGORIES = ['ASSOCIATION', 'POLITICAL_ACHIEVEMENT', 'IMAGE', 'DIRECTORY', 'LETTER', 'BANNER'];

    // Keep in sync with AICDA/src/lib/banner-sections.js (key stored in Gallery.title).
    public const BANNER_SECTION_LIMITS = [
        'home' => 10,
        'association-events' => 5,
        'political-achievements' => 5,
        'image' => 5,
        'management' => 5,
        'letter' => 5,
        'about-us' => 5,
        'contact-us' => 5,
        'become-member' => 5,
    ];

    // Like Node, report the error text — except DB / PHP engine internals.
    private static function safeMessage(Throwable $error): string
    {
        return $error instanceof PDOException || $error instanceof Error ? '' : $error->getMessage();
    }

    private static function categoryError(): array
    {
        return json_reply(400, ['success' => false, 'message' => 'Category must be one of: ' . implode(', ', self::CATEGORIES)]);
    }

    private static function isBannerSection($key): bool
    {
        return is_string($key) && array_key_exists($key, self::BANNER_SECTION_LIMITS);
    }

    // Videos are links (YouTube, Vimeo or a direct video URL), never uploads.
    private static function parseVideoUrl($value): ?string
    {
        $url = trim((string) $value);
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) && parse_url($url, PHP_URL_HOST) ? $url : null;
    }

    // Banners play in their admin-chosen order; everything else is newest first.
    private static function order(?string $category): string
    {
        return $category === 'BANNER' ? 'sortOrder ASC, createdAt ASC' : 'createdAt DESC';
    }

    private static function find(string $id): ?array
    {
        return DB::one('SELECT * FROM `Gallery` WHERE id = ?', [$id]);
    }

    // POST /api/v1/gallery/upload
    public static function upload(Request $request): array
    {
        try {
            $category = $request->input('category');
            $title = $request->input('title');
            $description = $request->input('description');

            if (!$category || !in_array($category, self::CATEGORIES, true)) {
                Upload::discard($request);
                return self::categoryError();
            }

            $isVideo = strtoupper((string) $request->input('resourceType', '')) === 'VIDEO';

            if ($isVideo) {
                Upload::discard($request);
                $fileUrl = self::parseVideoUrl($request->input('videoUrl'));
                if (!$fileUrl) {
                    return json_reply(400, ['success' => false, 'message' => 'A valid video URL (http or https) is required']);
                }
                $defaultTitle = 'Video';
            } else {
                if (!$request->file) {
                    return json_reply(400, ['success' => false, 'message' => 'Image is required']);
                }
                if (!str_starts_with($request->file['mimetype'], 'image/')) {
                    Upload::discard($request);
                    return json_reply(400, ['success' => false, 'message' => 'Selected file is not an image']);
                }
                $fileUrl = Upload::publicPath($request->file);
                $defaultTitle = $request->file['originalname'];
            }

            $sortOrder = 0;

            if ($category === 'BANNER') {
                $section = is_string($title) ? trim($title) : null;

                if ($isVideo || !self::isBannerSection($section)) {
                    Upload::discard($request);
                    return json_reply(400, ['success' => false, 'message' => 'Choose a valid banner section and upload an image']);
                }

                $count = (int) DB::scalar("SELECT COUNT(*) FROM `Gallery` WHERE category = 'BANNER' AND title = ?", [$section]);
                $last = DB::scalar("SELECT MAX(sortOrder) FROM `Gallery` WHERE category = 'BANNER' AND title = ?", [$section]);
                $limit = self::BANNER_SECTION_LIMITS[$section];

                if ($count >= $limit) {
                    Upload::discard($request);
                    return json_reply(400, [
                        'success' => false,
                        'message' => "This section already has the maximum of $limit banner" . ($limit === 1 ? '' : 's') . '. Delete one to add another.',
                    ]);
                }

                $sortOrder = ($last === null || $last === false ? -1 : (int) $last) + 1;
            }

            $now = Dates::now();
            $id = Crypto::cuid();
            $cleanTitle = is_string($title) ? trim($title) : '';
            $cleanDescription = is_string($description) ? trim($description) : '';

            DB::insert('Gallery', [
                'id' => $id,
                'title' => $cleanTitle !== '' ? $cleanTitle : $defaultTitle,
                'description' => $cleanDescription !== '' ? $cleanDescription : null,
                'category' => $category,
                'imageUrl' => $fileUrl,
                'resourceType' => $isVideo ? 'VIDEO' : 'IMAGE',
                'isActive' => true,
                'sortOrder' => $sortOrder,
                'createdAt' => $now,
                'updatedAt' => $now,
            ]);

            return json_reply(201, [
                'success' => true,
                'message' => 'Gallery item uploaded successfully',
                'gallery' => self::find($id),
            ]);
        } catch (Throwable $error) {
            Upload::discard($request);
            error_log((string) $error);
            return json_reply(500, ['success' => false, 'message' => self::safeMessage($error) ?: 'Unable to upload gallery item']);
        }
    }

    // GET /api/v1/gallery?category=&search=&resourceType=&page=&limit=
    // `search` looks in the title and description. With `page` or `limit` the list is paginated;
    // without them everything is returned. `count` is always the total number of matches.
    public static function index(Request $request): array
    {
        $category = $request->query['category'] ?? null;
        if ($category && !in_array($category, self::CATEGORIES, true)) {
            return self::categoryError();
        }

        try {
            $where = ['isActive = 1'];
            $params = [];
            if ($category) {
                $where[] = 'category = ?';
                $params[] = $category;
            }
            $search = is_string($request->query['search'] ?? null) ? trim($request->query['search']) : '';
            if ($search !== '') {
                $where[] = '(title LIKE ? OR description LIKE ?)';
                array_push($params, DB::like($search), DB::like($search));
            }
            $type = is_string($request->query['resourceType'] ?? null) ? strtoupper($request->query['resourceType']) : '';
            if (in_array($type, ['IMAGE', 'VIDEO'], true)) {
                $where[] = 'resourceType = ?';
                $params[] = $type;
            }
            $whereSql = 'WHERE ' . implode(' AND ', $where);
            $order = self::order($category);

            if (!isset($request->query['page']) && !isset($request->query['limit'])) {
                $gallery = DB::all("SELECT * FROM `Gallery` $whereSql ORDER BY $order", $params);

                return json_reply(200, ['success' => true, 'count' => count($gallery), 'gallery' => $gallery]);
            }

            $page = max((int) ($request->query['page'] ?? 1) ?: 1, 1);
            $limit = min(max((int) ($request->query['limit'] ?? 12) ?: 12, 1), 100);
            $total = (int) DB::scalar("SELECT COUNT(*) FROM `Gallery` $whereSql", $params);
            $gallery = DB::all("SELECT * FROM `Gallery` $whereSql ORDER BY $order LIMIT $limit OFFSET " . (($page - 1) * $limit), $params);

            return json_reply(200, [
                'success' => true,
                'count' => $total,
                'gallery' => $gallery,
                'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'totalPages' => max((int) ceil($total / $limit), 1)],
            ]);
        } catch (Throwable $error) {
            error_log((string) $error);
            return json_reply(500, ['success' => false, 'message' => 'Unable to fetch gallery']);
        }
    }

    // GET /api/v1/gallery/admin — hidden items included.
    public static function admin(Request $request): array
    {
        $category = $request->query['category'] ?? null;
        if ($category && !in_array($category, self::CATEGORIES, true)) {
            return self::categoryError();
        }

        try {
            $gallery = $category
                ? DB::all('SELECT * FROM `Gallery` WHERE category = ? ORDER BY ' . self::order($category), [$category])
                : DB::all('SELECT * FROM `Gallery` ORDER BY ' . self::order(null));

            return json_reply(200, ['success' => true, 'count' => count($gallery), 'gallery' => $gallery]);
        } catch (Throwable $error) {
            error_log((string) $error);
            return json_reply(500, ['success' => false, 'message' => 'Unable to fetch gallery']);
        }
    }

    // PATCH /api/v1/gallery/:id/visibility
    public static function visibility(Request $request): array
    {
        try {
            $isActive = $request->input('isActive');
            if (!is_bool($isActive)) {
                return json_reply(400, ['success' => false, 'message' => 'isActive must be true or false']);
            }

            $id = $request->params['id'];
            if (!self::find($id)) {
                return json_reply(404, ['success' => false, 'message' => 'Gallery item not found']);
            }

            DB::update('Gallery', ['isActive' => $isActive, 'updatedAt' => Dates::now()], 'id = ?', [$id]);

            return json_reply(200, [
                'success' => true,
                'message' => $isActive ? 'Gallery item is now visible' : 'Gallery item is now hidden',
                'gallery' => self::find($id),
            ]);
        } catch (Throwable $error) {
            error_log((string) $error);
            return json_reply(500, ['success' => false, 'message' => 'Unable to update gallery item']);
        }
    }

    // PATCH /api/v1/gallery/banners/reorder   body: { section, ids: [...] }
    public static function reorderBanners(Request $request): array
    {
        try {
            $section = $request->input('section');
            $ids = $request->input('ids');

            if (!self::isBannerSection($section) || !is_array($ids) || count($ids) === 0) {
                return json_reply(400, ['success' => false, 'message' => 'A valid section and ids are required']);
            }

            $existing = array_column(
                DB::all("SELECT id FROM `Gallery` WHERE category = 'BANNER' AND title = ?", [$section]),
                'id'
            );

            $sameSet = count($existing) === count($ids)
                && count(array_unique($ids, SORT_REGULAR)) === count($ids)
                && !array_diff($existing, $ids);

            if (!$sameSet) {
                return json_reply(400, [
                    'success' => false,
                    'message' => "The list does not match this section's banners. Refresh and try again.",
                ]);
            }

            DB::transaction(function () use ($ids) {
                $now = Dates::now();
                foreach (array_values($ids) as $index => $id) {
                    DB::update('Gallery', ['sortOrder' => $index, 'updatedAt' => $now], 'id = ?', [$id]);
                }
            });

            return json_reply(200, ['success' => true, 'message' => 'Banner order saved']);
        } catch (Throwable $error) {
            error_log((string) $error);
            return json_reply(500, ['success' => false, 'message' => 'Unable to save banner order']);
        }
    }

    // GET /api/v1/gallery/:id
    public static function show(Request $request): array
    {
        try {
            $gallery = self::find($request->params['id']);
            if (!$gallery || !$gallery['isActive']) {
                return json_reply(404, ['success' => false, 'message' => 'Gallery image not found']);
            }
            return json_reply(200, ['success' => true, 'gallery' => $gallery]);
        } catch (Throwable $error) {
            error_log((string) $error);
            return json_reply(500, ['success' => false, 'message' => 'Unable to fetch image']);
        }
    }

    // PATCH /api/v1/gallery/:id
    public static function update(Request $request): array
    {
        try {
            $id = $request->params['id'];
            $gallery = self::find($id);

            if (!$gallery) {
                return json_reply(404, ['success' => false, 'message' => 'Gallery image not found']);
            }

            $category = $request->input('category');
            if ($category && !in_array($category, self::CATEGORIES, true)) {
                return self::categoryError();
            }

            $data = [];

            if ($request->has('title')) {
                $data['title'] = trim((string) $request->input('title'));
            }
            if ($request->has('description')) {
                $description = trim((string) $request->input('description'));
                $data['description'] = $description !== '' ? $description : null;
            }
            if ($request->has('category')) {
                $data['category'] = $category;
            }

            if ($gallery['resourceType'] === 'VIDEO') {
                // Videos are links; a file upload is ignored.
                Upload::discard($request);

                if ($request->has('videoUrl')) {
                    $parsed = self::parseVideoUrl($request->input('videoUrl'));
                    if (!$parsed) {
                        return json_reply(400, ['success' => false, 'message' => 'A valid video URL (http or https) is required']);
                    }
                    $data['imageUrl'] = $parsed;
                }
            } elseif ($request->file) {
                if (!str_starts_with($request->file['mimetype'], 'image/')) {
                    Upload::discard($request);
                    return json_reply(400, ['success' => false, 'message' => 'Selected file is not an image']);
                }
                $data['imageUrl'] = Upload::publicPath($request->file);
            }

            if (!$data) {
                return json_reply(400, ['success' => false, 'message' => 'Nothing to update']);
            }

            DB::update('Gallery', $data + ['updatedAt' => Dates::now()], 'id = ?', [$id]);

            // Delete the old file (only for locally uploaded images).
            if (isset($data['imageUrl']) && $data['imageUrl'] !== $gallery['imageUrl']) {
                Upload::removeLocal($gallery['imageUrl']);
            }

            return json_reply(200, [
                'success' => true,
                'message' => 'Gallery item updated successfully',
                'gallery' => self::find($id),
            ]);
        } catch (Throwable $error) {
            Upload::discard($request);
            error_log((string) $error);
            return json_reply(500, ['success' => false, 'message' => self::safeMessage($error) ?: 'Unable to update gallery item']);
        }
    }

    // DELETE /api/v1/gallery/:id
    public static function destroy(Request $request): array
    {
        try {
            $id = $request->params['id'];
            $gallery = self::find($id);

            if (!$gallery) {
                return json_reply(404, ['success' => false, 'message' => 'Gallery image not found']);
            }

            Upload::removeLocal($gallery['imageUrl']);
            DB::run('DELETE FROM `Gallery` WHERE id = ?', [$id]);

            return json_reply(200, ['success' => true, 'message' => 'Gallery item deleted successfully']);
        } catch (Throwable $error) {
            error_log((string) $error);
            return json_reply(500, ['success' => false, 'message' => self::safeMessage($error) ?: 'Unable to delete gallery item']);
        }
    }
}
