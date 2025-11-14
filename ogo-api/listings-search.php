<?php
// listings-search.php — public search endpoint with filters and sorting

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

$rawWhere = isset($_GET['where']) ? trim((string)$_GET['where']) : '';
$sort     = isset($_GET['sort']) ? strtolower(trim((string)$_GET['sort'])) : 'recommended';

$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;

$lat = isset($_GET['lat']) && $_GET['lat'] !== '' ? (float)$_GET['lat'] : null;
$lng = isset($_GET['lng']) && $_GET['lng'] !== '' ? (float)$_GET['lng'] : null;
$guestFilter = isset($_GET['guests']) && $_GET['guests'] !== '' ? (int) $_GET['guests'] : null;
if ($guestFilter !== null && $guestFilter <= 0) {
    $guestFilter = null;
}

if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
    [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
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
        'message' => 'DB connect failed',
    ], 500);
}

$params = [];
$conditions = ['status = "published"'];

if ($rawWhere !== '') {
    $conditions[] = '('
        . 'city LIKE :where'
        . ' OR country LIKE :where'
        . ' OR location_city LIKE :where'
        . ' OR location_country LIKE :where'
        . ' OR title LIKE :where'
        . ')';
    $params[':where'] = '%' . $rawWhere . '%';
}
if ($minPrice !== null) {
    $conditions[] = 'nightly_price >= :min_price';
    $params[':min_price'] = $minPrice;
}
if ($maxPrice !== null) {
    $conditions[] = 'nightly_price <= :max_price';
    $params[':max_price'] = $maxPrice;
}
if ($guestFilter !== null) {
    $conditions[] = 'guests >= :guest_count';
    $params[':guest_count'] = $guestFilter;
}

$sql = 'SELECT id,
               host_user_id,
               title,
               city,
               country,
               location_city,
               location_country,
               nightly_price,
               nightly_price_strike,
               has_discount,
               discount_label,
               property_type,
               room_type,
               guests,
               lat,
               lng,
               cover_photo_url,
               approved_at,
               created_at
        FROM simple_listings';

if (!empty($conditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY COALESCE(approved_at, created_at) DESC LIMIT 120';

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    json_response([
        'status'  => 'error',
        'message' => 'Query failed',
    ], 500);
}

$results = [];
foreach ($rows as $row) {
    $price = isset($row['nightly_price']) ? (float)$row['nightly_price'] : null;
    $priceStrike = isset($row['nightly_price_strike']) ? (float)$row['nightly_price_strike'] : null;
    $guests = isset($row['guests']) ? (int)$row['guests'] : null;
    $latValue = isset($row['lat']) ? (float)$row['lat'] : null;
    $lngValue = isset($row['lng']) ? (float)$row['lng'] : null;

    $distanceKm = null;
    if ($lat !== null && $lng !== null && $latValue !== null && $lngValue !== null) {
        $distanceKm = haversine_distance($lat, $lng, $latValue, $lngValue);
    }

    $cityValue    = $row['city'] ?? null;
    $countryValue = $row['country'] ?? null;
    $fallbackCity = $row['location_city'] ?? null;
    $fallbackCountry = $row['location_country'] ?? null;

    $results[] = [
        'id'               => (int)$row['id'],
        'host_user_id'     => (int)$row['host_user_id'],
        'title'            => $row['title'],
        'city'             => ($cityValue !== null && $cityValue !== '') ? $cityValue : $fallbackCity,
        'country'          => ($countryValue !== null && $countryValue !== '') ? $countryValue : $fallbackCountry,
        'price_nightly'    => $price,
        'nightly_price'    => $price,
        'nightly_price_strike' => $priceStrike,
        'has_discount'     => !empty($row['has_discount']),
        'discount_label'   => $row['discount_label'],
        'property_type'    => $row['property_type'],
        'room_type'        => $row['room_type'],
        'guests'           => $guests,
        'lat'              => $latValue,
        'lng'              => $lngValue,
        'cover_photo_url'  => ogo_cover_photo_url($row['cover_photo_url']) ?: null,
        'approved_at'      => $row['approved_at'],
        'created_at'       => $row['created_at'],
        'distance_km'      => $distanceKm,
    ];
}

if ($lat !== null && $lng !== null) {
    foreach ($results as &$item) {
        if ($item['distance_km'] !== null) {
            $item['distance_km'] = round($item['distance_km'], 2);
        }
    }
    unset($item);
}

$sortKey = match ($sort) {
    'price_high', 'harga_tertinggi', 'highest' => 'price_high',
    'price_low', 'harga_terendah', 'lowest'    => 'price_low',
    'nearest', 'terdekat'                      => 'nearest',
    'best', 'terbaik'                          => 'best',
    default                                    => 'recommended',
};

usort($results, function (array $a, array $b) use ($sortKey, $lat, $lng) {
    switch ($sortKey) {
        case 'price_high':
            return ($b['price_nightly'] <=> $a['price_nightly']);
        case 'price_low':
            return ($a['price_nightly'] <=> $b['price_nightly']);
        case 'nearest':
            $distA = $a['distance_km'] ?? INF;
            $distB = $b['distance_km'] ?? INF;
            return $distA <=> $distB;
        case 'best':
            $discountCompare = ($b['has_discount'] <=> $a['has_discount']);
            if ($discountCompare !== 0) {
                return $discountCompare;
            }
            return ($a['price_nightly'] <=> $b['price_nightly']);
        case 'recommended':
        default:
            $discountCompare = ($b['has_discount'] <=> $a['has_discount']);
            if ($discountCompare !== 0) {
                return $discountCompare;
            }
            return ($a['price_nightly'] <=> $b['price_nightly']);
    }
});

$results = array_slice($results, 0, 60);

json_response([
    'status'   => 'ok',
    'listings' => $results,
]);

function haversine_distance(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earthRadius = 6371; // kilometers

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}
