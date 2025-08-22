<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'exam_system');   
define('DB_USER', 'root');          
define('DB_PASS', '');              
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'ระบบออกข้อสอบ By ATTS67');
date_default_timezone_set('Asia/Bangkok');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        die('DB connect error: ' . $e->getMessage());
    }
    return $pdo;
}


function getDBConnection(): PDO { return db(); }

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['username']);
}

function isAdmin(): bool {
    return !empty($_SESSION['role']) && strtolower((string)$_SESSION['role']) === 'admin';
}


function requireLogin(): void {
    if (!isLoggedIn()) {
        $next = $_SERVER['REQUEST_URI'] ?? '/index.php';
        header('Location: login.php?next=' . urlencode($next));
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        echo "<h1>403 Forbidden</h1><p>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</p>";
        exit;
    }
}

function pathInBase(string $path = '/'): string {
    $base = rtrim(str_replace('\\','/', dirname($_SERVER['PHP_SELF'] ?? '/')), '/');
    return $base . '/' . ltrim($path, '/');
}

function safeRedirect(string $url, int $code = 302): void {
    if (preg_match('~^https?://~i', $url)) {
        $sameHost = (parse_url($url, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? ''));
        $location = $sameHost ? $url : '/';
    } else {
        $location = $url;
    }

    if (!headers_sent()) {
        header('Location: ' . $location, true, $code);
        exit;
    }
    echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . '">';
    exit;
}
function logActivity(int $userId, string $action, string $detail = ''): void {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $now = date('Y-m-d H:i:s');

    try {
        $pdo = db();

        $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action VARCHAR(64) NOT NULL,
            detail TEXT NULL,
            ip VARCHAR(64) NULL,
            user_agent TEXT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, detail, ip, user_agent, created_at)
                               VALUES (:uid,:act,:det,:ip,:ua,:ts)");
        $stmt->execute([
            ':uid' => $userId ?: null,
            ':act' => $action,
            ':det' => $detail,
            ':ip'  => $ip,
            ':ua'  => $ua,
            ':ts'  => $now,
        ]);
    } catch (Throwable $e) {

        @file_put_contents(
            __DIR__ . '/activity.log',
            sprintf("[%s] uid=%s action=%s detail=%s\n", $now, $userId ?: '-', $action, $detail),
            FILE_APPEND
        );
    }
}


function logAction(string $action, $details = []): void {
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $detail = is_array($details)
        ? json_encode($details, JSON_UNESCAPED_UNICODE)
        : (string)$details;
    logActivity($uid, $action, $detail);
}

if (!defined('BCRYPT_COST')) {
    define('BCRYPT_COST', 12); 
}


function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}


function verifyCSRFToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput(string $s): string {
    $s = trim($s);
   
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s);
    return $s;
}

function sanitizeEmail(string $email): string {
    $email = trim($email);
    return filter_var($email, FILTER_SANITIZE_EMAIL) ?: '';
}


function validateUsername(string $u): bool {

    return (bool) preg_match('/^[A-Za-z0-9_]{3,20}$/', $u);
}

function validateEmail(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword(string $p): bool {

    if (strlen($p) < 8) return false;
    if (!preg_match('/[a-z]/', $p)) return false;
    if (!preg_match('/[A-Z]/', $p)) return false;
    if (!preg_match('/[0-9]/', $p)) return false;
    return true;
}
