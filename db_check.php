<?php
require_once __DIR__ . '/config.php';
$pdo = db();
$db  = $pdo->query('SELECT DATABASE()')->fetchColumn();
$c1  = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='activity_logs'")->fetchColumn();
$c2  = 0;
if ($c1) { $c2 = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn(); }
header('Content-Type: text/plain; charset=utf-8');
echo "DB=" . $db . "\n";
echo "has_activity_logs=" . $c1 . "\n";
echo "rows_in_activity_logs=" . $c2 . "\n";
