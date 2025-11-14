<?php
// admin-listing-action.php
session_start();
require_once __DIR__.'/ogo-api/config.php';

$uid   = $_SESSION['user_id']   ?? 0;
$role  = $_SESSION['user_role'] ?? '';
if (!$uid || !in_array($role, ['admin','super_admin'], true)) {
  http_response_code(403); echo 'Forbidden'; exit;
}
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
  http_response_code(400); echo 'Bad CSRF'; exit;
}

$listingId = (int)($_POST['listing_id'] ?? 0);
$action    = $_POST['action'] ?? '';
$reason    = trim($_POST['reason'] ?? '');

if ($listingId <= 0 || !in_array($action, ['approve','reject'], true)) {
  http_response_code(400); echo 'Bad request'; exit;
}

try {
  $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  ]);

  if ($action === 'approve') {
    $sql = "UPDATE simple_listings
            SET status='published', approved_by=:uid, approved_at=NOW(), rejected_reason=NULL
            WHERE id=:id";
    $stm = $pdo->prepare($sql);
    $stm->execute([':uid'=>$uid, ':id'=>$listingId]);

  } else { // reject
    $sql = "UPDATE simple_listings
            SET status='rejected', approved_by=:uid, approved_at=NOW(), rejected_reason=:r
            WHERE id=:id";
    $stm = $pdo->prepare($sql);
    $stm->execute([':uid'=>$uid, ':id'=>$listingId, ':r'=>$reason ?: '(no reason)']);
  }

  header('Location: admin-listings.php?status=in_review');
  exit;

} catch(Exception $e){
  http_response_code(500); echo 'DB error'; exit;
}
