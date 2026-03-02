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

// Oturum kontrolü
if (!isLoggedIn()) {
    sendJsonResponse(['success' => false, 'message' => 'Yetkisiz erişim. Lütfen giriş yapın.']);
}

// Session değişkenlerinin varlığını kontrol et
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Oturum bilgileri eksik. Lütfen tekrar giriş yapın.']);
}

$currentUserId = $_SESSION['user_id'];
$currentUserRole = isset($_SESSION['role_name']) ? $_SESSION['role_name'] : null;

// Eğer role_name yoksa, veritabanından almayı dene
if (empty($currentUserRole) && $currentUserId) {
    $currentUserRole = getRoleNameByUserId($pdo, $currentUserId);
    if ($currentUserRole) {
        $_SESSION['role_name'] = $currentUserRole; // Session'a ekle
    } else {
        sendJsonResponse(['success' => false, 'message' => 'Kullanıcı rolü bulunamadı. Lütfen tekrar giriş yapın.']);
    }
}

try {
    // Tüm kullanıcıları ve rollerini çek, mevcut kullanıcı hariç
    $sql = "SELECT u.id, u.username, u.email, u.is_online, r.name as role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id != :current_user_id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':current_user_id', $currentUserId, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filteredUsers = [];
    if ($currentUserRole === 'visitor') {
        foreach ($users as $user) {
            if ($user['role_name'] === 'teacher' || $user['role_name'] === 'admin') {
                $filteredUsers[] = $user;
            }
        }
    } else if ($currentUserRole === 'student') {
        // Öğrenciler tüm kullanıcıları görebilir
        $filteredUsers = $users;
    } else {
        // Öğretmenler ve Adminler herkesi görebilir
        $filteredUsers = $users;
    }

    sendJsonResponse(['success' => true, 'data' => $filteredUsers]);

} catch (PDOException $e) {
    error_log("Get Users Error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Kullanıcılar yüklenirken bir veritabanı hatası oluştu: ' . $e->getMessage()]);
} 