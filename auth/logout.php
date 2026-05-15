<?php
// auth/logout.php
require_once '../includes/config.php';
initSession();
session_unset();
session_destroy();
// Clear cookie
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
header('Location: ../index.php');
exit;
