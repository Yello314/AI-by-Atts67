<?php
require_once __DIR__ . '/config.php';
requireLogin(); 
logAction('debug_ping', ['by' => $_SESSION['username'] ?? 'unknown', 'ts' => time()]);
echo "OK";
