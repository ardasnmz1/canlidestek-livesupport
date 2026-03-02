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

// POST verilerini kontrol et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response = ['success' => false, 'message' => 'Geçersiz istek yöntemi. POST kullanılmalıdır.'];
    sendJsonResponse($response);
}

// Kullanıcı ID'si
$userId = (int)$_SESSION['user_id'];

// Gönderilen verileri kontrol et
$replyId = (int)($_POST['reply_id'] ?? 0);
$action = trim($_POST['action'] ?? ''); // 'like' veya 'unlike'

// Basit doğrulama
if ($replyId <= 0) {
    $response = ['success' => false, 'message' => 'Geçersiz cevap ID.'];
    sendJsonResponse($response);
}

if ($action !== 'like' && $action !== 'unlike') {
    $response = ['success' => false, 'message' => 'Geçersiz işlem. "like" veya "unlike" olmalıdır.'];
    sendJsonResponse($response);
}

try {
    // İlk olarak, cevabın var olup olmadığını kontrol et
    $sqlCheck = "SELECT tr.id, tr.ticket_id, tr.user_id
                 FROM ticket_replies tr
                 WHERE tr.id = :reply_id";
    
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->bindParam(':reply_id', $replyId, PDO::PARAM_INT);
    $stmtCheck->execute();
    
    $reply = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$reply) {
        $response = ['success' => false, 'message' => 'Belirtilen cevap bulunamadı.'];
        sendJsonResponse($response);
    }
    
    // Kendi cevabını beğenmesini engelle
    if ($reply['user_id'] == $userId) {
        $response = ['success' => false, 'message' => 'Kendi cevabınızı beğenemezsiniz.'];
        sendJsonResponse($response);
    }
    
    // Kullanıcının bu cevabı daha önce beğenip beğenmediğini kontrol et
    $sqlLikeCheck = "SELECT id FROM likes WHERE user_id = :user_id AND reply_id = :reply_id";
    $stmtLikeCheck = $pdo->prepare($sqlLikeCheck);
    $stmtLikeCheck->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmtLikeCheck->bindParam(':reply_id', $replyId, PDO::PARAM_INT);
    $stmtLikeCheck->execute();
    
    $existingLike = $stmtLikeCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($action === 'like') {
        // Zaten beğenilmişse hata gönder
        if ($existingLike) {
            $response = ['success' => false, 'message' => 'Bu cevabı zaten beğendiniz.'];
            sendJsonResponse($response);
        }
        
        // Beğeniyi ekle
        $sqlInsert = "INSERT INTO likes (user_id, reply_id, created_at) VALUES (:user_id, :reply_id, NOW())";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmtInsert->bindParam(':reply_id', $replyId, PDO::PARAM_INT);
        $stmtInsert->execute();
        
        $likeId = $pdo->lastInsertId();
        
        $response = [
            'success' => true,
            'message' => 'Cevap beğenildi.',
            'like_id' => $likeId
        ];
        
    } else if ($action === 'unlike') {
        // Beğeni yoksa hata gönder
        if (!$existingLike) {
            $response = ['success' => false, 'message' => 'Bu cevabı henüz beğenmediniz.'];
            sendJsonResponse($response);
        }
        
        // Beğeniyi kaldır
        $sqlDelete = "DELETE FROM likes WHERE user_id = :user_id AND reply_id = :reply_id";
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmtDelete->bindParam(':reply_id', $replyId, PDO::PARAM_INT);
        $stmtDelete->execute();
        
        $response = [
            'success' => true,
            'message' => 'Beğeni kaldırıldı.'
        ];
    }
    
    // Toplam beğeni sayısını getir
    $sqlCount = "SELECT COUNT(*) AS like_count FROM likes WHERE reply_id = :reply_id";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->bindParam(':reply_id', $replyId, PDO::PARAM_INT);
    $stmtCount->execute();
    
    $likeCount = $stmtCount->fetch(PDO::FETCH_ASSOC)['like_count'];
    
    $response['like_count'] = $likeCount;
    
} catch (PDOException $e) {
    error_log("Like Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'Beğeni işlemi sırasında bir veritabanı hatası oluştu: ' . $e->getMessage()];
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