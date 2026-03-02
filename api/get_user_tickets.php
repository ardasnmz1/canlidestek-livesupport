<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Yanıt için kullanılacak değişken
$response = ['success' => false, 'message' => 'Bilinmeyen hata oluştu.'];

// Oturum kontrolü
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'Yetkisiz erişim. Lütfen giriş yapın.'];
    sendJsonResponse($response);
}

// Kullanıcı ID'si
$userId = (int)$_SESSION['user_id'];
$currentUserRole = $_SESSION['role_name'] ?? '';

try {
    // Sorgu oluşturma - öğretmen ve adminler tüm talepleri görebilir
    if ($currentUserRole === 'teacher' || $currentUserRole === 'admin') {
        $sql = "SELECT st.*, u.username AS student_username
                FROM support_tickets st
                JOIN users u ON st.student_id = u.id
                ORDER BY st.updated_at DESC";
        $stmt = $pdo->prepare($sql);
    } else {
        // Diğer kullanıcılar (öğrenciler) sadece kendi taleplerini görebilir
        $sql = "SELECT st.*, u.username AS student_username
                FROM support_tickets st
                JOIN users u ON st.student_id = u.id
                WHERE st.student_id = :user_id
                ORDER BY st.updated_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = ['success' => true, 'data' => $tickets];
    
} catch (PDOException $e) {
    error_log("Get User Tickets Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'Destek talepleri yüklenirken bir veritabanı hatası oluştu: ' . $e->getMessage()];
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