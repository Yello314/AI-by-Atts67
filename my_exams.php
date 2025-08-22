<?php
require_once __DIR__ . '/config.php';
requireLogin();
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

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$CSRF = $_SESSION['csrf_token'];
$uid  = (int)($_SESSION['user_id'] ?? 0);

function fetchExamById(PDO $pdo, int $id, int $uid) {
  $st = $pdo->prepare("SELECT * FROM saved_exams WHERE id = :id AND user_id = :uid");
  $st->execute([':id'=>$id, ':uid'=>$uid]);
  return $st->fetch();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'export' && $method === 'GET') {
  $id = (int)($_GET['id'] ?? 0);
  $type = $_GET['type'] ?? 'aiken';
  $exam = fetchExamById($pdo, $id, $uid);
  if (!$exam) { http_response_code(404); echo "Not found"; exit; }

  $questions = json_decode($exam['questions'], true) ?: [];
  $filenameBase = preg_replace('/[^a-zA-Z0-9ก-๙_\-]+/u','_', $exam['title'] ?: ('exam_'.$id));

  if ($type === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filenameBase.'.json"');
    echo json_encode([
      'title'=>$exam['title'],'topic'=>$exam['topic'],
      'difficulty'=>$exam['difficulty'],'exam_type'=>$exam['exam_type'],
      'questions'=>$questions
    ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
  }

  $out = '';
  foreach ($questions as $i=>$q) {
    $qt = trim((string)($q['question'] ?? ''));
    $out .= ($i+1).". ".$qt."\n";
    if (!empty($q['options']) && is_array($q['options'])) {
      $keys = array_keys($q['options']); $letters = ['A','B','C','D','E','F'];
      foreach ($keys as $k=>$key) { $out .= ($letters[$k] ?? chr(65+$k)).". ".(string)$q['options'][$key]."\n"; }
      $ansKey = (string)($q['answer'] ?? ''); $ansIdx = array_search($ansKey, $keys, true);
      $out .= "ANSWER: ".($ansIdx!==false ? ($letters[$ansIdx] ?? chr(65+$ansIdx)) : '')."\n\n";
    } else {
      $out .= "A. True\nB. False\n";
      $out .= "ANSWER: ".(strtolower((string)($q['answer'] ?? ''))==='true'?'A':'B')."\n\n";
    }
  }
  header('Content-Type: text/plain; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$filenameBase.'.txt"');
  echo $out; exit;
}

if ($action === 'delete' && $method === 'POST') {
  header('Content-Type: application/json; charset=utf-8');
  $id = (int)($_POST['id'] ?? 0);
  $token = $_POST['csrf'] ?? '';
  if (!hash_equals($CSRF, $token)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Invalid CSRF']); exit; }
  $exam = fetchExamById($pdo, $id, $uid);
  if (!$exam) { echo json_encode(['success'=>false,'error'=>'Not found']); exit; }
  $pdo->prepare("DELETE FROM saved_exams WHERE id = :id AND user_id = :uid")->execute([':id'=>$id, ':uid'=>$uid]);
  logAction('exam_deleted', ['exam_id'=>$id, 'title'=>$exam['title']]);
  echo json_encode(['success'=>true]); exit;
}

if ($action === 'rename' && $method === 'POST') {
  header('Content-Type: application/json; charset=utf-8');
  $id = (int)($_POST['id'] ?? 0);
  $title = trim((string)($_POST['title'] ?? ''));
  $token = $_POST['csrf'] ?? '';
  if (!hash_equals($CSRF, $token)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Invalid CSRF']); exit; }
  if ($title==='') { echo json_encode(['success'=>false,'error'=>'กรุณากรอกชื่อ']); exit; }
  $exam = fetchExamById($pdo, $id, $uid);
  if (!$exam) { echo json_encode(['success'=>false,'error'=>'Not found']); exit; }
  $pdo->prepare("UPDATE saved_exams SET title = :t WHERE id = :id AND user_id = :uid")
      ->execute([':t'=>$title, ':id'=>$id, ':uid'=>$uid]);
  logAction('exam_renamed', ['exam_id'=>$id,'old'=>$exam['title'],'new'=>$title]);
  echo json_encode(['success'=>true,'title'=>$title]); exit;
}

$keyword = trim((string)($_GET['q'] ?? ''));
$params = [':uid'=>$uid];
$sql = "SELECT * FROM saved_exams WHERE user_id = :uid";
if ($keyword !== '') { $sql .= " AND (title LIKE :kw OR topic LIKE :kw)"; $params[':kw'] = '%'.$keyword.'%'; }
$sql .= " ORDER BY created_at DESC, id DESC";
$st = $pdo->prepare($sql); $st->execute($params); $rows = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ข้อสอบของฉัน - <?php echo APP_NAME; ?></title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="theme-airforce">
  <div class="container">
    <div class="card">

      <div class="user-header">
        <div class="user-info">
          👤 <?php echo htmlspecialchars($_SESSION['username']); ?>
          <span class="badge badge-primary">My Exams</span>
        </div>
        <div class="buttons">
          <a href="index.php">🏠 กลับไปสร้างข้อสอบ</a>
          <a href="logout.php" class="logout">ออกจากระบบ</a>
        </div>
      </div>

      <h1 class="card-title">🗂️ ข้อสอบของฉัน</h1>

      <form class="search" method="get">
        <input type="text" name="q" placeholder="ค้นหาจากชื่อ/หัวข้อ…" value="<?php echo htmlspecialchars($keyword); ?>">
        <button class="btn btn-primary" type="submit">ค้นหา</button>
      </form>

      <?php if (!$rows): ?>
        <div class="empty">ยังไม่มีข้อสอบที่บันทึกไว้ — ไปที่หน้า <a href="index.php">สร้างข้อสอบ</a> แล้วกด “บันทึกข้อสอบ”</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>ชื่อชุดข้อสอบ</th>
              <th>รายละเอียด</th>
              <th>วันที่บันทึก</th>
              <th class="text-right">การทำงาน</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="activity-log-row" data-id="<?php echo (int)$r['id']; ?>">
              <td>
                <div class="font-bold js-title"><?php echo htmlspecialchars($r['title'] ?: '(ยังไม่ตั้งชื่อ)'); ?></div>
                <div class="text-muted">ID: <?php echo (int)$r['id']; ?></div>
              </td>
              <td>
                <div class="mb-2">
                  <span class="badge badge-primary">เรื่อง: <?php echo htmlspecialchars($r['topic'] ?? '-'); ?></span>
                </div>
                <div class="gap-2">
                  <span class="badge badge-success">ระดับ: <?php echo strtoupper(htmlspecialchars($r['difficulty'] ?? '-')); ?></span>
                  <span class="badge badge-warning">รูปแบบ: <?php echo strtoupper(htmlspecialchars($r['exam_type'] ?? '-')); ?></span>
                </div>
              </td>
              <td class="text-muted">
                <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($r['created_at']))); ?>
              </td>
              <td class="text-right actions">
                <button class="btn btn-outline btn-view" type="button">ดู</button>
                <button class="btn btn-secondary btn-rename" type="button">เปลี่ยนชื่อ</button>
                <a class="btn btn-success" href="my_exams.php?action=export&type=aiken&id=<?php echo (int)$r['id']; ?>">AIKEN</a>
                <a class="btn btn-warning" href="my_exams.php?action=export&type=json&id=<?php echo (int)$r['id']; ?>">JSON</a>
                <button class="btn btn-danger btn-delete" type="button">ลบ</button>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

<div id="modal" class="modal-backdrop" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="head">
      <div id="m-title">รายละเอียดข้อสอบ</div>
      <button type="button" class="close-btn" id="m-close" aria-label="ปิด">✕</button>
    </div>
    <div class="body" id="m-body"></div>
  </div>
</div>


<script>
const CSRF = <?php echo json_encode($CSRF); ?>;
const $  = (s,root=document)=>root.querySelector(s);
const $$ = (s,root=document)=>Array.from(root.querySelectorAll(s));

const modal  = $('#modal');
const htmlEl = document.documentElement;

const openModal  = () => { modal.classList.add('show'); htmlEl.classList.add('lock-scroll'); };
const closeModal = () => { modal.classList.remove('show'); htmlEl.classList.remove('lock-scroll'); };

document.addEventListener('click', (e) => {
  if (e.target === modal) closeModal();
  if (e.target.id === 'm-close' || e.target.closest('#m-close')) closeModal();
});
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && modal.classList.contains('show')) closeModal();
});

