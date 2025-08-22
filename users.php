<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$db_host = 'localhost';
$db_name = 'exam_system';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle_status') {
        $user_id = $_POST['user_id'] ?? 0;
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = 'อัพเดทสถานะผู้ใช้เรียบร้อย';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'change_role') {
        $user_id = $_POST['user_id'] ?? 0;
        $new_role = $_POST['new_role'] ?? 'user';
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            $message = 'เปลี่ยน Role เรียบร้อย';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'delete_user') {
        $user_id = $_POST['user_id'] ?? 0;
        if ($user_id != $_SESSION['user_id']) { // Don't delete self
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = 'ลบผู้ใช้เรียบร้อย';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = 'ไม่สามารถลบตัวเองได้';
            $messageType = 'error';
        }
    }
}

$stmt = $pdo->query("
    SELECT u.*, 
           COUNT(DISTINCT se.id) as exam_count,
           COUNT(DISTINCT ea.id) as attempt_count
    FROM users u
    LEFT JOIN saved_exams se ON u.id = se.user_id
    LEFT JOIN exam_attempts ea ON u.id = ea.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: white;
        }
        
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.15);
            border-left-color: white;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logout-btn {
            padding: 8px 16px;
            background: #f44336;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 12px;
            background: #f5f6fa;
            font-weight: 600;
            color: #666;
            font-size: 14px;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #f5f6fa;
            font-size: 14px;
        }
        
        tr:hover {
            background: #fafafa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-admin {
            background: #ffc107;
            color: #333;
        }
        
        .badge-user {
            background: #6c757d;
            color: white;
        }
        
        .badge-active {
            background: #28a745;
            color: white;
        }
        
        .badge-inactive {
            background: #dc3545;
            color: white;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin: 0 2px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #888;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🎓 Admin Panel</h2>
            <p>ระบบออกข้อสอบ AI</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="users.php" class="active">👥 จัดการผู้ใช้</a>
            <a href="exams.php">📝 จัดการข้อสอบ</a>
            <a href="activities.php">📋 Activity Logs</a>
            <a href="../index.php">🏠 กลับหน้าหลัก</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>จัดการผู้ใช้</h1>
            <div>
                <span>👤 <?php echo $_SESSION['username']; ?> (Admin)</span>
                <a href="../logout.php" class="logout-btn">ออกจากระบบ</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>ผู้ใช้ทั้งหมด</h3>
                <div class="value"><?php echo count($users); ?></div>
            </div>
            <div class="stat-card">
                <h3>Admin</h3>
                <div class="value">
                    <?php echo count(array_filter($users, function($u) { 
                        return ($u['role'] ?? 'user') === 'admin'; 
                    })); ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>Active Users</h3>
                <div class="value">
                    <?php echo count(array_filter($users, function($u) { 
                        return ($u['is_active'] ?? 1) == 1; 
                    })); ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2 style="margin-bottom: 20px;">รายชื่อผู้ใช้ทั้งหมด</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>ข้อสอบ</th>
                        <th>ทำข้อสอบ</th>
                        <th>สร้างเมื่อ</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                        <td>
                            <span class="badge badge-<?php echo ($user['role'] ?? 'user') === 'admin' ? 'admin' : 'user'; ?>">
                                <?php echo $user['role'] ?? 'user'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo ($user['is_active'] ?? 1) ? 'active' : 'inactive'; ?>">
                                <?php echo ($user['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td><?php echo $user['exam_count']; ?></td>
                        <td><?php echo $user['attempt_count']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <div class="actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-warning">
                                        <?php echo ($user['is_active'] ?? 1) ? 'Disable' : 'Enable'; ?>
                                    </button>
                                </form>
                                
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="change_role">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="new_role" value="<?php echo ($user['role'] ?? 'user') === 'admin' ? 'user' : 'admin'; ?>">
                                    <button type="submit" class="btn btn-primary">
                                        Make <?php echo ($user['role'] ?? 'user') === 'admin' ? 'User' : 'Admin'; ?>
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('แน่ใจที่จะลบผู้ใช้นี้?');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-danger">ลบ</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>