<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$configFile = __DIR__ . '/../config.php';
$success = false;
$errors  = [];

$currentAppName   = APP_NAME;
$currentBcrypt    = defined('BCRYPT_COST') ? BCRYPT_COST : 12;
$currentTimezone  = date_default_timezone_get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appName  = trim($_POST['app_name'] ?? '');
    $bcrypt   = (int)($_POST['bcrypt_cost'] ?? 12);
    $timezone = trim($_POST['timezone'] ?? '');

    if ($appName === '') {
        $errors[] = 'กรุณากรอกชื่อระบบ';
    }
    if ($bcrypt < 8 || $bcrypt > 15) {
        $errors[] = 'BCRYPT_COST ต้องอยู่ระหว่าง 8-15';
    }
    if (!in_array($timezone, timezone_identifiers_list())) {
        $errors[] = 'โซนเวลาไม่ถูกต้อง';
    }

    if (!$errors) {
        $configContent = file_get_contents($configFile);

        $configContent = preg_replace(
            "/define\\('APP_NAME',\\s*'.*?'\\);/",
            "define('APP_NAME', '" . addslashes($appName) . "');",
            $configContent
        );
        
        $configContent = preg_replace(
            "/define\\('BCRYPT_COST',\\s*\\d+\\);/",
            "define('BCRYPT_COST', " . $bcrypt . ");",
            $configContent
        );

        $configContent = preg_replace(
            "/date_default_timezone_set\\('.*?'\\);/",
            "date_default_timezone_set('" . addslashes($timezone) . "');",
            $configContent
        );

        if (file_put_contents($configFile, $configContent) !== false) {
            $success = true;
            $currentAppName  = $appName;
            $currentBcrypt   = $bcrypt;
            $currentTimezone = $timezone;
        } else {
            $errors[] = 'ไม่สามารถบันทึกไฟล์ config.php ได้';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>ตั้งค่าระบบ - <?php echo APP_NAME; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body { font-family: Tahoma, sans-serif; background: #f5f6fa; margin:0; padding:20px; }
.container { max-width: 800px; margin:auto; background:#fff; border-radius: 8px; padding:20px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
h1 { margin-top:0; }
label { display:block; margin-top:15px; font-weight:bold; }
input, select { width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; margin-top:5px; }
button { margin-top:20px; padding:10px 15px; background:#667eea; color:#fff; border:none; border-radius:4px; cursor:pointer; }
button:hover { background:#546de5; }
.alert { padding:10px; border-radius:4px; margin-top:15px; }
.success { background:#e0ffe0; color:#2b8a3e; }
.error { background:#ffe0e0; color:#b02a37; }
</style>
</head>
<body>
<div class="container">
    <h1>⚙ ตั้งค่าระบบ</h1>
    <p><a href="Dashboard.php">ย้อนกลับ</a></p>

    <?php if ($success): ?>
        <div class="alert success">บันทึกการตั้งค่าสำเร็จ</div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert error">
            <?php foreach ($errors as $err) echo "<div>• ".htmlspecialchars($err)."</div>"; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <label>ชื่อระบบ (APP_NAME)</label>
        <input type="text" name="app_name" value="<?php echo htmlspecialchars($currentAppName); ?>">

        <label>ค่า BCRYPT_COST (8-15)</label>
        <input type="number" name="bcrypt_cost" value="<?php echo htmlspecialchars($currentBcrypt); ?>">

        <label>โซนเวลา</label>
        <select name="timezone">
            <?php foreach (timezone_identifiers_list() as $tz): ?>
                <option value="<?php echo htmlspecialchars($tz); ?>" <?php if ($tz === $currentTimezone) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($tz); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">💾 บันทึกการตั้งค่า</button>
    </form>
</div>
</body>
</html>
