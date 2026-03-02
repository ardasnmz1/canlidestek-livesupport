<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

$theme = isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : 'light-theme';

// Kategorileri getir
$stmt = $pdo->query("SELECT * FROM kb_categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

// Her kategori için makale sayısını hesapla
foreach ($categories as &$category) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kb_articles WHERE category_id = ?");
    $stmt->execute([$category['id']]);
    $category['article_count'] = $stmt->fetchColumn();
}

$page_title = "Bilgi Bankası";
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/knowledge_base.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="<?php echo $theme; ?>">
    <div id="kb-container">
        <header>
            <div class="header-left">
                <button id="menu-toggle-button" class="header-button mobile-only-flex">☰</button>
                <h1>Bilgi Bankası</h1>
            </div>
            <div class="header-right">
                <a href="index.php" class="header-button">Ana Sayfa</a>
                <button id="theme-toggle-button">Tema Değiştir</button>
                <button class="logout-button-styling" onclick="window.location.href='logout.php'">Çıkış Yap</button>
            </div>
        </header>

        <main id="kb-main">
            <!-- Hero Bölümü -->
            <section class="kb-hero">
                <h1>Bilgi Bankasına Hoş Geldiniz</h1>
                <p>İhtiyacınız olan tüm bilgiler burada. Hemen aramaya başlayın!</p>
                <div class="kb-search">
                    <input type="text" id="kb-search-input" placeholder="Bilgi bankasında ara...">
                    <button type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </section>

            <!-- Hızlı Erişim Kartları -->
            <section class="quick-access">
                <h2>Hızlı Erişim</h2>
                <div class="quick-access-grid">
                    <a href="kb_pages/destek_talepleri.php" class="quick-access-card">
                        <i class="fas fa-ticket-alt"></i>
                        <h3>Destek Talepleri</h3>
                        <p>Destek talebi oluşturma ve yönetimi</p>
                    </a>
                    <a href="kb_pages/hesap_islemleri.php" class="quick-access-card">
                        <i class="fas fa-user-cog"></i>
                        <h3>Hesap İşlemleri</h3>
                        <p>Hesap güvenliği ve gizlilik ayarları</p>
                    </a>
                    <a href="kb_pages/dosya_paylasimi.php" class="quick-access-card">
                        <i class="fas fa-file-upload"></i>
                        <h3>Dosya Paylaşımı</h3>
                        <p>Dosya yükleme ve paylaşım kuralları</p>
                    </a>
                </div>
            </section>

            <!-- Kategoriler -->
            <section class="kb-categories">
                <h2>Kategoriler</h2>
                <div class="categories-grid">
                    <?php foreach ($categories as $category): ?>
                    <a href="kb_pages/<?php echo strtolower(str_replace(' ', '_', $category['name'])); ?>.php" class="category-card">
                        <div class="category-header">
                            <i class="<?php echo $category['icon'] ?? 'fas fa-folder'; ?>"></i>
                            <h3><?php echo escape($category['name']); ?></h3>
                        </div>
                        <p><?php echo escape($category['description']); ?></p>
                        <div class="category-stats">
                            <span><i class="fas fa-file-alt"></i> <?php echo $category['article_count']; ?> makale</span>
                            <span><i class="fas fa-eye"></i> <?php echo $category['views'] ?? 0; ?> görüntülenme</span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Tema değiştirme
        document.getElementById('theme-toggle-button').addEventListener('click', function() {
            document.body.classList.toggle('dark-theme');
            document.cookie = `theme=${document.body.classList.contains('dark-theme') ? 'dark' : 'light'}; path=/; max-age=31536000`;
        });

        // Arama işlevselliği
        document.getElementById('kb-search-input').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    window.location.href = `kb_search.php?q=${encodeURIComponent(searchTerm)}`;
                }
            }
        });

        // Arama butonu tıklama
        document.querySelector('.kb-search button').addEventListener('click', function() {
            const searchTerm = document.getElementById('kb-search-input').value.trim();
            if (searchTerm) {
                window.location.href = `kb_search.php?q=${encodeURIComponent(searchTerm)}`;
            }
        });
    </script>
</body>
</html> 