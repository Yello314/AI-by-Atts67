<?php
session_start();
define('DB_HOST', 'localhost');
define('DB_NAME', 'exam_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

$debug_info = [];
$test_results = [];

function getDBConnection() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

try {
    $pdo = getDBConnection();
    if ($pdo) {
        $test_results['db_connection'] = ['status' => 'success', 'message' => 'เชื่อมต่อฐานข้อมูลสำเร็จ'];
    } else {
        $test_results['db_connection'] = ['status' => 'error', 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล'];
    }
} catch (Exception $e) {
    $test_results['db_connection'] = ['status' => 'error', 'message' => $e->getMessage()];
}

if ($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $test_results['tables'] = ['status' => 'success', 'message' => 'พบตาราง: ' . implode(', ', $tables)];
        
        if (in_array('users', $tables)) {
            $stmt = $pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll();
            $col_names = array_column($columns, 'Field');
            $test_results['users_structure'] = ['status' => 'success', 'message' => 'Columns: ' . implode(', ', $col_names)];
        }
    } catch (Exception $e) {
        $test_results['tables'] = ['status' => 'error', 'message' => $e->getMessage()];
    }
}

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, username, email, role, is_active, created_at FROM users");
        $users = $stmt->fetchAll();
        $test_results['users_list'] = ['status' => 'success', 'data' => $users];
    } catch (Exception $e) {
        $test_results['users_list'] = ['status' => 'error', 'message' => $e->getMessage()];
    }
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_test_admin') {
        try {
            $password = 'Admin@123';
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE username = 'admin'");
            $stmt->execute();
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, is_active)
                VALUES ('admin', 'admin@example.com', :password_hash, 'admin', 1)
            ");
            $stmt->execute(['password_hash' => $password_hash]);
            
            $message = "✅ สร้าง Admin account สำเร็จ!\nUsername: admin\nPassword: Admin@123";
            $messageType = 'success';
        } catch (Exception $e) {
            $message = "❌ Error: " . $e->getMessage();
            $messageType = 'error';
        }
    } else if ($action === 'test_password') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        try {
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();
            
            if ($user) {
                if (password_verify($password, $user['password_hash'])) {
                    $message = "✅ รหัสผ่านถูกต้อง! สามารถ login ได้";
                    $messageType = 'success';
                } else {
                    $message = "❌ รหัสผ่านไม่ถูกต้อง";
                    $messageType = 'error';
                }
            } else {
                $message = "❌ ไม่พบ username นี้";
                $messageType = 'error';
            }
        } catch (Exception $e) {
            $message = "❌ Error: " . $e->getMessage();
            $messageType = 'error';
        }
    } else if ($action === 'reset_password') {
        $username = $_POST['reset_username'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        
        try {
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE username = :username");
            $stmt->execute(['password_hash' => $password_hash, 'username' => $username]);
            
            if ($stmt->rowCount() > 0) {
                $message = "✅ รีเซ็ตรหัสผ่านสำเร็จ!\nUsername: $username\nPassword ใหม่: $new_password";
                $messageType = 'success';
            } else {
                $message = "❌ ไม่พบ username นี้";
                $messageType = 'error';
            }
        } catch (Exception $e) {
            $message = "❌ Error: " . $e->getMessage();
            $messageType = 'error';
        }
    } else if ($action === 'fix_table_structure') {
        try {
            $alterQueries = [
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS `is_active` BOOLEAN DEFAULT TRUE",
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS `role` ENUM('user', 'admin') DEFAULT 'user'",
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS `email` VARCHAR(100)",
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS `last_login` TIMESTAMP NULL"
            ];
            
            foreach ($alterQueries as $query) {
                try {
                    $pdo->exec($query);
                } catch (Exception $e) {
                }
            }
            
            $message = "✅ ปรับปรุงโครงสร้างตารางเรียบร้อย";
            $messageType = 'success';
        } catch (Exception $e) {
            $message = "❌ Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$php_info = [
    'PHP Version' => PHP_VERSION,
    'Session Status' => session_status() == PHP_SESSION_ACTIVE ? 'Active' : 'Inactive',
    'Session ID' => session_id(),
    'Password Hash Support' => function_exists('password_hash') ? 'Yes' : 'No',
    'PDO MySQL' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Login System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #ee5a24 0%, #f79f1f 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .test-item {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .test-success {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        
        .test-error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        
        .test-warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f0f0f0;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            margin-right: 10px;
            margin-top: 10px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .code {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin: 10px 0;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .status-icon {
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Debug Login System</h1>
            <p>ตรวจสอบและแก้ไขปัญหาการเข้าสู่ระบบ</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo nl2br(htmlspecialchars($message)); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>📊 สถานะระบบ</h2>
            
            <?php foreach ($test_results as $test => $result): ?>
                <div class="test-item test-<?php echo $result['status']; ?>">
                    <span class="status-icon">
                        <?php echo $result['status'] === 'success' ? '✅' : '❌'; ?>
                    </span>
                    <div>
                        <strong><?php echo ucfirst(str_replace('_', ' ', $test)); ?>:</strong>
                        <?php echo $result['message'] ?? ''; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <h3 style="margin-top: 20px;">PHP Environment:</h3>
            <table>
                <?php foreach ($php_info as $key => $value): ?>
                <tr>
                    <td><strong><?php echo $key; ?>:</strong></td>
                    <td><?php echo $value; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <?php if (isset($test_results['users_list']) && $test_results['users_list']['status'] === 'success'): ?>
        <div class="card">
            <h2>👥 รายชื่อผู้ใช้ในระบบ</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Active</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($test_results['users_list']['data'] as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                        <td>
                            <span style="padding: 2px 8px; background: <?php echo $user['role'] === 'admin' ? '#ffc107' : '#6c757d'; ?>; color: white; border-radius: 3px;">
                                <?php echo $user['role'] ?? 'user'; ?>
                            </span>
                        </td>
                        <td><?php echo ($user['is_active'] ?? 1) ? '✅' : '❌'; ?></td>
                        <td><?php echo $user['created_at'] ?? '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>🛠️ เครื่องมือแก้ไขด่วน</h2>
            
            <div class="grid">
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <h3>1. สร้าง Admin ทดสอบ</h3>
                    <p style="color: #666; margin: 10px 0;">สร้าง account admin/Admin@123</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="create_test_admin">
                        <button type="submit" class="btn btn-success">สร้าง Test Admin</button>
                    </form>
                </div>
                
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <h3>2. ทดสอบรหัสผ่าน</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="test_password">
                        <div class="form-group">
                            <input type="text" name="username" placeholder="Username" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="password" placeholder="Password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">ทดสอบ</button>
                    </form>
                </div>
                
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <h3>3. รีเซ็ตรหัสผ่าน</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="reset_password">
                        <div class="form-group">
                            <input type="text" name="reset_username" placeholder="Username" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="new_password" placeholder="New Password" value="Pass@123" required>
                        </div>
                        <button type="submit" class="btn btn-danger">รีเซ็ต Password</button>
                    </form>
                </div>
                
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <h3>4. ซ่อมโครงสร้างตาราง</h3>
                    <p style="color: #666; margin: 10px 0;">เพิ่ม columns ที่อาจขาดหาย</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="fix_table_structure">
                        <button type="submit" class="btn btn-warning">ซ่อมตาราง</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>📝 วิธีแก้ไขปัญหา Login</h2>
            
            <ol style="line-height: 1.8;">
                <li><strong>ตรวจสอบสถานะระบบ:</strong> ดูว่าทุกอย่างเป็นสีเขียว ✅</li>
                <li><strong>ตรวจสอบ User:</strong> ดูว่ามี user ในระบบหรือไม่</li>
                <li><strong>ทดสอบรหัสผ่าน:</strong> ใช้เครื่องมือทดสอบด้านบน</li>
                <li><strong>สร้าง Admin ใหม่:</strong> ถ้าไม่มี admin ให้กดสร้าง Test Admin</li>
                <li><strong>รีเซ็ตรหัสผ่าน:</strong> ถ้าลืมรหัสผ่าน</li>
            </ol>
            
            <div class="code">
                <strong>Login ทดสอบที่แนะนำ:</strong><br>
                Username: admin<br>
                Password: Admin@123
            </div>
            
            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;">
                <strong>⚠️ หมายเหตุ:</strong><br>
                - ถ้า login ไม่ได้ ให้ลองสร้าง Test Admin ก่อน<br>
                - ตรวจสอบว่า is_active = 1 (✅)<br>
                - ตรวจสอบว่า password_hash ไม่ว่าง<br>
                - หลังแก้ไขเสร็จ ควรลบไฟล์นี้ออก
            </div>
        </div>
        
        <div class="card">
            <h2>🔐 ทดสอบ Login โดยตรง</h2>
            <div class="grid">
                <div>
                    <h3>Login Form Test</h3>
                    <form action="login.php" method="POST">
                        <div class="form-group">
                            <label>Username:</label>
                            <input type="text" name="username" value="admin" required>
                        </div>
                        <div class="form-group">
                            <label>Password:</label>
                            <input type="password" name="password" value="Admin@123" required>
                        </div>
                        <input type="hidden" name="csrf_token" value="<?php echo bin2hex(random_bytes(32)); ?>">
                        <button type="submit" class="btn btn-primary">ทดสอบ Login</button>
                    </form>
                </div>
                
                <div>
                    <h3>Quick Links</h3>
                    <a href="login.php" class="btn btn-primary" style="text-decoration: none; display: inline-block;">📋 ไปหน้า Login</a>
                    <a href="register.php" class="btn btn-success" style="text-decoration: none; display: inline-block;">📝 ไปหน้า Register</a>
                    <a href="fix-admin.php" class="btn btn-warning" style="text-decoration: none; display: inline-block;">🔧 Fix Admin</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>