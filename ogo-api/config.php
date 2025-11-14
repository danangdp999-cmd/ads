<?php
// ==========================
//  OGORooms API config
// ==========================

// DB credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'bukc2791_ogofix');
define('DB_USER', 'bukc2791_ogofix');
define('DB_PASS', 'Domain21.');

// simple PDO singleton
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $pdo;
}

// JSON helper
function json_response(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data);
    exit;
}

// baca body (JSON / form)
function read_request_data(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);

    if (is_array($data)) {
        return $data;
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    if (!empty($_GET)) {
        return $_GET;
    }

    return [];
}
