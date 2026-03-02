<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$theme = isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : 'light-theme';
$page_title = "Hesap İşlemleri - Bilgi Bankası";

// İlgili makaleleri getir
$stmt = $pdo->prepare("SELECT * FROM kb_articles WHERE category_id = 4 ORDER BY views DESC");
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
                <h1>Hesap İşlemleri</h1>
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
                    <span>Hesap İşlemleri</span>
                </div>
                <div class="page-title">
                    <i class="fas fa-user-cog"></i>
                    <h1>Hesap İşlemleri</h1>
                </div>
                <p class="page-description">
                    Hesap oluşturma, şifre değiştirme ve profil yönetimi hakkında bilgiler burada bulunmaktadır.
                    Hesabınızla ilgili tüm işlemleri güvenle gerçekleştirebilirsiniz.
                </p>
            </div>

            <div class="kb-page-content">
                <div class="content-section">
                    <h2><i class="fas fa-user-plus"></i> Hesap Yönetimi</h2>
                    <div class="info-cards">
                        <div class="info-card">
                            <i class="fas fa-user-edit"></i>
                            <h3>Profil Düzenleme</h3>
                            <p>Profil bilgilerini güncelleme</p>
                            <a href="#" class="info-link">Detaylı Bilgi</a>
                        </div>
                        <div class="info-card">
                            <i class="fas fa-key"></i>
                            <h3>Şifre İşlemleri</h3>
                            <p>Şifre değiştirme ve sıfırlama</p>
                            <a href="#" class="info-link">Detaylı Bilgi</a>
                        </div>
                        <div class="info-card">
                            <i class="fas fa-envelope"></i>
                            <h3>E-posta Ayarları</h3>
                            <p>E-posta tercihleri yönetimi</p>
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
                                Profil fotoğrafımı nasıl değiştirebilirim?
                            </div>
                            <div class="faq-answer">
                                Profil fotoğrafınızı değiştirmek için profil sayfanızdaki fotoğraf bölümüne tıklayın ve
                                yeni bir fotoğraf yükleyin. Desteklenen formatlar: JPG, PNG ve GIF.
                            </div>
                        </div>
                        <div class="faq-item">
                            <div class="faq-question">
                                <i class="fas fa-plus"></i>
                                Şifremi unuttum ne yapmalıyım?
                            </div>
                            <div class="faq-answer">
                                Giriş sayfasındaki "Şifremi Unuttum" bağlantısını kullanarak şifre sıfırlama talimatlarını
                                e-posta adresinize gönderebilirsiniz. E-postadaki bağlantıyı takip ederek yeni şifrenizi oluşturabilirsiniz.
                            </div>
                        </div>
                        <div class="faq-item">
                            <div class="faq-question">
                                <i class="fas fa-plus"></i>
                                E-posta adresimi nasıl güncelleyebilirim?
                            </div>
                            <div class="faq-answer">
                                E-posta adresinizi güncellemek için profil ayarlarına gidin ve "E-posta Değiştir" seçeneğini kullanın.
                                Güvenlik nedeniyle, değişikliği onaylamak için mevcut e-posta adresinize bir doğrulama kodu gönderilecektir.
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