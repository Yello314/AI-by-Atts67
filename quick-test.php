<?php
session_start();

$db_host = 'localhost';
$db_name = 'exam_system';
$db_user = 'root';
$db_pass = '';

$message = '';
$tests = [];

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $tests['database'] = ['status' => 'success', 'message' => 'เชื่อมต่อฐานข้อมูลสำเร็จ'];
} catch (PDOException $e) {
    $tests['database'] = ['status' => 'error', 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล: ' . $e->getMessage()];
    $pdo = null;
}

if ($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            $tests['users_table'] = ['status' => 'success', 'message' => 'พบตาราง users'];
            
            $stmt = $pdo->query("SHOW COLUMNS FROM users");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $tests['columns'] = ['status' => 'success', 'message' => 'Columns: ' . implode(', ', $columns)];
        } else {
            $tests['users_table'] = ['status' => 'error', 'message' => 'ไม่พบตาราง users'];
        }
        
        $tables = ['activity_logs', 'saved_exams', 'exam_attempts'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $tests[$table] = [
                'status' => $stmt->rowCount() > 0 ? 'warning' : 'info',
                'message' => $stmt->rowCount() > 0 ? "พบตาราง $table" : "ไม่พบตาราง $table (optional)"
            ];
        }
        
    } catch (Exception $e) {
        $tests['tables'] = ['status' => 'error', 'message' => $e->getMessage()];
    }
}

$files = [
    'index.php' => 'หน้าหลัก',
    'login.php' => 'หน้า Login',
    'register.php' => 'หน้าสมัครสมาชิก',
    'config.php' => 'ไฟล์ Config',
    'admin/dashboard.php' => 'Admin Dashboard',
    'generate_exam.php' => 'Generate Exam API'
];

foreach ($files as $file => $desc) {
    $tests['file_' . str_replace('/', '_', $file)] = [
        'status' => file_exists($file) ? 'success' : 'warning',
        'message' => $desc . ': ' . (file_exists($file) ? 'พบไฟล์' : 'ไม่พบไฟล์')
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_admin') {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE username = 'admin'");
            $stmt->execute();
            
            $password_hash = password_hash('Admin@123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, is_active)
                VALUES ('admin', 'admin@test.com', ?, 'admin', 1)
            ");
            $stmt->execute([$password_hash]);
            
            $message = 'success|สร้าง Admin สำเร็จ! Username: admin, Password: Admin@123';
        } catch (Exception $e) {
            $message = 'error|' . $e->getMessage();
        }
    } elseif ($action === 'create_tables') {
        try {
            $queries = [
                "CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    email VARCHAR(100),
                    password_hash VARCHAR(255) NOT NULL,
                    role VARCHAR(20) DEFAULT 'user',
                    is_active TINYINT(1) DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_login TIMESTAMP NULL
                )",
                "CREATE TABLE IF NOT EXISTS activity_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    action VARCHAR(100),
                    details JSON,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS saved_exams (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    exam_title VARCHAR(255),
                    topic VARCHAR(255),
                    difficulty VARCHAR(20),
                    exam_type VARCHAR(20),
                    questions_data JSON,
                    is_public TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS exam_attempts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    exam_id INT,
                    score INT,
                    total_questions INT,
                    time_spent INT,
                    answers_data JSON,
                    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )"
            ];
            
            foreach ($queries as $query) {
                $pdo->exec($query);
            }
            
            $message = 'success|สร้างตารางทั้งหมดเรียบร้อย!';
        } catch (Exception $e) {
            $message = 'error|' . $e->getMessage();
        }
    } elseif ($action === 'auto_login') {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin' AND role = 'admin'");
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'] ?? '';
                $_SESSION['role'] = $user['role'];
                
                header('Location: index.php');
                exit;
            } else {
                $message = 'error|ไม่พบ Admin account';
            }
        } catch (Exception $e) {
            $message = 'error|' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Test & Setup</title>
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
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .test-item {
            padding: 12px;
            margin: 8px 0;
            border-radius: 8px;
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
        
        .test-info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
        }
        
        .actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .action-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        
        .action-card h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .action-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
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
        
        .links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        
        .links a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            transition: background 0.3s;
        }
        
        .links a:hover {
            background: rgba(255,255,255,0.3);
        }
        
        code {
            background: #f4f4f4;
            padding: 2px 6px;