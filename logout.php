<?php
// logout.php — simple logout

session_start();
$_SESSION = [];
session_unset();
session_destroy();

header('Location: /index.php'); // atau /index.php kalau kamu pakai itu
exit;
