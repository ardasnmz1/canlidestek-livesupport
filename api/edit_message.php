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

// Parametreleri kontrol et
if (!isset($_POST['message_id']) || empty($_POST['message_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Mesaj ID\'si belirtilmedi.']);
}

if (!isset($_POST['message_text'])) {
    sendJsonResponse(['success' => false, 'message' => 'Mesaj metni belirtilmedi.']);
}

$messageId = intval($_POST['message_id']);
$messageText = trim($_POST['message_text']);
$currentUserId = $_SESSION['user_id'];

try {
    // Mesajın mevcut olduğunu ve kullanıcının kendi mesajı olduğunu kontrol et
    $checkSql = "SELECT id, sender_id, message_text FROM messages WHERE id = :message_id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':message_id', $messageId, PDO::PARAM_INT);
    $checkStmt->execute();
    $message = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        sendJsonResponse(['success' => false, 'message' => 'Belirtilen mesaj bulunamadı.']);
    }
    
    // Sadece kullanıcı kendi mesajını düzenleyebilir (admin/öğretmen de kendi mesajlarını düzenleyebilir)
    if ($message['sender_id'] != $currentUserId) {
        sendJsonResponse(['success' => false, 'message' => 'Bu mesajı düzenleme yetkiniz bulunmamaktadır.']);
    }
    
    // messages tablosunda updated_at sütununun varlığını kontrol et
    $checkColumnSql = "SHOW COLUMNS FROM messages LIKE 'updated_at'";
    $checkColumnStmt = $pdo->prepare($checkColumnSql);
    $checkColumnStmt->execute();
    $updatedAtColumnExists = ($checkColumnStmt->rowCount() > 0);
    
    // Mesajı düzenle
    if ($updatedAtColumnExists) {
        $updateSql = "UPDATE messages SET message_text = :message_text, updated_at = NOW() WHERE id = :message_id";
    } else {
        $updateSql = "UPDATE messages SET message_text = :message_text WHERE id = :message_id";
    }
    
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->bindParam(':message_text', $messageText, PDO::PARAM_STR);
    $updateStmt->bindParam(':message_id', $messageId, PDO::PARAM_INT);
    $updateStmt->execute();
    
    // Güncellenmiş mesajın bilgilerini al
    $getUpdatedSql = "SELECT * FROM messages WHERE id = :message_id";
    $getUpdatedStmt = $pdo->prepare($getUpdatedSql);
    $getUpdatedStmt->bindParam(':message_id', $messageId, PDO::PARAM_INT);
    $getUpdatedStmt->execute();
    $updatedMessage = $getUpdatedStmt->fetch(PDO::FETCH_ASSOC);
    
    // updated_at bilgisi yoksa elle ekleyelim
    if (!isset($updatedMessage['updated_at']) && isset($updatedMessage['created_at'])) {
        $updatedMessage['updated_at'] = date('Y-m-d H:i:s'); // Şu anki zamanı kullan
    }
    
    // Başarılı yanıt döndür
    sendJsonResponse([
        'success' => true, 
        'message' => 'Mesaj başarıyla düzenlendi.',
        'data' => $updatedMessage
    ]);
    
} catch (PDOException $e) {
    error_log("Mesaj düzenleme hatası: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Mesaj düzenlenirken bir hata oluştu: ' . $e->getMessage()]);
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