<?php
// Hata raporlamayı aktifleştir
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Çıktı tamponlamayı başlat
ob_start();

// Oturum başlat
session_start();

// Gerekli dosyaları dahil et
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../api/fix_json.php';

// Oturum kontrolü
if (!isLoggedIn()) {
    sendJsonResponse(['success' => false, 'message' => 'Yetkisiz erişim. Lütfen giriş yapın.']);
}

// POST metodu kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Geçersiz istek metodu. POST kullanın.']);
}

// Mesaj ID parametresini kontrol et
if (!isset($_POST['message_id']) || empty($_POST['message_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Mesaj ID\'si belirtilmedi.']);
}

$messageId = intval($_POST['message_id']);
$currentUserId = $_SESSION['user_id'];

try {
    // Mesajın mevcut olduğunu ve kullanıcının kendi mesajı olduğunu kontrol et
    $checkSql = "SELECT id, sender_id FROM messages WHERE id = :message_id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':message_id', $messageId, PDO::PARAM_INT);
    $checkStmt->execute();
    $message = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        sendJsonResponse(['success' => false, 'message' => 'Belirtilen mesaj bulunamadı.']);
    }
    
    // Kullanıcının kendi mesajını veya admin/öğretmen ise herhangi bir mesajı silebilir
    if ($message['sender_id'] != $currentUserId && ($_SESSION['role_name'] !== 'admin' && $_SESSION['role_name'] !== 'teacher')) {
        sendJsonResponse(['success' => false, 'message' => 'Bu mesajı silme yetkiniz bulunmamaktadır.']);
    }
    
    // Mesajı sil
    $deleteSql = "DELETE FROM messages WHERE id = :message_id";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->bindParam(':message_id', $messageId, PDO::PARAM_INT);
    $deleteStmt->execute();
    
    // Başarılı yanıt döndür
    sendJsonResponse(['success' => true, 'message' => 'Mesaj başarıyla silindi.']);
    
} catch (PDOException $e) {
    error_log("Mesaj silme hatası: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Mesaj silinirken bir hata oluştu: ' . $e->getMessage()]);
}

// JSON yanıtları için yardımcı fonksiyon
function sendJsonResponse($data) {
    // Çıktı tamponunu temizle
    ob_end_clean();
    
    // HTTP başlıklarını ayarla
    header('Content-Type: application/json; charset=utf-8');
    
    // JSON yanıtı döndür ve çık
    echo json_encode($data);
    exit;
} 