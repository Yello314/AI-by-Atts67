<?php
require_once __DIR__ . '/config.php';

function requireLogin() {
    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function logActivity($action, $details = []) {
    $logFile = __DIR__ . '/activity.log';
    $entry = date('Y-m-d H:i:s') . " | $action | " . json_encode($details) . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND);
}
