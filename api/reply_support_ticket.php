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

// Kullanıcı bilgileri
$userId = (int)$_SESSION['user_id'];
$currentUserRole = $_SESSION['role_name'] ?? '';

// Gönderilen verileri kontrol et ve güvenli hale getir
$ticketId = (int)($_POST['ticket_id'] ?? 0);
$replyText = trim($_POST['reply_text'] ?? '');
$markAsSolution = isset($_POST['is_solution']) ? (bool)$_POST['is_solution'] : false;

// Burada messages tablosunda is_solution sütununun olup olmadığını kontrol edelim
// Eğer ticket_replies tablosunda markAsSolution kullanımı yapıyorsak, messages tablosunda da aynısını kullanmalıyız
try {
    $checkColumnSql = "SHOW COLUMNS FROM messages LIKE 'is_solution'";
    $checkColumnStmt = $pdo->prepare($checkColumnSql);
    $checkColumnStmt->execute();
    $messagesIsSolutionExists = ($checkColumnStmt->rowCount() > 0);
} catch (PDOException $e) {
    error_log("messages tablosu is_solution kontrolü hatası: " . $e->getMessage());
    $messagesIsSolutionExists = false;
}

// Dosya yolu varsayılan olarak NULL
$filePath = null;

// Basit doğrulama
if (empty($replyText) && !isset($_FILES['attachment'])) {
    $response = ['success' => false, 'message' => 'Cevap metni veya ek dosya gereklidir.'];
    sendJsonResponse($response);
}

if ($ticketId <= 0) {
    $response = ['success' => false, 'message' => 'Geçersiz destek talebi ID.'];
    sendJsonResponse($response);
}

// Destek talebinin varlığını ve yetkiyi kontrol et
try {
    $sql = "SELECT st.*, u.username AS student_username
            FROM support_tickets st
            JOIN users u ON st.student_id = u.id
            WHERE st.id = :ticket_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
    $stmt->execute();
    
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        $response = ['success' => false, 'message' => 'Destek talebi bulunamadı.'];
        sendJsonResponse($response);
    }
    
    // Yetki kontrolü - sadece talebi oluşturan öğrenci, ataması yapılan kişi veya admin/teacher cevap verebilir
    $canReply = false;
    
    if ($ticket['student_id'] == $userId) {
        $canReply = true; // Talebi oluşturan öğrenci
    } else if ($ticket['assigned_to_id'] == $userId) {
        $canReply = true; // Atanmış öğretmen/admin
    } else if ($currentUserRole === 'admin' || $currentUserRole === 'teacher') {
        $canReply = true; // Herhangi bir admin veya öğretmen
    }
    
    if (!$canReply) {
        $response = ['success' => false, 'message' => 'Bu destek talebine cevap verme yetkiniz yok.'];
        sendJsonResponse($response);
    }
    
    // Çözüm olarak işaretleme yetkisi - sadece öğretmen veya admin
    if ($markAsSolution && $currentUserRole !== 'teacher' && $currentUserRole !== 'admin') {
        $markAsSolution = false; // Yetki yoksa çözüm olarak işaretleme
    }
    
} catch (PDOException $e) {
    error_log("Ticket Check Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'Destek talebi kontrol edilirken bir hata oluştu.'];
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

    // Ticket_replies tablosunda file_path sütununun varlığını kontrol et
    $checkColumnSql = "SHOW COLUMNS FROM ticket_replies LIKE 'file_path'";
    $checkColumnStmt = $pdo->prepare($checkColumnSql);
    $checkColumnStmt->execute();
    $filePathColumnExists = ($checkColumnStmt->rowCount() > 0);

    // file_path sütunu mevcutsa
    if ($filePathColumnExists) {
        $sql = "INSERT INTO ticket_replies (ticket_id, user_id, reply_text, file_path, is_solution, created_at, updated_at)
                VALUES (:ticket_id, :user_id, :reply_text, :file_path, :is_solution, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':reply_text', $replyText, PDO::PARAM_STR);
        $stmt->bindParam(':file_path', $filePath, PDO::PARAM_STR);
        $stmt->bindParam(':is_solution', $markAsSolution, PDO::PARAM_BOOL);
    } else {
        // file_path sütunu mevcut değilse
        $sql = "INSERT INTO ticket_replies (ticket_id, user_id, reply_text, is_solution, created_at, updated_at)
                VALUES (:ticket_id, :user_id, :reply_text, :is_solution, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':reply_text', $replyText, PDO::PARAM_STR);
        $stmt->bindParam(':is_solution', $markAsSolution, PDO::PARAM_BOOL);
    }
    
    $stmt->execute();
    
    $replyId = $pdo->lastInsertId();
    
    // Eğer öğretmen/admin cevap verdiyse ve destek talebi henüz atanmamışsa, otomatik ata
    if (($currentUserRole === 'teacher' || $currentUserRole === 'admin') && 
        ($ticket['assigned_to_id'] === null || $ticket['assigned_to_id'] == 0)) {
        
        $sqlAssign = "UPDATE support_tickets SET assigned_to_id = :assigned_to_id WHERE id = :ticket_id";
        $stmtAssign = $pdo->prepare($sqlAssign);
        $stmtAssign->bindParam(':assigned_to_id', $userId, PDO::PARAM_INT);
        $stmtAssign->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
        $stmtAssign->execute();
    }
    
    // Eğer çözüm olarak işaretlendiyse, destek talebinin durumunu güncelle
    if ($markAsSolution) {
        $sqlUpdate = "UPDATE support_tickets SET status = 'resolved', updated_at = NOW() WHERE id = :ticket_id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
        $stmtUpdate->execute();
    } else if ($ticket['status'] === 'open') {
        // Talebin durumunu "in_progress" olarak güncelle (sadece ilk kez öğretmen/admin cevap verdiğinde)
        if ($currentUserRole === 'teacher' || $currentUserRole === 'admin') {
            $sqlUpdate = "UPDATE support_tickets SET status = 'in_progress', updated_at = NOW() WHERE id = :ticket_id";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->bindParam(':ticket_id', $ticketId, PDO::PARAM_INT);
            $stmtUpdate->execute();
        }
    }
    
    // Cevap bilgilerini al
    $sqlReply = "SELECT tr.*, u.username AS reply_username, u.role_id, r.name AS role_name
                 FROM ticket_replies tr
                 JOIN users u ON tr.user_id = u.id
                 JOIN roles r ON u.role_id = r.id
                 WHERE tr.id = :reply_id";
    
    $stmtReply = $pdo->prepare($sqlReply);
    $stmtReply->bindParam(':reply_id', $replyId, PDO::PARAM_INT);
    $stmtReply->execute();
    
    $replyData = $stmtReply->fetch(PDO::FETCH_ASSOC);
    
    $pdo->commit();
    
    $response = [
        'success' => true,
        'message' => 'Cevap başarıyla eklendi.',
        'reply' => $replyData
    ];
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Reply Support Ticket Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'Cevap eklenirken bir veritabanı hatası oluştu: ' . $e->getMessage()];
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