$$('.btn-view').forEach(btn=>{
  btn.addEventListener('click', async ()=>{
    const tr = btn.closest('tr'); const id = tr?.dataset?.id; if(!id) return;
    try {
      const res = await fetch(`my_exams.php?action=export&type=json&id=${encodeURIComponent(id)}`);
      if(!res.ok) throw new Error('โหลดข้อมูลไม่สำเร็จ');
      const data = await res.json();

      $('#m-title').textContent = data.title || 'รายละเอียดข้อสอบ';
      const body = $('#m-body'); body.innerHTML = '';

      (data.questions||[]).forEach((q,i)=>{
        const wrap = document.createElement('div'); wrap.className = 'question';

        const title = document.createElement('p');
        title.innerHTML = `<strong>ข้อ ${i+1}:</strong> ${q.question||''}`;
        wrap.appendChild(title);

        if (q.options && typeof q.options === 'object') {
          const list = document.createElement('div'); list.className = 'options';
          const entries = Object.entries(q.options);   
          entries.forEach(([k,v], idx) => {
            const p = document.createElement('label');
            p.textContent = `${String.fromCharCode(65+idx)}. ${v}`;
            list.appendChild(p);
          });
          wrap.appendChild(list);

          const keys = entries.map(([k])=>k);
          const ansKey = String(q.answer ?? '');
          const ansIdx = keys.indexOf(ansKey);
          const ansLetter = ansIdx >= 0 ? String.fromCharCode(65+ansIdx) : '';
          const ans = document.createElement('div'); ans.className='answerKey';
          ans.textContent = `เฉลย: ${ansLetter}${q.options[ansKey] ? ' — ' + q.options[ansKey] : ''}`;
          wrap.appendChild(ans);
        } else {
          const list = document.createElement('div'); list.className = 'options';
          ['True','False'].forEach((v,idx)=>{
            const p=document.createElement('label'); p.textContent=`${String.fromCharCode(65+idx)}. ${v}`;
            list.appendChild(p);
          });
          wrap.appendChild(list);

          const ans = document.createElement('div'); ans.className='answerKey';
          ans.textContent = `เฉลย: ${String(q.answer).toLowerCase()==='true' ? 'A' : 'B'}`;
          wrap.appendChild(ans);
        }

        body.appendChild(wrap);
      });

      openModal(); 
    } catch(e){ alert('❌ '+e.message); }
  });
});

