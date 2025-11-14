<?php
// listings-get.php?id=1 — fetch detailed listing info for public or owner view

session_start();

require_once __DIR__ . '/config.php';

if (!function_exists('json_response')) {
    function json_response($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        echo json_encode($data);
        exit;
    }
}

function ogo_normalize_local_path(string $value): string
{
    $normalized = str_replace('\\', '/', $value);
    $normalized = preg_replace('#/{2,}#', '/', $normalized);

    while (strpos($normalized, '../') === 0) {
        $normalized = substr($normalized, 3);
    }

    return ltrim($normalized, './');
}

function ogo_encode_path_segments(string $path): string
{
    $segments = explode('/', $path);
    $encoded  = [];

    foreach ($segments as $segment) {
        if ($segment === '') {
            $encoded[] = '';
            continue;
        }

        $decoded    = rawurldecode($segment);
        $encoded[]  = rawurlencode($decoded);
    }

    return implode('/', $encoded);
}

function ogo_public_path(string $value): string
{
    $trimmed = ltrim($value, '/');
    $encoded = ogo_encode_path_segments($trimmed);

    if ($encoded === '') {
        return '/';
    }

    return '/' . $encoded;
}

function ogo_cover_photo_url(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $trimmed = trim((string) $value);
    if ($trimmed === '') {
        return null;
    }

    if (preg_match('#^(https?:)?//#i', $trimmed)) {
        return $trimmed;
    }

    $normalized = ogo_normalize_local_path($trimmed);
    if ($normalized === '') {
        return null;
    }

    $full = dirname(__DIR__) . '/' . ltrim($normalized, '/');
    if (is_file($full)) {
        return ogo_public_path($normalized);
    }

    if (strpos($normalized, 'listing-photos/') === 0) {
        return ogo_public_path($normalized);
    }

    return null;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    json_response([
        'status'  => 'error',
        'message' => 'Invalid listing id'
    ], 400);
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Exception $e) {
    json_response([
        'status'  => 'error',
        'message' => 'DB connect failed'
    ], 500);
}

$sql = "SELECT l.id,
               l.host_id,
               l.host_user_id,
               l.host_type,
               l.title,
               l.description,
               l.headline,
               l.story,
               l.property_type,
               l.room_type,
               l.city,
               l.country,
               l.address_line1,
               l.bedrooms,
               l.bathrooms,
               l.guests,
               l.nightly_price,
               l.currency_code,
               l.cover_photo_url,
               l.highlights_json,
               l.amenities_json,
               l.house_rules_json,
               l.custom_rules,
               l.checkin_window,
               l.checkout_time,
               l.welcome_message,
               l.cancellation_policy,
               l.status,
               l.created_at,
               l.updated_at,
               l.approved_at,
               l.rejected_reason,
               u.email AS host_email,
               u.name  AS host_name
        FROM simple_listings l
        LEFT JOIN ogo_users u ON u.id = l.host_user_id
        WHERE l.id = :id
        LIMIT 1";

try {
    $stm = $pdo->prepare($sql);
    $stm->execute([':id' => $id]);
    $row = $stm->fetch();

    if (!$row) {
        json_response([
            'status'  => 'error',
            'message' => 'Listing not found'
        ], 404);
    }

    $status   = $row['status'] ?? 'draft';
    $userId   = $_SESSION['user_id']   ?? 0;
    $userRole = $_SESSION['user_role'] ?? '';

    $ownerUserId = (int) ($row['host_user_id'] ?? 0);
    $ownerHostId = (int) ($row['host_id'] ?? 0);
    $isOwner     = $userId && ($userId === $ownerUserId || $userId === $ownerHostId);
    $isAdmin     = in_array($userRole, ['admin', 'super_admin'], true);

    if ($status !== 'published' && !$isOwner && !$isAdmin) {
        json_response([
            'status'  => 'error',
            'message' => 'Listing is not published yet',
        ], 403);
    }

    $highlightLabels = [
        'great_location' => 'Great location',
        'city_view'      => 'City skyline view',
        'fast_wifi'      => 'Fast Wi-Fi',
        'self_check_in'  => 'Self check-in',
        'workspace'      => 'Dedicated workspace',
        'parking'        => 'Free parking',
        'pet_friendly'   => 'Pet friendly',
        'long_stays'     => 'Great for long stays',
    ];

    $houseRuleLabels = [
        'no_smoking'       => 'No smoking',
        'no_pets'          => 'No pets',
        'no_parties'       => 'No parties or events',
        'quiet_hours'      => 'Quiet hours after 10 PM',
        'suitable_children'=> 'Suitable for children',
    ];

    $highlights = [];
    if (!empty($row['highlights_json'])) {
        $decoded = json_decode($row['highlights_json'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $code) {
                if (!is_string($code)) {
                    continue;
                }
                $highlights[] = $highlightLabels[$code] ?? $code;
            }
        }
    }

    $amenities = [];
    if (!empty($row['amenities_json'])) {
        $decoded = json_decode($row['amenities_json'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $code) {
                if (is_string($code)) {
                    $amenities[] = $code;
                }
            }
        }
    }

    $houseRules = [];
    if (!empty($row['house_rules_json'])) {
        $decoded = json_decode($row['house_rules_json'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $code) {
                if (!is_string($code)) {
                    continue;
                }
                $houseRules[] = $houseRuleLabels[$code] ?? $code;
            }
        }
    }

    $listing = [
        'id'                => (int) $row['id'],
        'host_id'           => $ownerHostId ?: null,
        'host_user_id'      => $ownerUserId ?: null,
        'host_type'         => $row['host_type'] ?? 'home',
        'title'             => $row['title'],
        'headline'          => $row['headline'],
        'story'             => $row['story'],
        'description'       => $row['description'],
        'property_type'     => $row['property_type'],
        'room_type'         => $row['room_type'],
        'city'              => $row['city'],
        'country'           => $row['country'],
        'address_line1'     => $row['address_line1'],
        'bedrooms'          => $row['bedrooms'] !== null ? (int) $row['bedrooms'] : null,
        'bathrooms'         => $row['bathrooms'] !== null ? (int) $row['bathrooms'] : null,
        'guests'            => $row['guests'] !== null ? (int) $row['guests'] : null,
        'nightly_price'     => $row['nightly_price'] !== null ? (float) $row['nightly_price'] : null,
        'currency_code'     => $row['currency_code'] ?: 'IDR',
        'cover_photo_url'   => ogo_cover_photo_url($row['cover_photo_url']) ?: null,
        'highlights'        => $highlights,
        'amenities'         => $amenities,
        'house_rules'       => $houseRules,
        'custom_rules'      => $row['custom_rules'],
        'checkin_window'    => $row['checkin_window'],
        'checkout_time'     => $row['checkout_time'],
        'welcome_message'   => $row['welcome_message'],
        'cancellation_policy'=> $row['cancellation_policy'],
        'status'            => $status,
        'created_at'        => $row['created_at'],
        'approved_at'       => $row['approved_at'],
        'rejected_reason'   => $row['rejected_reason'],
        'host_email'        => $row['host_email'],
        'host_name'         => $row['host_name'],
        'is_owner'          => $isOwner,
        'can_edit'          => $isOwner || $isAdmin,
    ];

    json_response([
        'status'  => 'ok',
        'listing' => $listing,
    ]);
} catch (Exception $e) {
    json_response([
        'status'  => 'error',
        'message' => 'DB query error'
    ], 500);
}

