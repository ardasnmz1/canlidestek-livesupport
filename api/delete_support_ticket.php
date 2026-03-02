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

// Sadece öğretmen ve admin'lerin talepleri silmesine izin ver
if ($_SESSION['role_name'] !== 'teacher' && $_SESSION['role_name'] !== 'admin') {
    sendJsonResponse(['success' => false, 'message' => 'Bu işlem için yetkiniz bulunmamaktadır.']);
}

// POST metodu kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Geçersiz istek metodu. POST kullanın.']);
}

// Ticket ID parametresini kontrol et
if (!isset($_POST['ticket_id']) || empty($_POST['ticket_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Destek talebi ID\'si belirtilmedi.']);
}

$ticketId = intval($_POST['ticket_id']);

try {
    // Transaction başlat - tüm silme işlemleri birlikte gerçekleşmeli
    $pdo->beginTransaction();
    
    // Önce destek talebinin mevcut olduğunu ve silme yetkinizin olduğunu kontrol et
    $checkSql = "SELECT id FROM support_tickets WHERE id = :ticket_id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        // Transaction'ı geri al
        $pdo->rollBack();
        sendJsonResponse(['success' => false, 'message' => 'Belirtilen destek talebi bulunamadı.']);
    }
    
    // 1. Bu talebe ait cevaplara (ticket_replies) verilmiş beğenileri (likes) sil
    // Önce silinecek reply_id'leri al
    $getReplyIdsSql = "SELECT id FROM ticket_replies WHERE ticket_id = :ticket_id_for_replies";
    $getReplyIdsStmt = $pdo->prepare($getReplyIdsSql);
    $getReplyIdsStmt->bindParam(':ticket_id_for_replies', $ticketId, PDO::PARAM_INT);
    $getReplyIdsStmt->execute();
    $replyIds = $getReplyIdsStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($replyIds)) {
        $placeholders = implode(',', array_fill(0, count($replyIds), '?'));
        $deleteLikesSql = "DELETE FROM likes WHERE reply_id IN ($placeholders)";
        $deleteLikesStmt = $pdo->prepare($deleteLikesSql);
        $deleteLikesStmt->execute($replyIds);
    }

    // 2. Destek talebine ait cevapları (ticket_replies) sil
    $deleteRepliesSql = "DELETE FROM ticket_replies WHERE ticket_id = :ticket_id_replies";
    $deleteRepliesStmt = $pdo->prepare($deleteRepliesSql);
    $deleteRepliesStmt->bindParam(':ticket_id_replies', $ticketId, PDO::PARAM_INT);
    $deleteRepliesStmt->execute();
    
    // 3. Destek talebini sil (support_tickets)
    $deleteTicketSql = "DELETE FROM support_tickets WHERE id = :ticket_id_ticket";
    $deleteTicketStmt = $pdo->prepare($deleteTicketSql);
    $deleteTicketStmt->bindParam(':ticket_id_ticket', $ticketId, PDO::PARAM_INT);
    $deleteTicketStmt->execute();
    
    // Transaction'ı tamamla
    $pdo->commit();
    
    // Başarılı yanıt döndür
    sendJsonResponse(['success' => true, 'message' => 'Destek talebi ve ilgili tüm veriler başarıyla silindi.']);
    
} catch (PDOException $e) {
    // Hata durumunda transaction'ı geri al
    $pdo->rollBack();
    error_log("Destek talebi silme hatası: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Destek talebi silinirken bir hata oluştu: ' . $e->getMessage()]);
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