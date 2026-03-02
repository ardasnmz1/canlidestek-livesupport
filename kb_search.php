<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

$theme = isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : 'light-theme';

// Arama sorgusunu al
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search_query) {
    // Arama sonuçlarını getir
    $stmt = $pdo->prepare("SELECT a.*, c.name as category_name 
                          FROM kb_articles a 
                          LEFT JOIN kb_categories c ON a.category_id = c.id 
                          WHERE MATCH(a.title, a.content) AGAINST(? IN BOOLEAN MODE)
                          OR a.title LIKE ? OR a.content LIKE ?
                          ORDER BY a.views DESC");
    $search_pattern = "%{$search_query}%";
    $stmt->execute([$search_query, $search_pattern, $search_pattern]);
    $results = $stmt->fetchAll();
}

$page_title = "Arama Sonuçları: " . escape($search_query);
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
                <a href="knowledge_base.php" class="header-button">Bilgi Bankası</a>
                <a href="index.php" class="header-button">Ana Sayfa</a>
                <button id="theme-toggle-button">Tema Değiştir</button>
                <button class="logout-button-styling" onclick="window.location.href='logout.php'">Çıkış Yap</button>
            </div>
        </header>

        <main id="kb-main">
            <!-- Arama Bölümü -->
            <section class="kb-hero">
                <h1>Arama Sonuçları</h1>
                <p>"<?php echo escape($search_query); ?>" için <?php echo count($results ?? []); ?> sonuç bulundu</p>
                <div class="kb-search">
                    <input type="text" id="kb-search-input" value="<?php echo escape($search_query); ?>" placeholder="Bilgi bankasında ara...">
                    <button type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </section>

            <?php if (!empty($search_query)): ?>
                <?php if (!empty($results)): ?>
                    <!-- Arama Sonuçları -->
                    <section class="search-results">
                        <div class="articles-grid">
                            <?php foreach ($results as $article): ?>
                            <a href="kb_article.php?id=<?php echo $article['id']; ?>" class="article-card">
                                <div class="article-header">
                                    <span class="article-category">
                                        <i class="fas fa-folder"></i> <?php echo escape($article['category_name']); ?>
                                    </span>
                                </div>
                                <h3><?php echo escape($article['title']); ?></h3>
                                <p><?php echo mb_substr(strip_tags($article['content']), 0, 150) . '...'; ?></p>
                                <div class="article-meta">
                                    <span><i class="fas fa-eye"></i> <?php echo $article['views']; ?> görüntülenme</span>
                                    <span><i class="fas fa-clock"></i> <?php echo date('d.m.Y', strtotime($article['created_at'])); ?></span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h2>Sonuç Bulunamadı</h2>
                        <p>Aramanızla eşleşen makale bulunamadı. Lütfen farklı anahtar kelimeler deneyiniz.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
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