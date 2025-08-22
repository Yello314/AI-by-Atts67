<?php
require_once __DIR__ . '/../config.php';
requireAdmin();
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

function fetchExamByIdAdmin(PDO $pdo, int $id) {
  $st = $pdo->prepare("
    SELECT e.*, u.username
    FROM saved_exams e
    LEFT JOIN users u ON u.id = e.user_id
    WHERE e.id = :id
  ");
  $st->execute([':id'=>$id]);
  return $st->fetch();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'export' && $method === 'GET') {
  $id = (int)($_GET['id'] ?? 0);
  $type = $_GET['type'] ?? 'aiken';
  $exam = fetchExamByIdAdmin($pdo, $id);
  if (!$exam) { http_response_code(404); echo "Not found"; exit; }

  $questions = json_decode($exam['questions'], true) ?: [];
  $filenameBase = preg_replace('/[^a-zA-Z0-9ก-๙_\-]+/u','_', $exam['title'] ?: ('exam_'.$id));

  if ($type === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filenameBase.'.json"');
    echo json_encode([
      'id'         => (int)$exam['id'],
      'owner_id'   => (int)$exam['user_id'],
      'owner_name' => $exam['username'],
      'title'      => $exam['title'],
      'topic'      => $exam['topic'],
      'difficulty' => $exam['difficulty'],
      'exam_type'  => $exam['exam_type'],
      'created_at' => $exam['created_at'],
      'questions'  => $questions
    ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
  }

  $out = '';
  foreach ($questions as $i=>$q) {
    $qt = trim((string)($q['question'] ?? ''));
    $out .= ($i+1).". ".$qt."\n";
    if (!empty($q['options']) && is_array($q['options'])) {
      $keys = array_keys($q['options']);
      $letters = ['A','B','C','D','E','F'];
      foreach ($keys as $k=>$key) {
        $out .= ($letters[$k] ?? chr(65+$k)).". ".(string)$q['options'][$key]."\n";
      }
      $ansKey = (string)($q['answer'] ?? '');
      $ansIdx = array_search($ansKey, $keys, true);
      $ansLetter = $ansIdx!==false ? ($letters[$ansIdx] ?? chr(65+$ansIdx)) : '';
      $out .= "ANSWER: ".$ansLetter."\n\n";
    } else {
      $out .= "A. True\nB. False\n";
      $ansLetter = (strtolower((string)($q['answer'] ?? ''))==='true')?'A':'B';
      $out .= "ANSWER: ".$ansLetter."\n\n";
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

  $exam = fetchExamByIdAdmin($pdo, $id);
  if (!$exam) { echo json_encode(['success'=>false,'error'=>'Not found']); exit; }

  $st = $pdo->prepare("DELETE FROM saved_exams WHERE id = :id");
  $st->execute([':id'=>$id]);

  logAction('exam_deleted', [
    'by_admin' => $_SESSION['username'] ?? 'admin',
    'exam_id'  => $id,
    'owner_id' => (int)$exam['user_id'],
    'owner'    => $exam['username'],
    'title'    => $exam['title']
  ]);

  echo json_encode(['success'=>true]); exit;
}

if ($action === 'rename' && $method === 'POST') {
  header('Content-Type: application/json; charset=utf-8');
  $id = (int)($_POST['id'] ?? 0);
  $title = trim((string)($_POST['title'] ?? ''));
  $token = $_POST['csrf'] ?? '';
  if (!hash_equals($CSRF, $token)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Invalid CSRF']); exit; }
  if ($title==='') { echo json_encode(['success'=>false,'error'=>'กรุณากรอกชื่อ']); exit; }

  $exam = fetchExamByIdAdmin($pdo, $id);
  if (!$exam) { echo json_encode(['success'=>false,'error'=>'Not found']); exit; }

  $st = $pdo->prepare("UPDATE saved_exams SET title = :t WHERE id = :id");
  $st->execute([':t'=>$title, ':id'=>$id]);

  logAction('exam_renamed', [
    'by_admin' => $_SESSION['username'] ?? 'admin',
    'exam_id'  => $id,
    'owner_id' => (int)$exam['user_id'],
    'owner'    => $exam['username'],
    'old'      => $exam['title'],
    'new'      => $title
  ]);

  echo json_encode(['success'=>true,'title'=>$title]); exit;
}

$keyword    = trim((string)($_GET['q'] ?? ''));
$difficulty = trim((string)($_GET['d'] ?? ''));

$params = [];
$sql = "
  SELECT e.*, u.username
  FROM saved_exams e
  LEFT JOIN users u ON u.id = e.user_id
  WHERE 1=1
";
if ($keyword !== '') {
  $sql .= " AND (e.title LIKE :kw OR e.topic LIKE :kw OR u.username LIKE :kw) ";
  $params[':kw'] = '%'.$keyword.'%';
}
if ($difficulty !== '') {
  $sql .= " AND e.difficulty = :d ";
  $params[':d'] = $difficulty;
}
$sql .= " ORDER BY e.created_at DESC, e.id DESC LIMIT 300";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <title>จัดการข้อสอบ (Admin) - <?php echo APP_NAME; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root { --bg:#f5f6fa; --card:#fff; --border:#e9ecf2; --text:#333; --muted:#666; --primary:#667eea; --warn:#ff9800; --danger:#f44336; }
    * { box-sizing: border-box; }
    body { margin:0; font-family:"Segoe UI", Tahoma, sans-serif; background:var(--bg); color:var(--text); }
    .container { max-width: 1200px; margin: 24px auto; background: var(--card); border:1px solid var(--border); border-radius: 12px; padding: 20px; }
    .topbar { display:flex; justify-content: space-between; align-items:center; margin-bottom: 8px; }
    .topbar a { color: var(--primary); text-decoration:none; }
    .topbar a:hover { text-decoration:underline; }
    .header { display:flex; justify-content: space-between; align-items:center; gap:12px; margin-bottom: 16px; flex-wrap:wrap; }
    h1 { font-size: 22px; margin:0; }
    .search { display:flex; gap:8px; flex-wrap:wrap; }
    .search input, .search select { padding:10px 12px; border:1px solid var(--border); border-radius:8px; }
    .search button { padding:10px 14px; border:none; background:var(--primary); color:#fff; border-radius:8px; cursor:pointer; }
    table { width:100%; border-collapse: collapse; }
    th, td { padding:12px; border-bottom:1px solid var(--border); text-align:left; vertical-align: top; }
    th { background:#fafbff; color:#555; font-weight:600; }
    .badge { display:inline-block; padding:3px 8px; border-radius:999px; font-size:12px; border:1px solid var(--border); background:#fff; color:#444; }
    .muted { color: var(--muted); font-size: 12px; }
    .actions { display:flex; gap:8px; flex-wrap:wrap; }
    .btn { padding:8px 10px; border:1px solid var(--border); background:#fff; border-radius:8px; cursor:pointer; font-size: 14px; }
    .btn.primary { background: var(--primary); color:#fff; border-color: transparent; }
    .btn.warn { background: var(--warn); color:#fff; border-color: transparent; }
    .btn.danger { background: var(--danger); color:#fff; border-color: transparent; }
    .empty { padding: 24px; text-align:center; color:#777; }
    /* Modal */
    .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.35); display:none; align-items:center; justify-content:center; padding: 16px; }
    .modal { width: min(900px, 96vw); max-height: 90vh; overflow:auto; background:#fff; border-radius: 12px; border:1px solid var(--border); }
    .modal .head { padding: 16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content: space-between; align-items:center; gap: 10px; }
    .modal .body { padding: 16px 20px; }
    .close { cursor:pointer; font-size: 20px; }
    .question { border-bottom:1px dashed var(--border); padding:10px 0; }
    .question:last-child { border-bottom:none; }
    .q-title { font-weight:600; }
    .q-option { margin-left: 16px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="topbar">
      <div>👤 <?php echo htmlspecialchars($_SESSION['username']); ?> (Admin) | <a href="Dashboard.php">ย้อนกลับ</a></div>
      <div><a href="../activities.php">กิจกรรม</a> | <a href="../logout.php">ออกจากระบบ</a></div>
    </div>

    <div class="header">
      <h1>🛠️ จัดการข้อสอบทั้งหมด</h1>
      <form class="search" method="get" action="">
        <input type="text" name="q" placeholder="ค้นหาชื่อชุด/หัวข้อ/ผู้ใช้..." value="<?php echo htmlspecialchars($keyword); ?>">
        <select name="d">
          <option value="">ระดับทั้งหมด</option>
          <?php $ds = ['easy'=>'ง่าย','medium'=>'ปานกลาง','hard'=>'ยาก','mixed'=>'ผสม']; 
          foreach ($ds as $k=>$v): ?>
            <option value="<?php echo $k; ?>" <?php if ($difficulty===$k) echo 'selected'; ?>><?php echo $v; ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit">ค้นหา</button>
      </form>
    </div>

    <?php if (!$rows): ?>
      <div class="empty">ยังไม่มีชุดข้อสอบในระบบ</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>ชื่อชุดข้อสอบ</th>
            <th>รายละเอียด</th>
            <th>เจ้าของ</th>
            <th>วันที่บันทึก</th>
            <th>การทำงาน</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr data-id="<?php echo (int)$r['id']; ?>">
              <td>
                <div style="font-weight:600;"><?php echo htmlspecialchars($r['title']); ?></div>
                <div class="muted">ID: <?php echo (int)$r['id']; ?></div>
              </td>
              <td>
                <div><span class="badge">เรื่อง: <?php echo htmlspecialchars($r['topic'] ?? '-'); ?></span></div>
                <div style="margin-top:6px;">
                  <span class="badge">ระดับ: <?php echo htmlspecialchars($r['difficulty'] ?? '-'); ?></span>
                  <span class="badge">รูปแบบ: <?php echo htmlspecialchars($r['exam_type'] ?? '-'); ?></span>
                </div>
              </td>
              <td>
                <div><?php echo htmlspecialchars($r['username'] ?? '(unknown)'); ?></div>
                <div class="muted">UID: <?php echo (int)$r['user_id']; ?></div>
              </td>
              <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($r['created_at']))); ?></td>
              <td>
                <div class="actions">
                  <button class="btn primary btn-view" type="button">ดู</button>
                  <button class="btn btn-rename" type="button">เปลี่ยนชื่อ</button>
                  <a class="btn" href="exams.php?action=export&type=aiken&id=<?php echo (int)$r['id']; ?>">Aiken</a>
                  <a class="btn" href="exams.php?action=export&type=json&id=<?php echo (int)$r['id']; ?>">JSON</a>
                  <button class="btn danger btn-delete" type="button">ลบ</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- Modal -->
  <div id="modal" class="modal-backdrop">
    <div class="modal">
      <div class="head">
        <div id="m-title">รายละเอียดข้อสอบ</div>
        <div class="close" id="m-close">✕</div>
      </div>
      <div class="body" id="m-body"></div>
    </div>
  </div>

  <script>
    const CSRF = <?php echo json_encode($CSRF); ?>;
    const $ = (s,r=document)=>r.querySelector(s);
    const $$ = (s,r=document)=>Array.from(r.querySelectorAll(s));

    // Modal
    const modal=$('#modal'), mClose=$('#m-close');
    mClose.addEventListener('click',()=>modal.style.display='none');
    modal.addEventListener('click',e=>{ if(e.target===modal) modal.style.display='none'; });

    $$('.btn-view').forEach(btn=>{
      btn.addEventListener('click', async ()=>{
        const tr = btn.closest('tr'); const id = tr?.dataset?.id; if(!id) return;
        try{
          const res = await fetch(`exams.php?action=export&type=json&id=${encodeURIComponent(id)}`);
          if(!res.ok) throw new Error('โหลดข้อมูลไม่สำเร็จ');
          const data = await res.json();

          $('#m-title').textContent = `${data.title || 'รายละเอียดข้อสอบ'} — โดย ${data.owner_name ?? 'unknown'}`;
          const body = $('#m-body'); body.innerHTML='';

          (data.questions||[]).forEach((q,i)=>{
            const div=document.createElement('div'); div.className='question';
            const t=document.createElement('div'); t.className='q-title';
            t.textContent=`ข้อ ${i+1}. ${q.question||''}`; div.appendChild(t);

            if(q.options){
              Object.entries(q.options).forEach(([k,v],idx)=>{
                const p=document.createElement('div'); p.className='q-option';
                p.textContent=`${String.fromCharCode(65+idx)}. ${v}`; div.appendChild(p);
              });
              const keys=Object.keys(q.options);
              const ansKey=String(q.answer??''); const ansIdx=keys.indexOf(ansKey);
              const ansLetter=ansIdx>=0?String.fromCharCode(65+ansIdx):'';
              const a=document.createElement('div'); a.style.marginTop='6px';
              a.innerHTML=`<strong>เฉลย:</strong> ${ansLetter} ${q.options[ansKey]?'— '+q.options[ansKey]:''}`;
              div.appendChild(a);
            } else {
              const t1=document.createElement('div'); t1.className='q-option'; t1.textContent='A. True';
              const t2=document.createElement('div'); t2.className='q-option'; t2.textContent='B. False';
              div.appendChild(t1); div.appendChild(t2);
              const a=document.createElement('div'); a.style.marginTop='6px';
              a.innerHTML=`<strong>เฉลย:</strong> ${String(q.answer).toLowerCase()==='true'?'A':'B'}`;
              div.appendChild(a);
            }
            body.appendChild(div);
          });

          modal.style.display='flex';
        }catch(e){ alert('❌ '+e.message); }
      });
    });

    $$('.btn-rename').forEach(btn=>{
      btn.addEventListener('click', async ()=>{
        const tr=btn.closest('tr'); const id=tr?.dataset?.id; if(!id) return;
        const cur=tr.querySelector('td > div').textContent.trim();
        const title=prompt('ตั้งชื่อชุดข้อสอบใหม่:', cur);
        if(!title) return;
        try{
          const res=await fetch('exams.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:new URLSearchParams({action:'rename',id,title,csrf:CSRF})});
          const data=await res.json();
          if(!data.success) throw new Error(data.error||'เปลี่ยนชื่อไม่สำเร็จ');
          tr.querySelector('td > div').textContent=data.title;
        }catch(e){ alert('❌ '+e.message); }
      });
    });

    $$('.btn-delete').forEach(btn=>{
      btn.addEventListener('click', async ()=>{
        const tr=btn.closest('tr'); const id=tr?.dataset?.id; if(!id) return;
        if(!confirm('ยืนยันการลบชุดข้อสอบนี้?')) return;
        try{
          const res=await fetch('exams.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:new URLSearchParams({action:'delete',id,csrf:CSRF})});
          const data=await res.json();
          if(!data.success) throw new Error(data.error||'ลบไม่สำเร็จ');
          tr.remove();
        }catch(e){ alert('❌ '+e.message); }
      });
    });
  </script>
</body>
</html>
