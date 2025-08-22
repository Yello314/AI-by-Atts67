<?php
session_start();

$db_host = 'localhost';
$db_name = 'exam_system';
$db_user = 'root';
$db_pass = '';

$error = '';
$success = '';
$show_form = true;

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        } else {
            try {
                $pdo = new PDO(
                    "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
                    $db_user,
                    $db_pass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                $sql = "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'] ?? '';
                    $_SESSION['role'] = $user['role'] ?? 'user';
                    
                    $success = 'เข้าสู่ระบบสำเร็จ! กำลังนำคุณไปยังหน้าหลัก...';
                    $show_form = false;
                    
                    header('refresh:2;url=index.php');
                } else {
                    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                }
            } catch (PDOException $e) {
                $error = 'Database Error: ' . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'create_admin') {
        try {
            $pdo = new PDO(
                "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
                $db_user,
                $db_pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
            $stmt->execute();
            
            if ($stmt->fetch()) {
                $password_hash = password_hash('Admin@123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
                $stmt->execute([$password_hash]);
                $success = 'อัพเดท Admin password เรียบร้อย!';
            } else {
                $password_hash = password_hash('Admin@123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, role) 
                    VALUES ('admin', 'admin@test.com', ?, 'admin')
                ");
                $stmt->execute([$password_hash]);
                $success = 'สร้าง Admin account เรียบร้อย!';
            }
            $success .= '<br>Username: admin<br>Password: Admin@123';
        } catch (PDOException $e) {
            $error = 'ไม่สามารถสร้าง Admin: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Login - ระบบออกข้อสอบ AI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 30px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-bottom: 10px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e1e1e1;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #999;
            font-size: 14px;
        }
        
        .help-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .help-box h4 {
            color: #495057;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .help-box p {
            color: #6c757d;
            font-size: 14px;
            margin: 5px 0;
        }
        
        .help-box code {
            background: #fff;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
            color: #e83e8c;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e1e1e1;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .status-check {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .status-check strong {
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 ระบบออกข้อสอบ AI</h1>
            <p>Simple Login Page</p>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php
            $db_status = 'Unknown';
            $table_exists = false;
            try {
                $pdo = new PDO(
                    "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
                    $db_user,
                    $db_pass
                );
                $db_status = 'Connected';
                
                $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
                $table_exists = $stmt->rowCount() > 0;
            } catch (PDOException $e) {
                $db_status = 'Failed: ' . $e->getMessage();
            }
            ?>
            
            <div class="status-check">
                <strong>🔍 System Check:</strong><br>
                Database: <?php echo $db_status; ?><br>
                Users Table: <?php echo $table_exists ? '✅ Found' : '❌ Not Found'; ?>
            </div>
            
            <?php if ($show_form): ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label>ชื่อผู้ใช้ หรือ อีเมล</label>
                    <input type="text" name="username" required autofocus 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>รหัสผ่าน</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">เข้าสู่ระบบ</button>
            </form>
            
            <div class="divider">
                <span>หรือ</span>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="create_admin">
                <button type="submit" class="btn btn-success">
                    🔧 สร้าง/รีเซ็ต Admin Account (admin/Admin@123)
                </button>
            </form>
            <?php endif; ?>
            
            <div class="help-box">
                <h4>📌 วิธีใช้งาน:</h4>
                <p>1. คลิก "สร้าง/รีเซ็ต Admin Account" ด้านบน</p>
                <p>2. ใช้ <code>admin</code> / <code>Admin@123</code> เข้าสู่ระบบ</p>
                <p>3. หากยังไม่ได้ ให้ตรวจสอบว่ามีฐานข้อมูลและตาราง users</p>
            </div>
            
            <div class="links">
                <a href="register.php">สมัครสมาชิก</a>
                <a href="debug-login.php">Debug</a>
                <a href="fix-admin.php">Fix Admin</a>
            </div>
        </div>
    </div>
</body>
</html>