<?php
// Tüm çıktıyı devre dışı bırak
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Çıktı tamponlama başlat
ob_start();

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

// Eksik role_name varsa almaya çalış
if (!isset($_SESSION['role_name']) && isset($_SESSION['user_id'])) {
    $currentUserRole = getRoleNameByUserId($pdo, (int)$_SESSION['user_id']);
    if ($currentUserRole) {
        $_SESSION['role_name'] = $currentUserRole;
    }
}

$currentUserRole = $_SESSION['role_name'] ?? ''; // Doğru session değişkeni

if ($currentUserRole !== 'teacher' && $currentUserRole !== 'admin') {
    $response = ['success' => false, 'message' => 'Bu kaynağa erişim yetkiniz yok.'];
    sendJsonResponse($response);
}

try {
    // Aktif (örneğin 'open' veya 'pending' durumundaki) destek taleplerini ve talep eden öğrencinin kullanıcı adını çek
    // Destek taleplerini en yeniden en eskiye doğru sırala
    $sql = "SELECT st.id, st.student_id, u.username AS student_username, st.subject, st.status, st.created_at, st.updated_at
            FROM support_tickets st
            JOIN users u ON st.student_id = u.id
            WHERE st.status IN ('open', 'pending')
            ORDER BY st.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = ['success' => true, 'data' => $tickets];
    sendJsonResponse($response);

} catch (PDOException $e) {
    error_log("Get Support Tickets Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'Destek talepleri yüklenirken bir veritabanı hatası oluştu: ' . $e->getMessage()];
    sendJsonResponse($response);
}

/**
 * JSON yanıtını gönder ve çık
 */
function sendJsonResponse($data) {
    // Çıktı tamponunu temizle
    ob_clean();
    
    // JSON başlığını ayarla
    header('Content-Type: application/json');
    
    // Saf JSON çıktısı
    echo json_encode($data);
    
    // Tamponu boşalt ve çık
    ob_end_flush();
    exit;
} 