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

if (!$data || !isset($data['article_id']) || !isset($data['content'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz veri formatı']);
    exit;
}

$article_id = (int)$data['article_id'];
$content = trim($data['content']);
$user_id = $_SESSION['user_id'];

try {
    // Kullanıcının yetkisini kontrol et
    $stmt = $pdo->prepare("SELECT role_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !in_array($user['role_id'], [1, 2])) { // 1: admin, 2: teacher
        throw new Exception('Bu işlem için yetkiniz yok');
    }

    // Makaleyi güncelle
    $stmt = $pdo->prepare("UPDATE kb_articles SET content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$content, $article_id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Makale bulunamadı veya içerik değişmedi');
    }

    echo json_encode(['success' => true, 'message' => 'İçerik başarıyla güncellendi']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 