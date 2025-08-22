<?php
require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    $uid = (int)($_SESSION['user_id'] ?? 0);
    logActivity($uid, 'logout', 'User logged out');
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
    session_regenerate_id(true);
}

safeRedirect(pathInBase('/login.php'));
