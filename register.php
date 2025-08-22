<?php
require_once 'config.php';

// ถ้า login แล้วให้ redirect
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $errors[] = 'Invalid request token';
    }
    
    // Validate inputs
    if (!validateUsername($username)) {
        $errors[] = 'ชื่อผู้ใช้ต้องมี 3-20 ตัวอักษร และใช้ได้เฉพาะ a-z, A-Z, 0-9, _';
    }
    
    if (!validateEmail($email)) {
        $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }
    
    if (!validatePassword($password)) {
        $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร ประกอบด้วยตัวพิมพ์ใหญ่ ตัวพิมพ์เล็ก และตัวเลข';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'รหัสผ่านไม่ตรงกัน';
    }
    
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
            $stmt->execute(['username' => $username, 'email' => $email]);
            
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว';
            } else {
                // Create new user
                $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, role)
                    VALUES (:username, :email, :password_hash, 'user')
                ");
                
                $stmt->execute([
                    'username' => $username,
                    'email' => $email,
                    'password_hash' => $password_hash
                ]);
                
                $success = true;
                
                // Redirect to login page after 2 seconds
                header('refresh:2;url=login.php?registered=1');
            }
        } catch (Exception $e) {
            $errors[] = 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
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
    <title>สมัครสมาชิก - <?php echo APP_NAME; ?></title>
    <!-- Main Styles -->
    <link rel="stylesheet" href="styles.css">
<!--    <style>
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
        
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .register-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .register-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .register-form {
            padding: 30px;
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
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input.error {
            border-color: #f44336;
        }
        
        .form-hint {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-error ul {
            margin: 5px 0 0 20px;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
            text-align: center;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e1e1e1;
        }
        
        .form-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .form-footer a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .password-strength {
            margin-top: 5px;
            height: 5px;
            background: #e1e1e1;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 3px;
        }
        
        .strength-weak { background: #f44336; width: 33%; }
        .strength-medium { background: #ff9800; width: 66%; }
        .strength-strong { background: #4caf50; width: 100%; }
    </style>  -->
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>🎓 สมัครสมาชิก</h1>
            <p>เข้าร่วมเพื่อสร้างข้อสอบด้วย AI</p>
        </div>
        
        <form class="register-form" method="POST">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>พบข้อผิดพลาด:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>✅ สมัครสมาชิกสำเร็จ!</strong><br>
                    กำลังนำคุณไปยังหน้าเข้าสู่ระบบ...
                </div>
            <?php endif; ?>
            
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                <div class="form-hint">ใช้ได้เฉพาะ a-z, A-Z, 0-9, _ (3-20 ตัวอักษร)</div>
            </div>
            
            <div class="form-group">
                <label for="email">อีเมล</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">รหัสผ่าน</label>
                <input type="password" id="password" name="password" required>
                <div class="form-hint">ต้องมีอย่างน้อย 8 ตัวอักษร (ตัวพิมพ์ใหญ่, เล็ก, ตัวเลข)</div>
                <div class="password-strength">
                    <div class="password-strength-bar" id="strength-bar"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">ยืนยันรหัสผ่าน</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn">สมัครสมาชิก</button>
            
            <div class="form-footer">
                <p>มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
            </div>
        </form>
    </div>
    
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const bar = document.getElementById('strength-bar');
            
            if (password.length < 8) {
                bar.className = 'password-strength-bar strength-weak';
            } else if (password.match(/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])/)) {
                bar.className = 'password-strength-bar strength-strong';
            } else if (password.match(/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])/)) {
                bar.className = 'password-strength-bar strength-medium';
            } else {
                bar.className = 'password-strength-bar strength-weak';
            }
        });
    </script>
</body>
<?php include 'footer.php';?>
</html>