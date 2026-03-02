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

// Makale geri bildirimi işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback'])) {
    $is_helpful = $_POST['feedback'] === 'helpful';
    $field = $is_helpful ? 'helpful_votes' : 'not_helpful_votes';
    $stmt = $pdo->prepare("UPDATE kb_articles SET $field = $field + 1 WHERE id = ?");
    $stmt->execute([$article_id]);
    header("Location: kb_article.php?id=$article_id&feedback=sent");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($page_title); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="description" content="<?php echo escape(mb_substr(strip_tags($article['content']), 0, 160)); ?>">
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
            <div class="kb-article-container">
                <div class="article-breadcrumb">
                    <a href="knowledge_base.php">Bilgi Bankası</a>
                    <i class="fas fa-chevron-right"></i>
                    <a href="kb_pages/<?php echo strtolower(str_replace(' ', '_', $article['category_name'])); ?>.php">
                        <?php echo escape($article['category_name']); ?>
                    </a>
                    <i class="fas fa-chevron-right"></i>
                    <span><?php echo escape($article['title']); ?></span>
                </div>

                <article class="kb-article">
                    <div class="article-header">
                        <div class="article-meta-top">
                            <span class="article-category">
                                <i class="fas fa-folder"></i> <?php echo escape($article['category_name']); ?>
                            </span>
                            <span class="article-date">
                                <i class="fas fa-calendar"></i> <?php echo date('d.m.Y', strtotime($article['created_at'])); ?>
                            </span>
                        </div>

                        <h1><?php echo escape($article['title']); ?></h1>

                        <div class="article-meta-bottom">
                            <span><i class="fas fa-eye"></i> <?php echo $article['views']; ?> görüntülenme</span>
                            <span><i class="fas fa-thumbs-up"></i> <?php echo $article['helpful_votes']; ?> kişi faydalı buldu</span>
                            <?php if ($article['updated_at'] != $article['created_at']): ?>
                                <span><i class="fas fa-edit"></i> Son güncelleme: <?php echo date('d.m.Y', strtotime($article['updated_at'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="article-content">
                        <?php echo $article['content']; ?>
                    </div>

                    <?php if (!isset($_GET['feedback'])): ?>
                    <div class="article-feedback">
                        <h3>Bu makale yardımcı oldu mu?</h3>
                        <form method="post" class="feedback-buttons">
                            <button type="submit" name="feedback" value="helpful" class="feedback-btn positive">
                                <i class="fas fa-thumbs-up"></i> Evet, yardımcı oldu
                            </button>
                            <button type="submit" name="feedback" value="not_helpful" class="feedback-btn negative">
                                <i class="fas fa-thumbs-down"></i> Hayır, yardımcı olmadı
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="feedback-sent">
                        <i class="fas fa-check-circle"></i>
                        <p>Geri bildiriminiz için teşekkür ederiz!</p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($related_articles)): ?>
                    <div class="related-articles">
                        <h2>İlgili Makaleler</h2>
                        <div class="related-articles-grid">
                            <?php foreach ($related_articles as $related): ?>
                            <a href="kb_article.php?id=<?php echo $related['id']; ?>" class="related-article-card">
                                <h3><?php echo escape($related['title']); ?></h3>
                                <p><?php echo mb_substr(strip_tags($related['content']), 0, 100) . '...'; ?></p>
                                <div class="article-meta">
                                    <span><i class="fas fa-eye"></i> <?php echo $related['views']; ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo date('d.m.Y', strtotime($related['created_at'])); ?></span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </article>
            </div>
        </main>
    </div>

    <script>
        // Tema değiştirme
        document.getElementById('theme-toggle-button').addEventListener('click', function() {
            document.body.classList.toggle('dark-theme');
            localStorage.setItem('theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
        });

        // Kod bloklarını kopyalama özelliği
        document.querySelectorAll('pre code').forEach((block) => {
            const copyButton = document.createElement('button');
            copyButton.className = 'copy-code-button';
            copyButton.innerHTML = '<i class="fas fa-copy"></i>';
            
            block.parentNode.style.position = 'relative';
            block.parentNode.appendChild(copyButton);

            copyButton.addEventListener('click', () => {
                navigator.clipboard.writeText(block.textContent);
                copyButton.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    copyButton.innerHTML = '<i class="fas fa-copy"></i>';
                }, 2000);
            });
        });

        // Resim önizleme
        document.querySelectorAll('.article-content img').forEach(img => {
            img.addEventListener('click', () => {
                const modal = document.createElement('div');
                modal.className = 'image-preview-modal';
                modal.innerHTML = `
                    <div class="modal-content">
                        <img src="${img.src}" alt="${img.alt}">
                        <button class="close-modal"><i class="fas fa-times"></i></button>
                    </div>
                `;
                document.body.appendChild(modal);

                modal.addEventListener('click', (e) => {
                    if (e.target === modal || e.target.closest('.close-modal')) {
                        modal.remove();
                    }
                });
            });
        });
    </script>
</body>
</html> 