$$('.btn-rename').forEach(btn=>{
  btn.addEventListener('click', async ()=>{
    const tr = btn.closest('tr'); const id = tr?.dataset?.id; if(!id) return;
    const titleEl = tr.querySelector('.js-title') || tr.querySelector('td > div');
    const cur = (titleEl?.textContent || '').trim();
    const title = prompt('ตั้งชื่อชุดข้อสอบใหม่:', cur);
    if(!title) return;
    try{
      const res = await fetch('my_exams.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({action:'rename',id,title,csrf:CSRF})
      });
      const data = await res.json();
      if(!data.success) throw new Error(data.error||'เปลี่ยนชื่อไม่สำเร็จ');
      if (titleEl) titleEl.textContent = data.title;
    }catch(e){ alert('❌ '+e.message); }
  });
});

$$('.btn-delete').forEach(btn=>{
  btn.addEventListener('click', async ()=>{
    const tr = btn.closest('tr'); const id = tr?.dataset?.id; if(!id) return;
    if(!confirm('ยืนยันการลบชุดข้อสอบนี้?')) return;
    try{
      const res = await fetch('my_exams.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({action:'delete',id,csrf:CSRF})
      });
      const data = await res.json();
      if(!data.success) throw new Error(data.error||'ลบไม่สำเร็จ');
      tr.remove();
    }catch(e){ alert('❌ '+e.message); }
  });
});
</script>

</body>
</html>
    <?php include 'footer.php';?>
