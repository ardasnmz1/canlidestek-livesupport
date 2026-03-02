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

// Talebi oluşturan kullanıcı
$userId = (int)$_SESSION['user_id'];
// Gönderilen verileri kontrol et ve güvenli hale getir
$subject = trim($_POST['subject'] ?? '');
$description = trim($_POST['description'] ?? '');

// Dosya yolu varsayılan olarak NULL
$filePath = null;

// Basit doğrulama
if (empty($subject)) {
    $response = ['success' => false, 'message' => 'Talep konusu boş olamaz.'];
    sendJsonResponse($response);
}

// Dosya yükleme işlemi
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/tickets/';
    
    // Dizin yoksa oluştur
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0775, true)) {
            $response = ['success' => false, 'message' => 'Dosya yükleme dizini oluşturulamadı.'];
            sendJsonResponse($response);
        }
    }
    
    // Dosya yükleme dizinini kontrol et
    if (!is_writable($uploadDir)) {
        $response = ['success' => false, 'message' => 'Dosya yükleme dizini yazılabilir değil.'];
        sendJsonResponse($response);
    }
    
    // Dosya adını güvenli hale getir
    $originalFileName = basename($_FILES['attachment']['name']);
    $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
    $sanitizedFileName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', pathinfo($originalFileName, PATHINFO_FILENAME));
    $uniqueFileName = $sanitizedFileName . '_' . uniqid() . '.' . $fileExtension;
    $targetFile = $uploadDir . $uniqueFileName;
    
    // Dosya boyutu kontrolü
    define('MAX_FILE_SIZE_MB', 10);
    if ($_FILES['attachment']['size'] > MAX_FILE_SIZE_MB * 1024 * 1024) {
        $response = ['success' => false, 'message' => 'Dosya boyutu çok büyük (maksimum ' . MAX_FILE_SIZE_MB . 'MB).'];
        sendJsonResponse($response);
    }
    
    // İzin verilen dosya türleri
    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'
    ];
    
    $fileMimeType = mime_content_type($_FILES['attachment']['tmp_name']);
    
    if (!array_key_exists($fileMimeType, $allowedMimeTypes) || $allowedMimeTypes[$fileMimeType] !== $fileExtension) {
        $response = ['success' => false, 'message' => 'İzin verilmeyen dosya türü veya hatalı dosya uzantısı.'];
        sendJsonResponse($response);
    }
    
    // Dosyayı yükle
    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
        $filePath = 'uploads/tickets/' . $uniqueFileName;
    } else {
        $response = ['success' => false, 'message' => 'Dosya yüklenirken bir hata oluştu.'];
        sendJsonResponse($response);
    }
}

try {
    $pdo->beginTransaction();
    
    // Destek talebi oluştur
    $sql = "INSERT INTO support_tickets (student_id, subject, description, status, created_at, updated_at)
            VALUES (:student_id, :subject, :description, 'open', NOW(), NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->execute();
    
    $ticketId = $pdo->lastInsertId();
    
    // Eğer dosya yüklendiyse, ilk cevap olarak ekle
    if ($filePath) {
        $replyText = 'Ek dosya yüklendi.';
        
        $sqlAttachment = "INSERT INTO ticket_replies (ticket_id, user_id, reply_text, file_path, created_at, updated_at)
                          VALUES (:ticket_id, :user_id, :reply_text, :file_path, NOW(), NOW())";
        
        $stmtAttachment = $pdo->prepare($sqlAttachment);
        $stmtAttachment->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
        $stmtAttachment->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmtAttachment->bindParam(':reply_text', $replyText, PDO::PARAM_STR);
        $stmtAttachment->bindParam(':file_path', $filePath, PDO::PARAM_STR);
        $stmtAttachment->execute();
    }
    
    $pdo->commit();
    
    $response = [
        'success' => true,
        'message' => 'Destek talebi başarıyla oluşturuldu.',
        'ticket_id' => $ticketId
    ];
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Create Support Ticket Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'Destek talebi oluşturulurken bir veritabanı hatası oluştu: ' . $e->getMessage()];
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