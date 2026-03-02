<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

$theme = isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : 'light-theme';

// Kategori ID'sini al ve kontrol et
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$category_id) {
    header('Location: knowledge_base.php');
    exit;
}

// Kategoriyi getir
$stmt = $pdo->prepare("SELECT * FROM kb_categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: knowledge_base.php');
    exit;
}

// Arama yapıldı mı kontrol et
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Makaleleri getir
$articles_query = "SELECT a.*, c.name as category_name 
                  FROM kb_articles a 
                  LEFT JOIN kb_categories c ON a.category_id = c.id 
                  WHERE a.category_id = :category_id";

if ($search_query) {
    $articles_query .= " AND (a.title LIKE :search OR a.content LIKE :search)";
}
$articles_query .= " ORDER BY a.views DESC";

$stmt = $pdo->prepare($articles_query);
$stmt->bindParam(':category_id', $category_id);
if ($search_query) {
    $search_param = "%$search_query%";
    $stmt->bindParam(':search', $search_param);
}
$stmt->execute();
$articles = $stmt->fetchAll();

$page_title = $category['name'] . " - Bilgi Bankası";
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($page_title); ?> - Okul Destek Sistemi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:,">
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
                <a href="knowledge_base.php" class="header-button">Bilgi Bankası</a>
                <a href="index.php" class="header-button">Ana Sayfa</a>
                <button id="theme-toggle-button">Tema Değiştir</button>
                <button class="logout-button-styling" onclick="window.location.href='logout.php'">Çıkış Yap</button>
            </div>
        </header>

        <main id="kb-main">
            <div class="kb-category-header">
                <div class="category-breadcrumb">
                    <a href="knowledge_base.php">Bilgi Bankası</a> 
                    <i class="fas fa-chevron-right"></i> 
                    <span><?php echo escape($category['name']); ?></span>
                </div>
                <h1><i class="fas fa-folder-open"></i> <?php echo escape($category['name']); ?></h1>
                <p><?php echo escape($category['description']); ?></p>
            </div>

            <div class="kb-search-section">
                <form action="" method="GET" class="kb-search-form">
                    <input type="hidden" name="id" value="<?php echo $category_id; ?>">
                    <div class="search-input-group">
                        <div class="search-icon"><i class="fas fa-search"></i></div>
                        <input type="text" name="q" value="<?php echo escape($search_query); ?>" 
                               placeholder="<?php echo escape($category['name']); ?> kategorisinde ara..." 
                               class="kb-search-input">
                        <button type="submit" class="kb-search-button">Ara</button>
                    </div>
                </form>
            </div>

            <div class="kb-content">
                <?php if (empty($articles)): ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h2>Sonuç Bulunamadı</h2>
                        <p>Arama kriterlerinize uygun makale bulunamadı. Lütfen farklı anahtar kelimeler deneyiniz.</p>
                        <a href="?id=<?php echo $category_id; ?>" class="clear-search-button">Aramayı Temizle</a>
                    </div>
                <?php else: ?>
                    <div class="article-grid">
                        <?php foreach ($articles as $article): ?>
                            <a href="kb_article_blog.php?id=<?php echo $article['id']; ?>" class="article-card">
                                <div class="article-category">
                                    <i class="fas fa-tag"></i> <?php echo escape($article['category_name']); ?>
                                </div>
                                <h3><?php echo escape($article['title']); ?></h3>
                                <p><?php echo mb_substr(strip_tags($article['content']), 0, 150) . '...'; ?></p>
                                <div class="article-meta">
                                    <span><i class="fas fa-eye"></i> <?php echo $article['views']; ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo date('d.m.Y', strtotime($article['updated_at'])); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Tema değiştirme
        document.getElementById('theme-toggle-button').addEventListener('click', function() {
            document.body.classList.toggle('dark-theme');
            localStorage.setItem('theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
        });

        // Mobil menü
        const menuToggleButton = document.getElementById('menu-toggle-button');
        const kbContainer = document.getElementById('kb-container');
        
        if (menuToggleButton) {
            menuToggleButton.addEventListener('click', () => {
                kbContainer.classList.toggle('mobile-menu-open');
            });
        }
    </script>
</body>
</html> 