<?php
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=UTF-8');

try {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    $topic        = trim($input['topic'] ?? 'ทั่วไป');
    $numQuestions = max(1, intval($input['numQuestions'] ?? 5));
    $examType     = $input['examType'] ?? 'multiple';
    $difficulty   = $input['difficulty'] ?? 'medium';

    switch ($difficulty) {
        case 'easy':   $diffLabel = 'ง่าย'; break;
        case 'medium': $diffLabel = 'ปานกลาง'; break;
        case 'hard':   $diffLabel = 'ยาก'; break;
        case 'mixed':  $diffLabel = 'ผสม (ง่าย, ปานกลาง, ยาก)'; break;
        default:       $diffLabel = 'ปานกลาง';
    }

    switch ($examType) {
        case 'truefalse':
            $prompt = <<<EOT
สร้างข้อสอบแบบถูกหรือผิด (True/False) จำนวน {$numQuestions} ข้อ เกี่ยวกับเรื่อง "{$topic}"
ระดับความยาก: {$diffLabel}
กรุณาส่งผลลัพธ์กลับมาในรูปแบบ JSON Array เท่านั้น โดยแต่ละ object มี key:
- "question"
- "answer" (ค่าที่ถูกต้อง: true หรือ false)
ไม่ต้องมีคำอธิบายเพิ่มเติม
EOT;
            break;

        case 'combined':
            $mf = floor($numQuestions / 2);
            $tf = $numQuestions - $mf;
            $prompt = <<<EOT
สร้างข้อสอบรูปแบบผสม (ปรนัย + ถูกหรือผิด) เกี่ยวกับเรื่อง "{$topic}"
ระดับความยาก: {$diffLabel}
- ปรนัย (Multiple Choice) จำนวน {$mf} ข้อ: มีตัวเลือก a, b, c, d และ key "answer" เป็น 'a'/'b'/...
- ถูกหรือผิด (True/False) จำนวน {$tf} ข้อ: key "answer" เป็น true/false
ส่งกลับ JSON Array โดยแต่ละ object มี:
- "question"
- ถ้าเป็นปรนัย มี "options" และ "answer"
- ถ้าเป็นถูกหรือผิด มี "answer"
ไม่ต้องมีคำอธิบายเพิ่มเติม
EOT;
            break;

        case 'multiple':
        default:
            $prompt = <<<EOT
สร้างข้อสอบแบบปรนัย (Multiple Choice) จำนวน {$numQuestions} ข้อ เกี่ยวกับเรื่อง "{$topic}"
ระดับความยาก: {$diffLabel}
แต่ละข้อมีตัวเลือก a, b, c, d และ key "answer" เป็น 'a'/'b'/...
ส่งกลับ JSON Array โดยแต่ละ object มี:
- "question"
- "options" (object a-d)
- "answer"
ไม่ต้องมีคำอธิบายเพิ่มเติม
EOT;
            break;
    }

    $apiKey = 'AIzaSyD9l4NNz8F8TEvNPqhsydiCrFTiv6mwdts';

    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ];

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        throw new Exception("cURL error: {$err}");
    }
    if ($status !== 200) {
        throw new Exception("HTTP status {$status} returned: {$response}");
    }

    $data = json_decode($response, true);
    $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

    if (!preg_match('/\[.*\]/s', $rawText, $matches)) {
        throw new Exception('AI did not return a valid JSON Array');
    }
    $json = $matches[0];

    if (json_decode($json) === null) {
        throw new Exception('Returned JSON is invalid');
    }

    if (ob_get_length()) ob_clean();
    echo $json;
    exit;

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['error' => '❌ ' . $e->getMessage()]);
    exit;
}
?>
