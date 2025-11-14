<?php
// OGORooms simple API (auth + listings basic)

require __DIR__ . '/config.php';

error_reporting(E_ALL);
ini_set('display_errors', '0'); // biar output tetap JSON

$action = $_GET['action'] ?? 'ping';

try {
    switch ($action) {
        case 'ping':
            api_ping();
            break;

        case 'db-check':
            api_db_check();
            break;

        case 'register':
            api_register();
            break;

        case 'login':
            api_login();
            break;

        case 'me':
            api_me();
            break;

        case 'listings-create-basic':
            api_listings_create_basic();
            break;

        case 'host-listings':
            api_host_listings();
            break;

        default:
            json_response([
                'status'  => 'error',
                'message' => 'Unknown action: ' . $action,
            ], 404);
    }
} catch (Throwable $e) {
    // log ke error_log cPanel
    error_log('[OGO_API_FATAL] ' . $e->getMessage() . ' in ' .
        $e->getFile() . ':' . $e->getLine());

    json_response([
        'status'  => 'error',
        'message' => 'Server internal error',
        'error'   => $e->getMessage(), // sementara buka biar gampang debug
    ], 500);
}

// ---------- HANDLERS ----------

function api_ping(): void
{
    json_response([
        'status'  => 'ok',
        'app'     => 'OGORooms API',
        'version' => 'core-v1',
        'db'      => DB_NAME,
        'time'    => date('c'),
    ]);
}

function api_db_check(): void
{
    $pdo = db();
    $stmt = $pdo->query('SELECT COUNT(*) AS total_users FROM ogo_users');
    $row  = $stmt->fetch();

    json_response([
        'status'       => 'ok',
        'db_connected' => true,
        'total_users'  => (int)($row['total_users'] ?? 0),
    ]);
}

// REGISTER
function api_register(): void
{
    $data     = read_request_data();
    $email    = trim($data['email'] ?? '');
    $password = (string)($data['password'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['status' => 'error', 'message' => 'Invalid email'], 400);
    }
    if (strlen($password) < 6) {
        json_response(['status' => 'error', 'message' => 'Password too short'], 400);
    }

    $pdo = db();

    // cek duplikat
    $stmt = $pdo->prepare('SELECT id FROM ogo_users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_response([
            'status'  => 'error',
            'message' => 'Email already registered',
        ], 400);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO ogo_users (email, password_hash, role)
         VALUES (?, ?, ?)'
    );
    $stmt->execute([$email, $hash, 'guest']);

    $id = (int)$pdo->lastInsertId();

    json_response([
        'status' => 'ok',
        'user'   => [
            'id'    => $id,
            'email' => $email,
            'role'  => 'guest',
        ],
    ]);
}

