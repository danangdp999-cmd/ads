<?php
// listings-create.php – simple JSON API untuk simpan listing

error_reporting(E_ALL);
ini_set('display_errors', '0'); // jangan kirim HTML error

require_once __DIR__ . '/config.php'; // di sini sudah ada json_response()

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// fallback kalau di config.php tidak ada json_response
if (!function_exists('json_response')) {
    function json_response($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        echo json_encode($data);
        exit;
    }
}

// koneksi DB
try {
    $pdo = db();
} catch (Exception $e) {
    json_response(array(
        'status'  => 'error',
        'message' => 'DB connect failed'
    ), 500);
}

// GET biasa: health check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(array(
        'status'  => 'ok',
        'message' => 'listings-create alive, send POST JSON',
        'db'      => DB_NAME,
    ), 200);
}

// Baca JSON body
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    json_response(array(
        'status'  => 'error',
        'message' => 'Invalid JSON body'
    ), 400);
}

// helper ambil field
function read_trimmed(array $source, string $key): ?string {
    if (!array_key_exists($key, $source)) {
        return null;
    }

    $value = trim((string) $source[$key]);
    return $value === '' ? null : $value;
}

function read_int(array $source, string $key, ?int $min = null): ?int {
    if (!array_key_exists($key, $source)) {
        return null;
    }

    $raw = trim((string) $source[$key]);
    if ($raw === '') {
        return null;
    }

    $value = (int) $raw;
    if ($min !== null && $value < $min) {
        $value = $min;
    }

    return $value;
}

function read_float(array $source, string $key, ?float $min = null): ?float {
    if (!array_key_exists($key, $source)) {
        return null;
    }

    $raw = trim((string) $source[$key]);
    if ($raw === '') {
        return null;
    }

    $value = (float) $raw;
    if ($min !== null && $value < $min) {
        $value = $min;
    }

    return $value;
}

$hostUserId = 0;
if (!empty($_SESSION['user_id'])) {
    $hostUserId = (int) $_SESSION['user_id'];
} elseif (!empty($data['host_user_id'])) {
    $hostUserId = (int) $data['host_user_id'];
} elseif (!empty($data['host_id'])) {
    $hostUserId = (int) $data['host_id'];
}

if ($hostUserId <= 0) {
    json_response(array(
        'status'        => 'error',
        'message'       => 'Authentication required. Please sign in again.',
        'require_login' => true,
    ), 401);
}

$hostType = read_trimmed($data, 'host_type');
$title    = read_trimmed($data, 'title');

if ($hostType === null || $title === null) {
    json_response(array(
        'status'  => 'error',
        'message' => 'host_type and title are required fields.'
    ), 400);
}

$description   = read_trimmed($data, 'description');
$propertyType  = read_trimmed($data, 'property_type');
$roomType      = read_trimmed($data, 'place_type');
if ($roomType === null) {
    $roomType = read_trimmed($data, 'room_type');
}
$country       = read_trimmed($data, 'country');
$city          = read_trimmed($data, 'city');
$addressLine   = read_trimmed($data, 'street');
if ($addressLine === null) {
    $addressLine = read_trimmed($data, 'address_line1');
}
$bedrooms      = read_int($data, 'bedrooms', 0);
$bathrooms     = read_float($data, 'bathrooms', 0);
$nightlyPrice  = read_float($data, 'price_nightly', 0);
$nightlyStrike = read_float($data, 'nightly_price_strike', 0);
$weekendPrice  = read_float($data, 'weekend_price', 0);
$weekendStrike = read_float($data, 'weekend_price_strike', 0);
$hasDiscount   = !empty($data['has_discount']) ? 1 : 0;
$discountLabel = read_trimmed($data, 'discount_label');
$status        = read_trimmed($data, 'status') ?? 'draft';
$guests        = read_int($data, 'max_guests', 1) ?? 1;
$currencyCode  = read_trimmed($data, 'currency_code');
$currencyCode  = $currencyCode !== null ? strtoupper(substr($currencyCode, 0, 3)) : 'IDR';
if ($currencyCode === '') {
    $currencyCode = 'IDR';
}

try {
    $columns = array();
    $placeholders = array();
    $params = array();

    $addParam = function (string $column, $value) use (&$columns, &$placeholders, &$params) {
        $columns[] = $column;
        $placeholders[] = ':' . $column;
        $params[':' . $column] = $value;
    };

    $addParam('host_user_id', $hostUserId);
    $addParam('host_type', $hostType);
    $addParam('title', $title);
    $addParam('status', $status);
    $addParam('guests', $guests);
    $addParam('currency_code', $currencyCode);
    $addParam('has_discount', $hasDiscount);

    if ($description !== null) {
        $addParam('description', $description);
    }
    if ($propertyType !== null) {
        $addParam('property_type', $propertyType);
    }
    if ($roomType !== null) {
        $addParam('room_type', $roomType);
    }
    if ($country !== null) {
        $addParam('country', $country);
    }
    if ($city !== null) {
        $addParam('city', $city);
    }
    if ($addressLine !== null) {
        $addParam('address_line1', $addressLine);
    }
    if ($bedrooms !== null) {
        $addParam('bedrooms', $bedrooms);
    }
    if ($bathrooms !== null) {
        $addParam('bathrooms', $bathrooms);
    }
    if ($nightlyPrice !== null) {
        $addParam('nightly_price', $nightlyPrice);
    }
    if ($nightlyStrike !== null) {
        $addParam('nightly_price_strike', $nightlyStrike);
    }
    if ($weekendPrice !== null) {
        $addParam('weekend_price', $weekendPrice);
    }
    if ($weekendStrike !== null) {
        $addParam('weekend_price_strike', $weekendStrike);
    }
    if ($discountLabel !== null) {
        $addParam('discount_label', $discountLabel);
    }

    $sql = sprintf(
        'INSERT INTO simple_listings (%s) VALUES (%s)',
        implode(', ', $columns),
        implode(', ', $placeholders)
    );

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $id = (int) $pdo->lastInsertId();

    json_response(array(
        'status'     => 'ok',
        'listing_id' => $id
    ), 200);

} catch (Exception $e) {
    json_response(array(
        'status'  => 'error',
        'message' => 'DB insert error'
    ), 500);
}
