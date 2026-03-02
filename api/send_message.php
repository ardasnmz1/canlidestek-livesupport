<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php'; // generateChatId, escape vb. için
require_once 'fix_json.php';

// Oturum ve rol kontrolü
if (!isLoggedIn()) {
    sendJsonResponse(['success' => false, 'message' => 'Yetkisiz erişim. Mesaj göndermek için lütfen giriş yapın.']);
}

// Eğer role_name yoksa dene
if (isset($_SESSION['user_id']) && !isset($_SESSION['role_name'])) {
    $currentUserRole = getRoleNameByUserId($pdo, (int)$_SESSION['user_id']);
    if ($currentUserRole) {
        $_SESSION['role_name'] = $currentUserRole;
    }
}

$response = ['success' => false, 'message' => 'Bilinmeyen bir hata oluştu.'];

// Girdi parametrelerini al
$senderId = isset($_POST['sender_id']) ? (int)$_POST['sender_id'] : null;
$receiverId = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : null;
$messageText = isset($_POST['message_text']) ? trim($_POST['message_text']) : null;
$chatIdParam = isset($_POST['chat_id']) ? trim($_POST['chat_id']) : null;

// Temel doğrulamalar
if (empty($senderId) || empty($receiverId)) {
    sendJsonResponse(['success' => false, 'message' => 'Gönderen veya alıcı bilgisi eksik.']);
}

if ($senderId !== $_SESSION['user_id']) {
    sendJsonResponse(['success' => false, 'message' => 'Geçersiz gönderen. Yetkilendirme hatası.']);
}

if (empty($messageText) && (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] == UPLOAD_ERR_NO_FILE)) {
    sendJsonResponse(['success' => false, 'message' => 'Mesaj içeriği boş olamaz (metin veya dosya eklenmeli).']);
}

// Chat ID'yi oluştur veya al
$chatId = !empty($chatIdParam) ? $chatIdParam : generateChatId($senderId, $receiverId);
$filePath = null;
$uploadedFileName = null;

// Dosya yükleme işlemi
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/'; // Ana dizine göre uploads klasörü
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0775, true)) {
            sendJsonResponse(['success' => false, 'message' => 'Dosya yükleme dizini oluşturulamadı.']);
        }
    }
    if (!is_writable($uploadDir)){
        sendJsonResponse(['success' => false, 'message' => 'Dosya yükleme dizini yazılabilir değil.']);
    }

    $originalFileName = basename($_FILES['attachment']['name']);
    $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
    $sanitizedFileName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', pathinfo($originalFileName, PATHINFO_FILENAME)); // Özel karakterleri temizle
    $uniqueFileName = $sanitizedFileName . '_' . uniqid() . '.' . $fileExtension;
    $targetFile = $uploadDir . $uniqueFileName;

    // Dosya boyutu kontrolü (örneğin 10MB)
    define('MAX_FILE_SIZE_MB', 10);
    if ($_FILES['attachment']['size'] > MAX_FILE_SIZE_MB * 1024 * 1024) {
        sendJsonResponse(['success' => false, 'message' => 'Dosya boyutu çok büyük (maksimum ' . MAX_FILE_SIZE_MB . 'MB).']);
    }

    // İzin verilen dosya türleri (isteğe bağlı, güvenliği artırır)
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
         // Mime type ile uzantı eşleşmiyorsa veya izin verilenler listesinde yoksa reddet
        sendJsonResponse(['success' => false, 'message' => 'İzin verilmeyen dosya türü veya hatalı dosya uzantısı.']);
    }

    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
        $filePath = 'uploads/' . $uniqueFileName; // Veritabanına kaydedilecek göreli yol
        $uploadedFileName = $originalFileName; // İstemciye orijinal adı gönderebiliriz (isteğe bağlı)
    } else {
        sendJsonResponse(['success' => false, 'message' => 'Dosya yüklenirken bir sunucu hatası oluştu.']);
    }
}

try {
    $pdo->beginTransaction();

    // messages tablosunda is_solution sütununun varlığını kontrol et
    $checkColumnSql = "SHOW COLUMNS FROM messages LIKE 'is_solution'";
    $checkColumnStmt = $pdo->prepare($checkColumnSql);
    $checkColumnStmt->execute();
    $isSolutionColumnExists = ($checkColumnStmt->rowCount() > 0);

    // is_solution değeri varsayılan olarak false (0)
    $isSolution = isset($_POST['is_solution']) && $_POST['is_solution'] ? 1 : 0;

    if ($isSolutionColumnExists) {
        $sql = "INSERT INTO messages (chat_id, sender_id, receiver_id, message_text, file_path, is_solution, created_at, updated_at)
                VALUES (:chat_id, :sender_id, :receiver_id, :message_text, :file_path, :is_solution, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':chat_id', $chatId, PDO::PARAM_STR);
        $stmt->bindParam(':sender_id', $senderId, PDO::PARAM_INT);
        $stmt->bindParam(':receiver_id', $receiverId, PDO::PARAM_INT);
        $stmt->bindParam(':message_text', $messageText, PDO::PARAM_STR);
        $stmt->bindParam(':file_path', $filePath, PDO::PARAM_STR);
        $stmt->bindParam(':is_solution', $isSolution, PDO::PARAM_INT);
    } else {
        $sql = "INSERT INTO messages (chat_id, sender_id, receiver_id, message_text, file_path, created_at, updated_at)
                VALUES (:chat_id, :sender_id, :receiver_id, :message_text, :file_path, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':chat_id', $chatId, PDO::PARAM_STR);
        $stmt->bindParam(':sender_id', $senderId, PDO::PARAM_INT);
        $stmt->bindParam(':receiver_id', $receiverId, PDO::PARAM_INT);
        $stmt->bindParam(':message_text', $messageText, PDO::PARAM_STR);
        $stmt->bindParam(':file_path', $filePath, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $messageId = $pdo->lastInsertId();

    // Gönderilen mesajı tam olarak geri döndür
    if ($isSolutionColumnExists) {
        $selectSql = "SELECT id, chat_id, sender_id, receiver_id, message_text, file_path, created_at, is_read, is_solution 
                    FROM messages 
                    WHERE id = :message_id LIMIT 1";
    } else {
        $selectSql = "SELECT id, chat_id, sender_id, receiver_id, message_text, file_path, created_at, is_read 
                    FROM messages 
                    WHERE id = :message_id LIMIT 1";
    }
    $selectStmt = $pdo->prepare($selectSql);
    $selectStmt->bindParam(':message_id', $messageId, PDO::PARAM_INT);
    $selectStmt->execute();
    $sentMessage = $selectStmt->fetch(PDO::FETCH_ASSOC);

    if ($sentMessage) {
         // Dosya adı eklemesi (isteğe bağlı, eğer JS'de orijinal adı göstermek isterseniz)
        if ($filePath && $uploadedFileName) {
            $sentMessage['original_file_name'] = $uploadedFileName;
        }
        $response = ['success' => true, 'message' => 'Mesaj başarıyla gönderildi.', 'data' => $sentMessage, 'chat_id' => $chatId];
    } else {
        throw new PDOException("Gönderilen mesaj veritabanından alınamadı.");
    }

    $pdo->commit();
    
    sendJsonResponse($response);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Send Message - Veritabanı Hatası: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Mesaj gönderilirken bir veritabanı hatası oluştu. Lütfen tekrar deneyin.']);
} catch (Exception $e) {
    // Diğer genel hatalar
    error_log("Send Message - Genel Hata: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Mesaj gönderilirken beklenmedik bir hata oluştu.']);
} 