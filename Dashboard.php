<?php
require_once '../config.php';
requireAdmin();

$pdo = getDBConnection();

$stats = [];

$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(role='admin') as admins FROM users");
$stats['users'] = $stmt->fetch();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM saved_exams");
$stats['exams'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM exam_attempts");
$stats['attempts'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM activity_logs WHERE DATE(created_at) = CURDATE()");
$stats['today_activities'] = $stmt->fetch()['total'];

$stmt = $pdo->prepare("
    SELECT a.*, u.username 
    FROM activity_logs a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_activities = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT u.username, COUNT(e.id) as exam_count 
    FROM users u 
    LEFT JOIN saved_exams e ON u.id = e.user_id 
    GROUP BY u.id 
    ORDER BY exam_count DESC 
    LIMIT 5
");
$stmt->execute();
$top_creators = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
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
        
        .sidebar-header p {
            font-size: 13px;
            opacity: 0.8;
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
        
        .header h1 {
            color: #333;
            font-size: 24px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #888;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-card .sub-value {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card h2 {
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f5f6fa;
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
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .two-columns {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .two-columns {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🎓 Admin Panel</h2>
            <p><?php echo APP_NAME; ?></p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="active">📊 Dashboard</a>
            <a href="users.php">👥 จัดการผู้ใช้</a>
            <a href="exams.php">📝 จัดการข้อสอบ</a>
            <a href="activities.php">📋 Activity Logs</a>
            <a href="settings.php">⚙️ ตั้งค่าระบบ</a>
            <a href="../index.php">🏠 กลับหน้าหลัก</a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Dashboard Overview</h1>
            <div class="user-info">
                <span>👤 <?php echo $_SESSION['username']; ?> (Admin)</span>
                <a href="../logout.php" class="logout-btn">ออกจากระบบ</a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>ผู้ใช้ทั้งหมด</h3>
                <div class="value"><?php echo $stats['users']['total']; ?></div>
                <div class="sub-value">Admin: <?php echo $stats['users']['admins']; ?> คน</div>
            </div>
            
            <div class="stat-card">
                <h3>ข้อสอบที่สร้าง</h3>
                <div class="value"><?php echo $stats['exams']; ?></div>
                <div class="sub-value">ชุดข้อสอบทั้งหมด</div>
            </div>
            
            <div class="stat-card">
                <h3>การทำข้อสอบ</h3>
                <div class="value"><?php echo $stats['attempts']; ?></div>
                <div class="sub-value">ครั้งที่ทำข้อสอบ</div>
            </div>
            
            <div class="stat-card">
                <h3>กิจกรรมวันนี้</h3>
                <div class="value"><?php echo $stats['today_activities']; ?></div>
                <div class="sub-value">รายการ</div>
            </div>
        </div>
        
        <div class="two-columns">
            <div class="card">
                <h2>📊 กิจกรรมล่าสุด</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ผู้ใช้</th>
                            <th>กิจกรรม</th>
                            <th>เวลา</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_activities as $activity): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($activity['username']); ?></td>
                            <td>
                                <?php
                                $action = $activity['action'];
                                $badge_class = 'badge-info';
                                if (strpos($action, 'login') !== false) $badge_class = 'badge-success';
                                if (strpos($action, 'exam') !== false) $badge_class = 'badge-warning';
                                if (strpos($action, 'failed') !== false) $badge_class = 'badge-danger';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars($action); ?>
                                </span>
                            </td>
                            <td><?php echo date('H:i', strtotime($activity['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>🏆 Top Creators</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ผู้ใช้</th>
                            <th>ข้อสอบ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_creators as $creator): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($creator['username']); ?></td>
                            <td><strong><?php echo $creator['exam_count']; ?></strong> ชุด</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>