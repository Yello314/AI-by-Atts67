<?php
require_once __DIR__ . '/config.php';

$BASE_PATH = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
if ($BASE_PATH === '') { $BASE_PATH = '/'; }

if (isLoggedIn()) {
    safeRedirect($BASE_PATH . '/index.php');
}

$error = '';
$success = isset($_GET['registered']) ? 'ลงทะเบียนสำเร็จ! กรุณาเข้าสู่ระบบ' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf)) {
        $error = 'ไม่สามารถยืนยันคำขอได้ (CSRF)';
    } elseif ($username === '' || $password === '') {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        try {
            $pdo = db();
            $stmt = $pdo->prepare("
                SELECT id, username, email, password_hash, role,
                       IFNULL(is_active, 1) AS is_active
                FROM users
                WHERE username = :id OR email = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ((int)$user['is_active'] === 0) {
                    $error = 'บัญชีของคุณถูกระงับการใช้งาน';
                } else {
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'] ?? '';
                    $_SESSION['role'] = strtolower($user['role'] ?? 'user');
                    
                    logAction('login_success', [
                        'username' => $user['username'],
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    ]);

                    $target = ($_SESSION['role'] === 'admin')
                        ? $BASE_PATH . '/admin/dashboard.php'
                        : $BASE_PATH . '/index.php';
                    
                    safeRedirect($target);
                }
            } else {
                logActivity(0, 'login_failed', json_encode([
                    'username' => $username,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'reason' => 'invalid_credentials'
                ], JSON_UNESCAPED_UNICODE));
                
                $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            }
        } catch (Throwable $e) {
            $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - <?php echo APP_NAME; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container flex items-center justify-center" style="min-height: 100vh;">
        <div class="login-container">
            <div class="login-header">
<br>                <h1>🎓 <?php echo APP_NAME; ?></h1>
                <p>เข้าสู่ระบบเพื่อเริ่มสร้างข้อสอบ</p>
            </div>

            <form class="login-form" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        ✅ <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
<br><br>
                <div class="form-group">
                    <label for="username">ชื่อผู้ใช้ หรือ อีเมล</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                           required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary w-full">
                    เข้าสู่ระบบ
                </button>

                <div class="text-center mt-4">
                    <p>ยังไม่มีบัญชี? 
                        <a href="<?php echo htmlspecialchars($BASE_PATH . '/register.php'); ?>" 
                           style="color: var(--primary); font-weight: 600;">
                            สมัครสมาชิก
                        </a>
                    </p>
                </div>

            </form>
        </div>
    </div>
    <?php include 'footer.php';?>
</body>
</html>