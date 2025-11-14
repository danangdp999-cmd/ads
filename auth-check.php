<?php
// auth-check.php — simple session check

session_start();

if (empty($_SESSION['user_id'])) {
    // belum login -> lempar ke login
    header('Location: /login.php');
    exit;
}

$currentUserId    = (int)$_SESSION['user_id'];
$currentUserEmail = $_SESSION['user_email'] ?? '';
$currentUserRole  = $_SESSION['user_role'] ?? 'guest';
