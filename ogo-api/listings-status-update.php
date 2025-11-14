<?php
// listings-status-update.php – update status + review_note saja

error_reporting(E_ALL);
ini_set('display_errors', '0');

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

// koneksi DB
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

// GET = health check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(array(
        'status'  => 'ok',
        'message' => 'listings-status-update alive, POST JSON',
        'db'      => DB_NAME
    ));
}

// baca JSON body
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    json_response(array(
        'status'  => 'error',
        'message' => 'Invalid JSON body'
    ), 400);
}

function g($key, $default = '') {
    global $data;
    if (isset($data[$key])) {
        return trim((string)$data[$key]);
    }
    return $default;
}

$id     = isset($data['id']) ? (int)$data['id'] : 0;
$status = g('status', 'draft');
$note   = g('review_note', '');

if ($id <= 0) {
    json_response(array(
        'status'  => 'error',
        'message' => 'Invalid listing id'
    ), 400);
}

$allowed = array('draft','in_review','published','rejected');
if (!in_array($status, $allowed, true)) {
    json_response(array(
        'status'  => 'error',
        'message' => 'Invalid status value'
    ), 400);
}

try {
    $stmt = $pdo->prepare(
        "UPDATE simple_listings
         SET status = :status,
             review_note = :note
         WHERE id = :id"
    );
    $stmt->execute(array(
        ':status' => $status,
        ':note'   => $note,
        ':id'     => $id,
    ));

    json_response(array(
        'status'  => 'ok',
        'message' => 'Status updated'
    ));
} catch (Exception $e) {
    json_response(array(
        'status'  => 'error',
        'message' => 'DB update error'
    ), 500);
}
