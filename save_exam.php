<?php
require_once __DIR__ . '/config.php';
requireLogin();
header('Content-Type: application/json; charset=utf-8');

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        throw new RuntimeException('Invalid JSON payload');
    }

    $uid        = (int)($_SESSION['user_id'] ?? 0);
    $title      = trim((string)($data['title'] ?? ''));
    $topic      = trim((string)($data['topic'] ?? ''));
    $difficulty = trim((string)($data['difficulty'] ?? ''));
    $examType   = trim((string)($data['exam_type'] ?? ''));
    $questions  = $data['questions'] ?? null;

    if ($uid <= 0) {
        throw new RuntimeException('User not authenticated');
    }
    if ($title === '' || !is_array($questions) || count($questions) === 0) {
        throw new RuntimeException('กรอกชื่อชุดข้อสอบและรายการคำถามให้ครบ');
    }

    $pdo = db();

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS saved_exams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            topic VARCHAR(255) NULL,
            difficulty VARCHAR(32) NULL,
            exam_type VARCHAR(32) NULL,
            questions LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $stmt = $pdo->prepare("
        INSERT INTO saved_exams (user_id, title, topic, difficulty, exam_type, questions)
        VALUES (:uid, :title, :topic, :difficulty, :exam_type, :questions)
    ");

    $stmt->execute([
        ':uid'        => $uid,
        ':title'      => $title,
        ':topic'      => $topic,
        ':difficulty' => $difficulty,
        ':exam_type'  => $examType,
        ':questions'  => json_encode($questions, JSON_UNESCAPED_UNICODE),
    ]);

    $newId = (int)$pdo->lastInsertId();

    logAction('exam_saved', [
        'exam_id' => $newId,
        'title'   => $title,
        'count'   => count($questions),
    ]);

    echo json_encode(['success' => true, 'id' => $newId], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
