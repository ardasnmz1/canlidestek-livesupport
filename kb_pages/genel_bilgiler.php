<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$theme = isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : 'light-theme';
$page_title = "Genel Bilgiler - Bilgi Bankası";

// İlgili makaleleri getir
$stmt = $pdo->prepare("SELECT * FROM kb_articles WHERE category_id = 1 ORDER BY views DESC");
$stmt->execute();
$articles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($page_title); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="<?php echo $theme; ?>">
    <div id="kb-container">
        <header>
            <div class="header-left">
                <button id="menu-toggle-button" class="header-button mobile-only-flex">☰</button>
                <h1>Genel Bilgiler</h1>
            </div>
            <div class="header-right">
                <a href="../knowledge_base.php" class="header-button">Bilgi Bankası</a>
                <a href="../index.php" class="header-button">Ana Sayfa</a>
                <button id="theme-toggle-button">Tema Değiştir</button>
                <button class="logout-button-styling" onclick="window.location.href='../logout.php'">Çıkış Yap</button>
            </div>
        </header>

        <main id="kb-main">
            <div class="kb-page-header">
                <div class="page-breadcrumb">
                    <a href="../knowledge_base.php">Bilgi Bankası</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Genel Bilgiler</span>
                </div>
                <div class="page-title">
                    <i class="fas fa-info-circle"></i>
                    <h1>Genel Bilgiler</h1>
                </div>
                <p class="page-description">
                    Sistem hakkında genel bilgiler ve sık sorulan sorular burada bulunmaktadır.
                    Temel kullanım, özellikler ve sistem gereksinimleri hakkında detaylı bilgiler içerir.
                </p>
            </div>

            <div class="kb-page-content">
                <div class="content-section">
                    <h2><i class="fas fa-info-circle"></i> Temel Bilgiler</h2>
                    <div class="info-cards">
                        <div class="info-card">
                            <i class="fas fa-question-circle"></i>
                            <h3>Sistem Nedir?</h3>
                            <p>Canlı destek sistemi hakkında temel bilgiler</p>
                            <a href="#" class="info-link">Detaylı Bilgi</a>
                        </div>
                        <div class="info-card">
                            <i class="fas fa-tools"></i>
                            <h3>Nasıl Kullanılır?</h3>
                            <p>Sistemin temel kullanım rehberi</p>
                            <a href="#" class="info-link">Detaylı Bilgi</a>
                        </div>
                        <div class="info-card">
                            <i class="fas fa-laptop"></i>
                            <h3>Sistem Gereksinimleri</h3>
                            <p>Önerilen tarayıcı ve sistem gereksinimleri</p>
                            <a href="#" class="info-link">Detaylı Bilgi</a>
                        </div>
                    </div>
                </div>

                <div class="content-section">
                    <h2><i class="fas fa-book"></i> İlgili Makaleler</h2>
                    <div class="article-grid">
                        <?php foreach ($articles as $article): ?>
                        <a href="../kb_article.php?id=<?php echo $article['id']; ?>" class="article-card">
                            <div class="article-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h3><?php echo escape($article['title']); ?></h3>
                            <p><?php echo mb_substr(strip_tags($article['content']), 0, 150) . '...'; ?></p>
                            <div class="article-meta">
                                <span><i class="fas fa-eye"></i> <?php echo $article['views']; ?> görüntülenme</span>
                                <span><i class="fas fa-clock"></i> <?php echo date('d.m.Y', strtotime($article['updated_at'])); ?></span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="content-section">
                    <h2><i class="fas fa-question-circle"></i> Sıkça Sorulan Sorular</h2>
                    <div class="faq-accordion">
                        <div class="faq-item">
                            <div class="faq-question">
                                <i class="fas fa-plus"></i>
                                Sistem ne işe yarar?
                            </div>
                            <div class="faq-answer">
                                Canlı destek sistemi, öğrencilerin öğretmenlerle ve yöneticilerle doğrudan iletişim kurabildiği,
                                sorularına hızlı yanıt alabildikleri ve dosya paylaşabildikleri bir platformdur.
                            </div>
                        </div>
                        <div class="faq-item">
                            <div class="faq-question">
                                <i class="fas fa-plus"></i>
                                Kimler kullanabilir?
                            </div>
                            <div class="faq-answer">
                                Sistemi okul yöneticileri, öğretmenler ve öğrenciler kullanabilir. Her kullanıcı grubu
                                için özel olarak tanımlanmış yetkiler ve özellikler bulunmaktadır.
                            </div>
                        </div>
                        <div class="faq-item">
                            <div class="faq-question">
                                <i class="fas fa-plus"></i>
                                Teknik destek nasıl alabilirim?
                            </div>
                            <div class="faq-answer">
                                Teknik destek için "Destek Talepleri" bölümünden yeni bir talep oluşturabilir veya
                                sistem yöneticileriyle doğrudan iletişime geçebilirsiniz.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Tema değiştirme
        document.getElementById('theme-toggle-button').addEventListener('click', function() {
            document.body.classList.toggle('dark-theme');
            localStorage.setItem('theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
        });

        // SSS Akordiyon
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const answer = question.nextElementSibling;
                const icon = question.querySelector('i');
                
                // Tüm diğer cevapları kapat
                document.querySelectorAll('.faq-answer').forEach(item => {
                    if (item !== answer) {
                        item.style.maxHeight = null;
                        item.previousElementSibling.querySelector('i').classList.replace('fa-minus', 'fa-plus');
                    }
                });

                // Seçili cevabı aç/kapat
                if (answer.style.maxHeight) {
                    answer.style.maxHeight = null;
                    icon.classList.replace('fa-minus', 'fa-plus');
                } else {
                    answer.style.maxHeight = answer.scrollHeight + "px";
                    icon.classList.replace('fa-plus', 'fa-minus');
                }
            });
        });
    </script>
</body>
</html> 