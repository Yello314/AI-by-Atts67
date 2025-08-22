<?php
require_once __DIR__ . '/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ระบบออกข้อสอบ AI - <?php echo APP_NAME; ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

   <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{
      font-family:'Prompt','Kanit',sans-serif;
      background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
      min-height:100vh;padding:20px;
    }
    body::before{
      content:'';position:fixed;inset:0;pointer-events:none;z-index:1;
      background:url('data:image/svg+xml,<svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%239C92AC" fill-opacity="0.05"><path d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/></g></g></svg>');
    }
    .container{max-width:1000px;margin:0 auto;position:relative;z-index:2}
    .main-card{
      background:rgba(255,255,255,.95);backdrop-filter:blur(20px);
      border-radius:30px;box-shadow:0 25px 50px -12px rgba(0,0,0,.25);overflow:hidden;
      animation:slideUp .5s ease-out;
    }
    @keyframes slideUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
    .user-header{
      background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
      padding:25px 30px;display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;
    }
    .user-info{display:flex;align-items:center;gap:15px;color:#fff;font-size:16px;font-weight:500}
    .user-info .badge{background:rgba(255,255,255,.2);padding:5px 12px;border-radius:20px;font-size:12px;backdrop-filter:blur(10px)}
    .header-buttons{display:flex;gap:10px;flex-wrap:wrap}
    .header-buttons a{
      padding:10px 20px;background:rgba(255,255,255,.2);color:#fff;text-decoration:none;border-radius:12px;
      font-weight:500;font-size:14px;backdrop-filter:blur(10px);transition:all .3s;border:1px solid rgba(255,255,255,.3)
    }
    .header-buttons a:hover{background:rgba(255,255,255,.3);transform:translateY(-2px);box-shadow:0 10px 20px rgba(0,0,0,.2)}
    .header-buttons a.logout{background:rgba(244,67,54,.9);border-color:rgba(244,67,54,.5)}
    .header-buttons a.logout:hover{background:rgba(244,67,54,1)}
    .content{padding:40px}
    h1{font-size:32px;font-weight:700;color:#1a202c;margin-bottom:10px;display:flex;align-items:center;gap:10px}
    .subtitle{color:#718096;font-size:16px;margin-bottom:30px}
    .form-card{background:linear-gradient(135deg,#f6f8fb 0%,#f1f4f9 100%);border-radius:20px;padding:30px;margin-bottom:30px;border:1px solid #e2e8f0}
    .form-group{margin-bottom:25px}
    label{display:block;font-weight:600;color:#2d3748;margin-bottom:8px;font-size:14px;text-transform:uppercase;letter-spacing:.5px}
    input[type="text"],input[type="number"],select{
      width:100%;padding:12px 16px;border:2px solid #e2e8f0;border-radius:12px;font-size:16px;
      font-family:'Prompt',sans-serif;transition:all .3s;background:#fff
    }
    input:focus,select:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.1);transform:translateY(-2px)}
    select{cursor:pointer;appearance:none;background-image:url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27currentColor%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3e%3cpolyline points=%276 9 12 15 18 9%27%3e%3c/polyline%3e%3c/svg%3e');background-repeat:no-repeat;background-position:right 12px center;background-size:20px;padding-right:40px}
    .btn-primary{
      width:100%;padding:14px 24px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;border:none;border-radius:12px;
      font-size:16px;font-weight:600;cursor:pointer;transition:all .3s;text-transform:uppercase;letter-spacing:1px;position:relative;overflow:hidden
    }
    .btn-primary::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.3),transparent);transition:left .5s}
    .btn-primary:hover::before{left:100%}
    .btn-primary:hover{transform:translateY(-2px);box-shadow:0 15px 30px rgba(102,126,234,.4)}
    .loading{display:inline-block;width:20px;height:20px;border:3px solid rgba(255,255,255,.3);border-radius:50%;border-top-color:#fff;animation:spin 1s linear infinite;margin-left:10px}
    @keyframes spin{to{transform:rotate(360deg)}}
    #quizForm{animation:fadeIn .5s ease-out}
    @keyframes fadeIn{from{opacity:0}to{opacity:1}}

    .question{background:#fff;border-radius:16px;padding:25px;margin-bottom:20px;border:2px solid #e2e8f0;transition:all .3s;position:relative;overflow:hidden}
    .question::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:linear-gradient(180deg,#667eea,#764ba2)}
    .question:hover{transform:translateX(5px);box-shadow:0 10px 25px rgba(0,0,0,.1)}
    .question p{margin:10px 0;line-height:1.8;color:#2d3748}
    .question strong{color:#1a202c;font-size:18px}
    .options{margin:20px 0 20px 20px}
    .options label{display:flex;align-items:center;padding:12px 16px;margin:10px 0;background:#f7fafc;border:2px solid #e2e8f0;border-radius:10px;cursor:pointer;transition:all .3s}
    .options label:hover{background:#edf2f7;border-color:#667eea;transform:translateX(5px)}
    .options input[type="radio"]{width:20px;height:20px;margin-right:12px;accent-color:#667eea}
    .answerKey{margin-top:20px;padding:15px;background:linear-gradient(135deg,#48bb78 0%,#38a169 100%);color:#fff;border-radius:10px;font-weight:500}
    .buttons{display:flex;gap:15px;flex-wrap:wrap;margin-top:30px}
    .buttons button{flex:1;min-width:150px;padding:12px 24px;border:none;border-radius:12px;font-size:14px;font-weight:600;cursor:pointer;transition:all .3s;text-transform:uppercase;letter-spacing:.5px}
    .btn-success{background:linear-gradient(135deg,#48bb78 0%,#38a169 100%);color:#fff}
    .btn-info{background:linear-gradient(135deg,#4299e1 0%,#3182ce 100%);color:#fff}
    .btn-secondary{background:linear-gradient(135deg,#718096 0%,#4a5568 100%);color:#fff}
    .btn-warning{background:linear-gradient(135deg,#f6ad55 0%,#ed8936 100%);color:#fff}
    #result{margin-top:30px;padding:20px;background:linear-gradient(135deg,#48bb78 0%,#38a169 100%);color:#fff;border-radius:15px;font-size:18px;font-weight:600;text-align:center;animation:slideIn .5s ease-out}
    @keyframes slideIn{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}
    .alert{padding:15px 20px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px;animation:slideIn .3s ease-out}
    .alert-info{background:linear-gradient(135deg,rgba(66,153,225,.1),rgba(49,130,206,.1));border-left:4px solid #4299e1;color:#2b6cb1}
    .alert-success{background:linear-gradient(135deg,rgba(72,187,120,.1),rgba(56,161,105,.1));border-left:4px solid #48bb78;color:#276749}
    .alert-error{background:linear-gradient(135deg,rgba(245,101,101,.1),rgba(229,62,62,.1));border-left:4px solid #f56565;color:#c53030}
    @media (max-width:768px){
      .content{padding:20px} h1{font-size:24px}
      .user-header{flex-direction:column;text-align:center}
      .buttons{flex-direction:column}.buttons button{width:100%}
    }

@media print {
  @page { size: A4; margin: 12mm 12mm 10mm 12mm; }
  @page :first { margin: 1mm 12mm 8mm 12mm; }  

  html, body { margin:0 !important; }
  #printArea { margin:0 !important; padding:0 !important; }

  .print-header { margin:0 0 4pt 0 !important; padding:0 !important; border:0 !important; }

  html, body {
    margin: 0 !important;
    background: #fff !important;
    font-family: "Angsana New","TH Sarabun New","Tahoma",sans-serif !important;
    font-size: 16pt !important;
    line-height: 1.2 !important;
    color: #000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  body::before { display: none !important; }

  body * { visibility: hidden !important; }
  #printArea, #printArea * { visibility: visible !important; }

  .container, .main-card, .content,
  #printArea {
    background: #fff !important;
    box-shadow: none !important;
    border: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  .user-header, .header-buttons, .form-card, #configForm,
  .buttons, #result, .alert { display: none !important; }

  .print-header {
    display: block !important;
    margin: -3mm 0 4pt 0 !important;  
    padding: 0 !important;
    border: 0 !important;
    font-weight: normal !important;
    line-height: 1.15 !important;
  }

  .question {
    border: 0 !important;
    box-shadow: none !important;
    background: transparent !important;
    padding: 0 !important;
    margin: 0 0 6pt 0 !important;
    page-break-inside: avoid; break-inside: avoid;
  }
  .question::before { display: none !important; }
  .question p { margin: 0 0 4pt 0 !important; }

  .options { margin: 0 0 0 14pt !important; }
  .options label {
    display: block !important;
    margin: 0 0 2pt 0 !important;
    padding: 0 !important;
    border: 0 !important;
    background: none !important;
  }
  .options input[type="radio"] { display: none !important; }

  .answerKey, .loading { display: none !important; }
}
  </style>  

  <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
</head>
<body>
  <div class="container">
    <div class="main-card">
      <div class="user-header">
        <div class="user-info">
          <span>👤 สวัสดี, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
          <?php if (isAdmin()): ?><span class="badge">Admin</span><?php endif; ?>
        </div>
        <div class="header-buttons">
          <?php if (isAdmin()): ?><a href="admin/dashboard.php">🛠️ Admin Panel</a><?php endif; ?>
          <a href="my_exams.php">📚 ข้อสอบของฉัน</a>
          <a href="logout.php" class="logout">ออกจากระบบ</a>
        </div>
      </div>

      <div class="content">
        <h1>🎓 AI สร้างข้อสอบ by Atts</h1>
        <p class="subtitle">สร้างข้อสอบคุณภาพสูงด้วยปัญญาประดิษฐ์ได้ในไม่กี่วินาที</p>

        <div class="form-card">
          <form id="configForm">
            <div class="form-group">
              <label for="topic">หัวข้อเรื่อง:</label>
              <input type="text" id="topic" placeholder="เช่น พื้นฐานคอมพิวเตอร์" required>
            </div>
            <div class="form-group">
              <label for="numQuestions">จำนวนข้อ:</label>
              <input type="number" id="numQuestions" value="5" min="1" max="100">
            </div>
            <div class="form-group">
              <label for="difficulty">ระดับความยาก:</label>
              <select id="difficulty">
                <option value="easy">ง่าย</option>
                <option value="medium" selected>ปานกลาง</option>
                <option value="hard">ยาก</option>
                <option value="mixed">ผสม (ง่าย, ปานกลาง, ยาก)</option>
              </select>
            </div>
            <div class="form-group">
              <label for="examType">รูปแบบข้อสอบ:</label>
              <select id="examType">
                <option value="multiple">ปรนัย</option>
                <option value="truefalse">ถูกหรือผิด</option>
                <option value="combined">ปรนัย + ถูกหรือผิด</option>
              </select>
            </div>
            <button type="submit" class="btn-primary"><span id="btnText">สร้างข้อสอบ</span></button>
          </form>
        </div>

        <form id="quizForm" style="display:none;">
          <div id="printArea">
            <div class="print-header">
              ชื่อ..................................................   ห้อง/ชั้น...............................   วันที่........../........../..........
              <div>หัวข้อ: <strong id="printTopic">-</strong> | จำนวนข้อ: <strong id="printCount">0</strong></div>
            </div>

            <div id="questionsContainer"></div>
          </div>

          <div class="buttons">
            <button id="submitBtn" type="button" class="btn-success">✅ ตรวจคำตอบ</button>
            <button id="saveBtn"   type="button" class="btn-info">💾 บันทึกข้อสอบ</button>
            <button id="printBtn"  type="button" class="btn-secondary">🖨️ พิมพ์</button>
            <button id="exportBtn" type="button" class="btn-warning">📤 Export</button>
          </div>
        </form>

        <div id="result"></div>
      </div>
    </div>
  </div>

  <script>
    const configForm = document.getElementById('configForm');
    const quizForm = document.getElementById('quizForm');
    const questionsContainer = document.getElementById('questionsContainer');
    const submitBtn = document.getElementById('submitBtn');
    const saveBtn = document.getElementById('saveBtn');
    const printBtn = document.getElementById('printBtn');
    const exportBtn = document.getElementById('exportBtn');
    const resultDiv = document.getElementById('result');

    configForm.addEventListener('submit', async e => {
      e.preventDefault();

      const submitButton = configForm.querySelector('button[type="submit"]');
      const btnText = document.getElementById('btnText');
      btnText.innerHTML = 'กำลังสร้างข้อสอบ... <span class="loading"></span>';
      submitButton.disabled = true;

      resultDiv.innerHTML = '<div class="alert alert-info">⏳ กำลังสร้างข้อสอบ... กรุณารอสักครู่</div>';

      const topic = document.getElementById('topic').value.trim();
      const numQuestions = parseInt(document.getElementById('numQuestions').value, 10) || 5;
      const difficulty = document.getElementById('difficulty').value;
      const examType = document.getElementById('examType').value;

      try {
        const res = await fetch('generate_exam.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ topic, numQuestions, difficulty, examType })
        });
        if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
        const data = await res.json();
        if (data.error) throw new Error(data.error);

        window.examConfig = { topic, numQuestions, difficulty, examType };
        window.quizData = data;
        document.getElementById('printTopic').textContent = topic || '-';
        document.getElementById('printCount').textContent = data.length || 0;

        questionsContainer.innerHTML = '';
        data.forEach((q, idx) => {
          const div = document.createElement('div');
          div.className = 'question';

          const pQ = document.createElement('p');
          pQ.innerHTML = `<strong>ข้อ ${idx+1}:</strong> ${q.question}`;
          div.appendChild(pQ);

          const optsDiv = document.createElement('div');
          optsDiv.className = 'options';

          if (q.options) {
            Object.entries(q.options).forEach(([k, v]) => {
              const lb = document.createElement('label');
              const input = document.createElement('input');
              input.type = 'radio';
              input.name = `q${idx}`;
              input.value = k;
              lb.appendChild(input);
              lb.append(` (${k}) ${v}`);
              optsDiv.appendChild(lb);
            });
          } else {
            ['true','false'].forEach(val => {
              const lb = document.createElement('label');
              const input = document.createElement('input');
              input.type = 'radio';
              input.name = `q${idx}`;
              input.value = val;
              lb.appendChild(input);
              lb.append(val === 'true' ? ' ถูกต้อง' : ' ไม่ถูกต้อง');
              optsDiv.appendChild(lb);
            });
          }

          div.appendChild(optsDiv);

          const pA = document.createElement('p');
          pA.className = 'answerKey';
          pA.style.display = 'none';
          const ansText = q.options && q.options[q.answer] ? ` — ${q.options[q.answer]}` : '';
          pA.innerHTML = `<strong>เฉลย:</strong> ${q.answer}${ansText}`;
          div.appendChild(pA);

          questionsContainer.appendChild(div);
        });

        quizForm.style.display = 'block';
        configForm.style.display = 'none';
        resultDiv.innerHTML = '<div class="alert alert-success">✅ สร้างข้อสอบเรียบร้อยแล้ว!</div>';

      } catch (err) {
        resultDiv.innerHTML = `<div class="alert alert-error">❌ ${err.message}</div>`;
      } finally {
        btnText.innerHTML = 'สร้างข้อสอบ';
        submitButton.disabled = false;
      }
    });

    submitBtn.addEventListener('click', () => {
  let score = 0;
  window.quizData.forEach((q, idx) => {
    const sel = document.querySelector(`input[name=q${idx}]:checked`);
    const userAnswer = sel ? sel.value : null;

    const answerEl = document.querySelectorAll('.answerKey')[idx];
    answerEl.style.display = 'block'; // โชว์เฉลยเสมอ

    if (userAnswer === String(q.answer)) {
      score++;
      answerEl.classList.remove('answer-wrong');
      answerEl.classList.add('answer-correct');
    } else {
      answerEl.classList.remove('answer-correct');
      answerEl.classList.add('answer-wrong');
    }
  });

  resultDiv.innerHTML = `🎯 คุณได้คะแนน ${score} / ${window.quizData.length} ข้อ`;
});


    saveBtn.addEventListener('click', async () => {
      const examTitle = prompt('ตั้งชื่อข้อสอบชุดนี้:');
      if (!examTitle) return;
      try {
        const res = await fetch('save_exam.php', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({
            title: examTitle,
            topic: window.examConfig.topic,
            difficulty: window.examConfig.difficulty,
            exam_type: window.examConfig.examType,
            questions: window.quizData
          })
        });
        const data = await res.json();
        if (data.success) alert('✅ บันทึกข้อสอบเรียบร้อยแล้ว!');
        else alert('❌ เกิดข้อผิดพลาด: ' + data.error);
      } catch (err) {
        alert('❌ ไม่สามารถบันทึกข้อสอบได้');
      }
    });

    printBtn.addEventListener('click', () => window.print());

    exportBtn.addEventListener('click', () => {
      let output = '';
      window.quizData.forEach((q, idx) => {
        output += `${q.question}\n`;  
        if (q.options) {
          const keys = Object.keys(q.options);
          const map = ['A','B','C','D'];
          keys.forEach((k,i)=>{ output += `${map[i]}. ${q.options[k]}\n`; });
          const ansIdx = keys.indexOf(q.answer);
          output += `ANSWER: ${map[ansIdx] || ''}\n\n`;
        } else {
          output += `A. True\nB. False\n`;
          output += `ANSWER: ${q.answer === 'true' ? 'A' : 'B'}\n\n`;
        }
      });
      const blob = new Blob([output], {type:'text/plain;charset=utf-8'});
      saveAs(blob, 'questions.txt');
    });
  </script>
</body>
    <?php include 'footer.php';?>
</html>
