<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

$theme = isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : 'light-theme';

// Makale ID'sini kontrol et
$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$article_id) {
    header('Location: knowledge_base.php');
    exit;
}

// Makaleyi getir
$stmt = $pdo->prepare("SELECT a.*, c.name as category_name, c.id as category_id,
                       (a.helpful_votes) as helpful_votes,
                       (a.not_helpful_votes) as not_helpful_votes
                       FROM kb_articles a 
                       LEFT JOIN kb_categories c ON a.category_id = c.id 
                       WHERE a.id = ?");
$stmt->execute([$article_id]);
$article = $stmt->fetch();

if (!$article) {
    header('Location: knowledge_base.php');
    exit;
}

// Görüntülenme sayısını artır
$stmt = $pdo->prepare("UPDATE kb_articles SET views = views + 1 WHERE id = ?");
$stmt->execute([$article_id]);

// İlgili makaleleri getir
$stmt = $pdo->prepare("SELECT * FROM kb_articles 
                       WHERE category_id = ? AND id != ? 
                       ORDER BY views DESC LIMIT 3");
$stmt->execute([$article['category_id'], $article_id]);
$related_articles = $stmt->fetchAll();

$page_title = $article['title'] . " - Bilgi Bankası";
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($page_title); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="<?php echo $theme; ?>">
    <div id="blog-container">
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

        <main id="blog-main">
            <article class="blog-article">
                <div class="article-breadcrumb">
                    <a href="knowledge_base.php">Bilgi Bankası</a>
                    <i class="fas fa-chevron-right"></i>
                    <a href="kb_pages/<?php echo strtolower(str_replace(' ', '_', $article['category_name'])); ?>.php">
                        <?php echo escape($article['category_name']); ?>
                    </a>
                    <i class="fas fa-chevron-right"></i>
                    <span><?php echo escape($article['title']); ?></span>
                </div>

                <header class="article-header">
                    <div class="article-meta-top">
                        <span class="article-category"><?php echo escape($article['category_name']); ?></span>
                        <span class="article-date"><?php echo date('d.m.Y', strtotime($article['created_at'])); ?></span>
                    </div>
                    <h1><?php echo escape($article['title']); ?></h1>
                    <div class="article-meta-bottom">
                        <span><i class="fas fa-eye"></i> <?php echo $article['views']; ?> görüntülenme</span>
                        <span><i class="fas fa-thumbs-up"></i> <?php echo $article['helpful_votes'] ?? 0; ?> faydalı buldu</span>
                    </div>
                </header>

                <div class="article-content">
                    <?php echo nl2br(escape($article['content'])); ?>
                </div>

                <div class="article-actions">
                    <div class="share-buttons">
                        <button class="share-button twitter" onclick="shareArticle('twitter')">
                            <i class="fab fa-twitter"></i> Twitter'da Paylaş
                        </button>
                        <button class="share-button facebook" onclick="shareArticle('facebook')">
                            <i class="fab fa-facebook"></i> Facebook'ta Paylaş
                        </button>
                        <button class="share-button linkedin" onclick="shareArticle('linkedin')">
                            <i class="fab fa-linkedin"></i> LinkedIn'de Paylaş
                        </button>
                    </div>
                </div>

                <?php if (!empty($related_articles)): ?>
                <div class="related-articles">
                    <h2>İlgili Makaleler</h2>
                    <div class="related-articles-grid">
                        <?php foreach ($related_articles as $related): ?>
                        <a href="kb_article_blog.php?id=<?php echo $related['id']; ?>" class="related-article-card">
                            <h3><?php echo escape($related['title']); ?></h3>
                            <p><?php echo mb_substr(strip_tags($related['content']), 0, 100) . '...'; ?></p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </article>
        </main>
    </div>

    <script>
        // Tema değiştirme
        document.getElementById('theme-toggle-button').addEventListener('click', function() {
            document.body.classList.toggle('dark-theme');
            localStorage.setItem('theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
        });

        // Sosyal medya paylaşım fonksiyonları
        function shareArticle(platform) {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            let shareUrl = '';

            switch (platform) {
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
                    break;
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                    break;
                case 'linkedin':
                    shareUrl = `https://www.linkedin.com/shareArticle?mini=true&url=${url}&title=${title}`;
                    break;
            }

            window.open(shareUrl, '_blank', 'width=600,height=400');
        }
    </script>
</body>
</html> 