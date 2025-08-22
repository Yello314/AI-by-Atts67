<?php
require_once __DIR__ . '/../config.php';
requireAdmin();
$pdo = db();


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
        exit;
    }


    $ct = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') === false) {
        throw new RuntimeException('Content-Type must be application/json');
    }

    $nowMs = (int) floor(microtime(true) * 1000);
    $lastMs = (int) ($_SESSION['_last_log_ms'] ?? 0);
    if ($nowMs - $lastMs < 200) {
        throw new RuntimeException('Too many requests');
    }
    $_SESSION['_last_log_ms'] = $nowMs;

    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        throw new RuntimeException('Empty payload');
    }
    $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

    $action  = trim((string)($payload['action'] ?? ''));
    $details = $payload['details'] ?? [];

    if ($action === '') {
        throw new RuntimeException('Missing action');
    }
    if (!preg_match('/^[A-Za-z0-9._-]{1,64}$/', $action)) {
        throw new RuntimeException('Invalid action format');
    }

    if (!is_array($details)) {
        $details = ['value' => (string)$details];
    }

    $context = [
        '_page'     => $_SERVER['HTTP_X_REQUESTED_PAGE'] ?? ($_SERVER['HTTP_REFERER'] ?? ''),
        '_ip'       => $_SERVER['REMOTE_ADDR'] ?? '',
        '_ua'       => $_SERVER['HTTP_USER_AGENT'] ?? '',
        '_time'     => date('c'),
    ];
    $merged = array_merge($details, $context);

    $json = json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Failed to encode details: ' . json_last_error_msg());
    }
    if (strlen($json) > 65000) { 
        $json = substr($json, 0, 65000);
    }

    logAction($action, $json);

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
