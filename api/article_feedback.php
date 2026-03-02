<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// POST isteği kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir']);
    exit;
}

// JSON verisini al
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['article_id']) || !isset($data['is_helpful'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz veri formatı']);
    exit;
}

$article_id = (int)$data['article_id'];
$is_helpful = (bool)$data['is_helpful'];
$user_id = $_SESSION['user_id'];

try {
    // Önce makaleyi kontrol et
    $stmt = $pdo->prepare("SELECT id FROM kb_articles WHERE id = ?");
    $stmt->execute([$article_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Makale bulunamadı');
    }

    // Kullanıcının daha önce geri bildirim verip vermediğini kontrol et
    $stmt = $pdo->prepare("SELECT id FROM kb_article_feedback WHERE article_id = ? AND user_id = ?");
    $stmt->execute([$article_id, $user_id]);
    if ($stmt->fetch()) {
        throw new Exception('Bu makale için zaten geri bildirim verdiniz');
    }

    // Geri bildirimi kaydet
    $stmt = $pdo->prepare("INSERT INTO kb_article_feedback (article_id, user_id, is_helpful) VALUES (?, ?, ?)");
    $stmt->execute([$article_id, $user_id, $is_helpful]);

    // Makale istatistiklerini güncelle
    $field = $is_helpful ? 'helpful_votes' : 'not_helpful_votes';
    $stmt = $pdo->prepare("UPDATE kb_articles SET $field = $field + 1 WHERE id = ?");
    $stmt->execute([$article_id]);

    echo json_encode(['success' => true, 'message' => 'Geri bildiriminiz kaydedildi']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 