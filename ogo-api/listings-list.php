<?php
// listings-list.php – ambil daftar simple_listings

require_once __DIR__ . '/config.php';

if (!function_exists('json_response')) {
    function json_response($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        echo json_encode($data);
        exit;
    }
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        )
    );
} catch (Exception $e) {
    json_response(array(
        'status'  => 'error',
        'message' => 'DB connect failed'
    ), 500);
}

try {
    $stmt = $pdo->query(
    "SELECT id, host_type, property_type, place_type,
            title, country, city, price_nightly, created_at,
            IFNULL(status,'draft') AS status
     FROM simple_listings
     ORDER BY id DESC
     LIMIT 50"
    );

    $rows = $stmt->fetchAll();

    json_response(array(
        'status'   => 'ok',
        'listings' => $rows
    ));
} catch (Exception $e) {
    json_response(array(
        'status'  => 'error',
        'message' => 'DB query error'
    ), 500);
}