// LOGIN
function api_login(): void
{
    $data     = read_request_data();
    $email    = trim($data['email'] ?? '');
    $password = (string)($data['password'] ?? '');

    if ($email === '' || $password === '') {
        json_response(['status' => 'error', 'message' => 'Email and password required'], 400);
    }

    $pdo = db();

    $stmt = $pdo->prepare(
        'SELECT id, email, password_hash, role
         FROM ogo_users WHERE email = ? LIMIT 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        json_response([
            'status'  => 'error',
            'message' => 'Invalid email or password',
        ], 401);
    }

    // update last_login_at
    $up = $pdo->prepare('UPDATE ogo_users SET last_login_at = NOW() WHERE id = ?');
    $up->execute([$user['id']]);

    json_response([
        'status' => 'ok',
        'user'   => [
            'id'    => (int)$user['id'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ],
    ]);
}

// ME (pakai ?email= )
function api_me(): void
{
    $email = trim($_GET['email'] ?? '');

    if ($email === '') {
        json_response(['status' => 'error', 'message' => 'Email is required'], 400);
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT id, email, role, created_at, last_login_at
         FROM ogo_users WHERE email = ? LIMIT 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        json_response(['status' => 'error', 'message' => 'User not found'], 404);
    }

    json_response([
        'status' => 'ok',
        'user'   => [
            'id'            => (int)$user['id'],
            'email'         => $user['email'],
            'role'          => $user['role'],
            'created_at'    => $user['created_at'],
            'last_login_at' => $user['last_login_at'],
        ],
    ]);
}

// CREATE LISTING BASIC
function api_listings_create_basic(): void
{
    $data = read_request_data();

    $hostId  = isset($data['host_id']) ? (int)$data['host_id'] : 0;
    $type    = trim((string)($data['host_type'] ?? ''));
    $title   = trim((string)($data['title'] ?? ''));
    $description = trim((string)($data['description'] ?? ''));
    $propertyType = trim((string)($data['property_type'] ?? ''));
    $roomType     = trim((string)($data['room_type'] ?? ($data['place_type'] ?? '')));

    $city    = trim((string)($data['city'] ?? ($data['location_city'] ?? '')));
    $country = trim((string)($data['country'] ?? ($data['location_country'] ?? '')));

    $guests = null;
    if (isset($data['guests'])) {
        $guests = (int)$data['guests'];
    } elseif (isset($data['max_guests'])) {
        $guests = (int)$data['max_guests'];
    }
    $bedrooms  = array_key_exists('bedrooms', $data) ? (int)$data['bedrooms'] : null;
    $bathrooms = array_key_exists('bathrooms', $data) ? (int)$data['bathrooms'] : null;

    $nightly = null;
    if (isset($data['nightly_price'])) {
        $nightly = (float)$data['nightly_price'];
    } elseif (isset($data['price_nightly'])) {
        $nightly = (float)$data['price_nightly'];
    }

    $nightlyStrike = null;
    if (isset($data['nightly_price_strike'])) {
        $nightlyStrike = (float)$data['nightly_price_strike'];
    } elseif (isset($data['price_nightly_original'])) {
        $nightlyStrike = (float)$data['price_nightly_original'];
    }

    $weekend = null;
    if (isset($data['weekend_price'])) {
        $weekend = (float)$data['weekend_price'];
    }

    $weekendStrike = null;
    if (isset($data['weekend_price_strike'])) {
        $weekendStrike = (float)$data['weekend_price_strike'];
    } elseif (isset($data['weekend_price_original'])) {
        $weekendStrike = (float)$data['weekend_price_original'];
    }

    $hasDiscount = !empty($data['has_discount']) ? 1 : 0;
    if (!$hasDiscount) {
        if ($nightly !== null && $nightlyStrike !== null && $nightlyStrike > $nightly) {
            $hasDiscount = 1;
        }
        if ($weekend !== null && $weekendStrike !== null && $weekendStrike > $weekend) {
            $hasDiscount = 1;
        }
    }

    $discountLabel = isset($data['discount_label']) ? trim((string)$data['discount_label']) : null;
    if ($discountLabel === '') {
        $discountLabel = null;
    }
    if ($discountLabel !== null) {
        $discountLabel = function_exists('mb_substr')
            ? mb_substr($discountLabel, 0, 50)
            : substr($discountLabel, 0, 50);
    }

    $status = strtolower((string)($data['status'] ?? 'draft'));
    $allowedStatus = ['draft', 'in_review', 'published', 'rejected'];
    if (!in_array($status, $allowedStatus, true)) {
        $status = 'draft';
    }

    $allowedTypes = ['home', 'experience', 'service'];
    if ($hostId <= 0 || $type === '' || !in_array($type, $allowedTypes, true)) {
        json_response(['status' => 'error', 'message' => 'Valid host_id and host_type required'], 400);
    }
    if ($title === '') {
        json_response(['status' => 'error', 'message' => 'Title is required'], 400);
    }
    if ($guests !== null && $guests < 0) {
        $guests = 0;
    }
    if ($bedrooms !== null && $bedrooms < 0) {
        $bedrooms = 0;
    }
    if ($bathrooms !== null && $bathrooms < 0) {
        $bathrooms = 0;
    }
    if ($nightly !== null && $nightly < 0) {
        $nightly = null;
    }
    if ($nightlyStrike !== null && $nightlyStrike < 0) {
        $nightlyStrike = null;
    }
    if ($weekend !== null && $weekend < 0) {
        $weekend = null;
    }
    if ($weekendStrike !== null && $weekendStrike < 0) {
        $weekendStrike = null;
    }

    $pdo = db();

    $chk = $pdo->prepare('SELECT id FROM ogo_users WHERE id = ? LIMIT 1');
    $chk->execute([$hostId]);
    if (!$chk->fetchColumn()) {
        json_response(['status' => 'error', 'message' => 'Host not found'], 404);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO simple_listings
         (host_id, host_user_id, host_type, title, description, property_type, room_type,
          city, country, location_city, location_country,
          bedrooms, bathrooms, guests,
          nightly_price, nightly_price_strike,
          weekend_price, weekend_price_strike,
          has_discount, discount_label, status,
          created_at, updated_at)
         VALUES
         (:host_id, :host_user_id, :host_type, :title, :description, :property_type, :room_type,
          :city, :country, :location_city, :location_country,
          :bedrooms, :bathrooms, :guests,
          :nightly_price, :nightly_price_strike,
          :weekend_price, :weekend_price_strike,
          :has_discount, :discount_label, :status,
          NOW(), NOW())'
    );

    $stmt->execute([
        ':host_id'                => $hostId,
        ':host_user_id'           => $hostId,
        ':host_type'              => $type,
        ':title'                  => $title,
        ':description'            => $description !== '' ? $description : null,
        ':property_type'          => $propertyType !== '' ? $propertyType : null,
        ':room_type'              => $roomType !== '' ? $roomType : null,
        ':city'                   => $city !== '' ? $city : null,
        ':country'                => $country !== '' ? $country : null,
        ':location_city'          => $city !== '' ? $city : null,
        ':location_country'       => $country !== '' ? $country : null,
        ':bedrooms'               => $bedrooms,
        ':bathrooms'              => $bathrooms,
        ':guests'                 => $guests,
        ':nightly_price'          => $nightly,
        ':nightly_price_strike'   => $nightlyStrike,
        ':weekend_price'          => $weekend,
        ':weekend_price_strike'   => $weekendStrike,
        ':has_discount'           => $hasDiscount,
        ':discount_label'         => $discountLabel,
        ':status'                 => $status,
    ]);

    $id = (int)$pdo->lastInsertId();

    json_response([
        'status'     => 'ok',
        'listing_id' => $id,
        'listing'    => [
            'id'        => $id,
            'host_id'   => $hostId,
            'host_type' => $type,
            'status'    => $status,
        ],
    ]);
}

// LISTING LIST HOST
function api_host_listings(): void
{
    $hostId = isset($_GET['host_id']) ? (int)$_GET['host_id'] : 0;

    if ($hostId <= 0) {
        json_response(['status' => 'error', 'message' => 'host_id is required'], 400);
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT id, host_type, title, city, country,
                nightly_price, nightly_price_strike,
                weekend_price, weekend_price_strike,
                has_discount, discount_label, status, created_at
         FROM simple_listings
         WHERE host_user_id = ?
         ORDER BY created_at DESC'
    );
    $stmt->execute([$hostId]);
    $rows = $stmt->fetchAll();

    json_response([
        'status'   => 'ok',
        'listings' => $rows,
    ]);
}
