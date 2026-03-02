<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$theme = isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : 'light-theme';
$page_title = "Destek Talepleri - Bilgi Bankası";

// İlgili makaleleri getir
$stmt = $pdo->prepare("SELECT * FROM kb_articles WHERE category_id = 3 ORDER BY views DESC");
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
                <h1>Destek Talepleri</h1>
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
                    <span>Destek Talepleri</span>
                </div>
                <div class="page-title">
                    <i class="fas fa-headset"></i>
                    <h1>Destek Talepleri</h1>
                </div>
                <p class="page-description">
                    Destek talebi oluşturma ve yönetimi hakkında detaylı bilgiler bulabilirsiniz. 
                    Sistem üzerinden nasıl destek talebi oluşturacağınızı, mevcut taleplerinizi nasıl 
                    takip edeceğinizi ve diğer önemli konuları bu bölümde öğrenebilirsiniz.
                </p>
            </div>

            <div class="kb-page-content">
                <div class="content-section">
                    <h2><i class="fas fa-info-circle"></i> Genel Bilgiler</h2>
                    <div class="info-cards">
                        <div class="info-card">
                            <i class="fas fa-plus-circle"></i>
                            <h3>Talep Oluşturma</h3>
                            <p>Yeni bir destek talebi nasıl oluşturulur?</p>
                            <a href="#" class="info-link">Detaylı Bilgi</a>
                        </div>
                        <div class="info-card">
                            <i class="fas fa-tasks"></i>
                            <h3>Talep Takibi</h3>
                            <p>Mevcut taleplerinizi nasıl takip edebilirsiniz?</p>
                            <a href="#" class="info-link">Detaylı Bilgi</a>
                        </div>
                        <div class="info-card">
                            <i class="fas fa-clock"></i>
                            <h3>Yanıt Süreleri</h3>
                            <p>Taleplere ne kadar sürede yanıt verilir?</p>
                            <a href="#" class="info-link">Detaylı Bilgi</a>
                        </div>
                    </div>
                </div>

                <div class="content-section">
                    <h2><i class="fas fa-book"></i> İlgili Makaleler</h2>
                    <div class="article-grid">
                        <?php foreach ($articles as $article): ?>
                        <a href="../kb_article_blog.php?id=<?php echo $article['id']; ?>" class="article-card">
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
                                Destek talebimi nasıl oluştururum?
                            </div>
                            <div class="faq-answer">
                                Ana sayfada bulunan "Destek Talebi Oluştur" butonuna tıklayarak yeni bir talep oluşturabilirsiniz.
                                Formu doldurduktan sonra talebiniz sistem yöneticilerine iletilecektir.
                            </div>
                        </div>
                        <div class="faq-item">
                            <div class="faq-question">
                                <i class="fas fa-plus"></i>
                                Talebime ne zaman yanıt alabilirim?
                            </div>
                            <div class="faq-answer">
                                Destek taleplerine genellikle 24 saat içerisinde yanıt verilmektedir. 
                                Acil durumlarda bu süre daha kısa olabilir.
                            </div>
                        </div>
                        <div class="faq-item">
                            <div class="faq-question">
                                <i class="fas fa-plus"></i>
                                Kapalı bir talebi tekrar açabilir miyim?
                            </div>
                            <div class="faq-answer">
                                Kapatılan bir talebi 7 gün içerisinde tekrar açabilirsiniz. 
                                Bunun için talep detay sayfasındaki "Talebi Tekrar Aç" butonunu kullanabilirsiniz.
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