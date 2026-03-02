<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Yanıt için kullanılacak değişken
$response = ['success' => false, 'message' => 'Bilinmeyen hata oluştu.'];

// Oturum ve yetki kontrolü
if (!isLoggedIn() || ($_SESSION['role_name'] !== 'admin' && $_SESSION['role_name'] !== 'teacher')) {
    $response = ['success' => false, 'message' => 'Yetkisiz erişim. Yalnızca öğretmen ve adminler talep durumunu değiştirebilir.'];
    sendJsonResponse($response);
}

// POST verilerini kontrol et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response = ['success' => false, 'message' => 'Geçersiz istek yöntemi. POST kullanılmalıdır.'];
    sendJsonResponse($response);
}

// Gönderilen verileri kontrol et ve güvenli hale getir
$ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

// Durum değerini kontrol et
$validStatuses = ['open', 'in_progress', 'resolved', 'closed'];
if (!in_array($status, $validStatuses)) {
    $response = ['success' => false, 'message' => 'Geçersiz durum değeri.'];
    sendJsonResponse($response);
}

// Talep ID kontrol et
if ($ticketId <= 0) {
    $response = ['success' => false, 'message' => 'Geçersiz talep ID.'];
    sendJsonResponse($response);
}

try {
    // Talebin varlığını kontrol et
    $checkSql = "SELECT id, status FROM support_tickets WHERE id = :ticket_id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    $ticket = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$ticket) {
        $response = ['success' => false, 'message' => 'Destek talebi bulunamadı.'];
        sendJsonResponse($response);
    }
    
    // Talebin durumunu güncelle
    $sql = "UPDATE support_tickets SET status = :status, updated_at = NOW() WHERE id = :ticket_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
    $stmt->execute();
    
    $response = [
        'success' => true, 
        'message' => 'Destek talebi durumu başarıyla güncellendi.',
        'ticket_id' => $ticketId,
        'status' => $status
    ];
    
} catch (PDOException $e) {
    error_log("Update Ticket Status Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'Talep durumu güncellenirken bir hata oluştu: ' . $e->getMessage()];
}

sendJsonResponse($response);

/**
 * JSON yanıtını gönder ve çık
 */
function sendJsonResponse($data) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    ob_end_flush();
    exit;
} 