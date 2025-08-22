<?php
require_once __DIR__ . '/../config.php';
requireAdmin(); 
$pdo = db();

$uid     = (int)($_SESSION['user_id'] ?? 0);
$isAdmin = isAdmin();

$keyword = trim((string)($_GET['q'] ?? ''));
$params  = [];
$sql = "
    SELECT a.*, u.username 
    FROM activity_logs a
    LEFT JOIN users u ON a.user_id = u.id
";
$where = [];

if (!$isAdmin) {
    $where[] = "a.user_id = :uid";
    $params[':uid'] = $uid;
}
if ($keyword !== '') {
    $where[] = "(a.action LIKE :kw OR a.detail LIKE :kw OR u.username LIKE :kw)";
    $params[':kw'] = '%'.$keyword.'%';
}
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY a.created_at DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

function fmt_detail($detail): string {
    $text = (string)$detail;
    $arr  = json_decode($text, true);
    if (is_array($arr)) {
        $pairs = [];
        foreach ($arr as $k => $v) {
            if (is_array($v) || is_object($v)) {
                $v = json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            }
            $v = (string)$v;
            if (mb_strlen($v) > 60) $v = mb_substr($v, 0, 57) . '...';
            $pairs[] = "$k=$v";
        }
        return implode(' | ', $pairs);
    }
    return $text;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>กิจกรรมล่าสุด - <?php echo APP_NAME; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    body { font-family: "Segoe UI", Tahoma, sans-serif; background: #f5f6fa; margin:0; padding:20px; }
    .container { max-width: 1200px; margin:auto; background:#fff; border-radius: 8px; padding:20px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
    h1 { margin-top:0; }
    form.search { margin-bottom: 16px; display:flex; gap:8px; }
    form.search input { padding:8px 12px; border:1px solid #ccc; border-radius:4px; flex:1; }
    form.search button { padding:8px 16px; background:#667eea; border:none; color:#fff; border-radius:4px; cursor:pointer; }
    table { width:100%; border-collapse: collapse; }
    th, td { padding:8px 10px; border-bottom:1px solid #eee; text-align:left; vertical-align: top; font-size: 14px; }
    th { background:#fafbff; color:#555; font-weight:600; }
    tr:hover { background:#fafafa; }
    .muted { color:#777; font-size: 12px; }
    .detail { max-width: 480px; white-space: nowrap; overflow:hidden; text-overflow: ellipsis; }
    .badge { display:inline-block; padding:2px 6px; border-radius:4px; font-size:12px; color:#fff; }
    .badge.login { background:#4caf50; }
    .badge.exam { background:#ff9800; }
    .badge.logout { background:#f44336; }
    .badge.default { background:#607d8b; }
    .topbar a { color:#667eea; text-decoration:none; }
    .topbar a:hover { text-decoration:underline; }
</style>
</head>
<body>
<div class="container">
    <h1>📋 กิจกรรมล่าสุด</h1>
    <div class="topbar" style="margin-bottom:8px;">
        👤 <?php echo htmlspecialchars($_SESSION['username']); ?> 
        <?php if ($isAdmin): ?> <span style="color:#888;">(Admin)</span> <?php endif; ?>
        | <a href="Dashboard.php">ย้อนกลับ</a> | <a href="../logout.php">ออกจากระบบ</a>
    </div>

    <form class="search" method="get">
        <input type="text" name="q" placeholder="ค้นหา (กิจกรรม, รายละเอียด, ชื่อผู้ใช้)" value="<?php echo htmlspecialchars($keyword); ?>">
        <button type="submit">ค้นหา</button>
    </form>

    <?php if (!$rows): ?>
        <p class="muted">ไม่มีข้อมูลกิจกรรม</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>เวลา</th>
                <?php if ($isAdmin): ?><th>ผู้ใช้</th><?php endif; ?>
                <th>กิจกรรม</th>
                <th>รายละเอียด</th>
                <th>IP</th>
                <th>User Agent</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): 
            $action = strtolower($r['action']);
            $badgeClass = 'default';
            if (strpos($action,'login') !== false) $badgeClass = 'login';
            elseif (strpos($action,'exam') !== false) $badgeClass = 'exam';
            elseif (strpos($action,'logout') !== false) $badgeClass = 'logout';
        ?>
            <tr>
                <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($r['created_at']))); ?></td>
                <?php if ($isAdmin): ?><td><?php echo htmlspecialchars($r['username'] ?? '-'); ?></td><?php endif; ?>
                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($r['action']); ?></span></td>
                <td class="detail" title="<?php echo htmlspecialchars($r['detail']); ?>">
                    <?php echo htmlspecialchars(fmt_detail($r['detail'])); ?>
                </td>
                <td class="muted"><?php echo htmlspecialchars($r['ip']); ?></td>
                <td class="muted"><?php echo htmlspecialchars($r['user_agent']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>
