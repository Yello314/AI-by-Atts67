<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer สวยงาม</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Prompt', sans-serif;
            line-height: 1.6;
        }

        .main-content {
            min-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 40px 20px;
        }

        .main-content h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .register-link {
            color: #FFD700;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .register-link:hover {
            color: #FFF;
            text-shadow: 0 0 10px #FFD700;
        }

        footer {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            font-family: 'Prompt', sans-serif;
        }

        .contact-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            padding: 40px 20px;
            background: rgba(255, 255, 255, 0.95);
            color: #333;
        }

        .contact-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 5px solid transparent;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }

        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        .contact-card.phone:hover {
            border-left-color: #27ae60;
        }

        .contact-card.line:hover {
            border-left-color: #00C300;
        }

        .contact-card.facebook:hover {
            border-left-color: #1877F2;
        }

        .contact-card.instagram:hover {
            border-left-color: #E4405F;
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            object-fit: cover;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .contact-info strong {
            font-size: 16px;
            color: #2c3e50;
            display: block;
            margin-bottom: 5px;
        }

        .contact-id {
            color: #7f8c8d;
            font-size: 14px;
            font-weight: 500;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            padding: 50px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-section h3 {
            color: #FFD700;
            font-size: 1.4rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-section h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #FFD700, #FFA500);
            border-radius: 2px;
        }

        .footer-section p {
            color: #ecf0f1;
            margin-bottom: 15px;
            text-align: justify;
        }

        .footer-section em {
            color: #FFD700;
            font-style: italic;
            display: block;
            margin-top: 15px;
            font-size: 1.1rem;
        }

        .vision-box {
            background: rgba(255, 215, 0, 0.1);
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #FFD700;
            margin: 20px 0;
        }

        .vision-box em {
            margin: 0;
            font-size: 1rem;
        }

        .features-highlight {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .features-highlight span {
            background: rgba(255, 255, 255, 0.1);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #ecf0f1;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }

        .contact-icon-text {
            font-size: 1.2rem;
            width: 25px;
            text-align: center;
        }

        .contact-item strong {
            color: #FFD700;
            font-size: 0.9rem;
            display: block;
            margin-bottom: 3px;
        }

        .contact-item p {
            margin: 0;
            color: #bdc3c7;
            font-size: 0.9rem;
        }

        .user-groups {
            margin-bottom: 25px;
        }

        .user-category {
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border-left: 3px solid #3498db;
        }

        .user-category h4 {
            color: #FFD700;
            font-size: 1rem;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .user-category p {
            margin: 0;
            color: #bdc3c7;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .education-levels {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
        }

        .level-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .level-tag {
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .contact-details p {
            margin-bottom: 10px;
            color: #bdc3c7;
        }

        .contact-details strong {
            color: #ecf0f1;
        }

        .services-list {
            list-style: none;
            padding: 0;
        }

        .services-list li {
            color: #bdc3c7;
            margin-bottom: 12px;
            padding-left: 25px;
            position: relative;
        }

        .services-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #27ae60;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .footer-bottom {
            background: rgba(0, 0, 0, 0.3);
            color: #bdc3c7;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-bottom strong {
            color: #FFD700;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .contact-section {
                grid-template-columns: 1fr;
                padding: 30px 15px;
            }

            .footer-content {
                grid-template-columns: 1fr;
                padding: 30px 15px;
            }

            .main-content h1 {
                font-size: 2rem;
            }

            .contact-card {
                padding: 15px;
            }

            .contact-icon {
                width: 40px;
                height: 40px;
            }

            .features-highlight {
                justify-content: center;
            }

            .features-highlight span {
                font-size: 0.8rem;
                padding: 4px 8px;
            }

            .level-tags {
                justify-content: center;
            }

            .user-category {
                padding: 12px;
            }

            .contact-item {
                padding: 8px;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .contact-card, .footer-section {
            animation: fadeInUp 0.6s ease forwards;
        }

        .contact-card:nth-child(1) { animation-delay: 0.1s; }
        .contact-card:nth-child(2) { animation-delay: 0.2s; }
        .contact-card:nth-child(3) { animation-delay: 0.3s; }
        .contact-card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body>

    <!-- Footer -->
    <footer>
        <!-- ส่วนการติดต่อด้วยไอคอน -->
        <div class="contact-section">
            <a href="tel:0982861905" class="contact-card phone">
                <img src="https://cdn-icons-png.flaticon.com/512/724/724664.png" alt="Phone" class="contact-icon">
                <div class="contact-info">
                    <strong>ติดต่อสอบถาม<br>ได้ 24 ชม.</strong>
                    <div class="contact-id">0982861905</div>
                </div>
            </a>

            <a href="https://line.me/ti/p/@adulwas48" target="_blank" rel="noopener noreferrer" class="contact-card line">
                <img src="https://static.vecteezy.com/system/resources/previews/016/716/473/large_2x/line-icon-free-png.png" alt="Line" class="contact-icon">
                <div class="contact-info">
                    <strong>สอบถามข้อมูลและ<br>สอบถามได้ทางไลน์</strong>
                    <div class="contact-id">@adulwas48</div>
                </div>
            </a>

            <a href="https://www.facebook.com/phol.adulwas" target="_blank" rel="noopener noreferrer" class="contact-card facebook">
                <img src="https://upload.wikimedia.org/wikipedia/commons/5/51/Facebook_f_logo_%282019%29.svg" alt="Facebook" class="contact-icon">
                <div class="contact-info">
                    <strong>สอบถามข้อมูลและ<br>ติดตามการพัฒนาได้ทางเฟซบุ๊ก</strong>
                    <div class="contact-id">Phol Adulwas</div>
                </div>
            </a>

            <a href="https://www.instagram.com/phol_mubmib" target="_blank" rel="noopener noreferrer" class="contact-card instagram">
                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Instagram_icon.png" alt="Instagram" class="contact-icon">
                <div class="contact-info">
                    <strong>ติดตามข้อมูลข่าวสารได้ที่<br>Instagram:</strong>
                    <div class="contact-id">Phol_MubMib</div>
                </div>
            </a>
        </div>

        <!-- เนื้อหาหลักของ Footer -->
        <div class="footer-content">
            <div class="footer-section">
                <h3>เกี่ยวกับเรา</h3>
                <p>
                    นักเรียนจ่าอากาศเหล่า สื่อสารคอมพิวเตอร์ ชั้นปีที่ 2 ร่วมกันพัฒนาเว็บไซต์ที่ใช้ AI ในการสร้างข้อสอบอัตโนมัติ 
                    เพื่อประเมินศักยภาพบุคลากรภายในกองทัพอากาศและเพิ่มประสิทธิภาพการประเมินผล
                </p>
                <div class="vision-box">
                    <em>"สอบง่าย ครอบคลุม เน้นความเข้าใจ ไม่ใช่ท่องจำ"</em>
                </div>
                <div class="features-highlight">
                    <span>🤖 AI Technology</span>
                    <span>📊 Smart Analytics</span>
                    <span>🎯 Precise Assessment</span>
                </div>
            </div>

            <div class="footer-section">
                <h3>ติดต่อเรา</h3>
                <div class="contact-details">
                    <div class="contact-item">
                        <span class="contact-icon-text">📞</span>
                        <div>
                            <strong>โทรศัพท์</strong>
                            <p>0982861905</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon-text">💬</span>
                        <div>
                            <strong>Line Official</strong>
                            <p>@adulwas48</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon-text">✉️</span>
                        <div>
                            <strong>อีเมล</strong>
                            <p>k.phol554@gmail.com</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon-text">📍</span>
                        <div>
                            <strong>ที่อยู่</strong>
                            <p>171/1 ถ. พหลโยธิน แขวงสนามบิน<br>กรุงเทพมหานคร 10210</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-section">
                <h3>กลุ่มผู้ใช้งาน</h3>
                <div class="user-groups">
                    <div class="user-category">
                        <h4>👥 บุคคลทั่วไป</h4>
                        <p>เหมาะสำหรับการทดสอบความรู้เบื้องต้น</p>
                    </div>
                    <div class="user-category">
                        <h4>👨‍🏫 ครูอาจารย์</h4>
                        <p>สร้างข้อสอบสำหรับนักเรียนได้อย่างง่ายดาย</p>
                    </div>
                    <div class="user-category">
                        <h4>👨‍🎓 นักศึกษา</h4>
                        <p>ทดสอบความรู้และเตรียมสอบ</p>
                    </div>
                </div>
                <div class="education-levels">
                    <h4 style="color: #FFD700; margin: 20px 0 10px 0;">ระดับการศึกษา</h4>
                    <div class="level-tags">
                        <span class="level-tag">🏫 โรงเรียน</span>
                        <span class="level-tag">🏛️ มหาวิทยาลัย</span>
                        <span class="level-tag">🏠 ใช้ที่บ้าน</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="footer-bottom">
            Copyright 2025 © <strong>ATTS67</strong> - พัฒนาด้วยความใส่ใจเพื่อการศึกษา
        </div>
    </footer>
</body>
</html>