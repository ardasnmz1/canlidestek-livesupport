<?php
@ini_set('display_errors', 0); // Bu script için hata gösterimini zorla kapat
error_reporting(0);           // Bu script için hiçbir hata raporlama
ob_start();                   // Çıktı tamponlamasını başlat

session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Oturum açmanız gerekiyor.'
    ]);
    exit;
}

// Dosya yükleme kontrolü
if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'Lütfen bir profil fotoğrafı seçin.'
    ]);
    exit;
}

$file = $_FILES['profile_image'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_size = 5 * 1024 * 1024; // 5MB

// Dosya türü kontrolü
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode([
        'success' => false,
        'message' => 'Sadece JPG, PNG ve GIF formatları desteklenmektedir.'
    ]);
    exit;
}

// Dosya boyutu kontrolü
if ($file['size'] > $max_size) {
    echo json_encode([
        'success' => false,
        'message' => 'Dosya boyutu 5MB\'dan küçük olmalıdır.'
    ]);
    exit;
}

// Yükleme dizini kontrolü ve oluşturma
$upload_dir = __DIR__ . '/../uploads/profile_images';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Benzersiz dosya adı oluştur
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = uniqid('profile_') . '.' . $file_extension;
$upload_path = $upload_dir . '/' . $new_filename;

// Eski profil fotoğrafını sil
try {
    $stmt = $pdo->prepare("SELECT profile_image_url FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $old_image = $stmt->fetchColumn();
    
    if ($old_image && $old_image !== 'assets/images/default_profile.png') {
        $old_image_path = __DIR__ . '/../' . $old_image;
        if (file_exists($old_image_path)) {
            unlink($old_image_path);
        }
    }
} catch (PDOException $e) {
    error_log("Eski profil fotoğrafı silme hatası: " . $e->getMessage());
}

// Yeni fotoğrafı yükle
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    // Veritabanını güncelle
    try {
        $relative_path = 'uploads/profile_images/' . $new_filename;
        $stmt = $pdo->prepare("UPDATE users SET profile_image_url = ? WHERE id = ?");
        $stmt->execute([$relative_path, $_SESSION['user_id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Profil fotoğrafı başarıyla güncellendi.',
            'new_image_url' => $relative_path
        ]);
    } catch (PDOException $e) {
        error_log("Veritabanı güncelleme hatası: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Veritabanı güncellenirken bir hata oluştu.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Dosya yüklenirken bir hata oluştu.'
    ]);
}

ob_end_flush(); // Tamponu gönder ve kapat
exit; // Komut dosyasının hemen sonlandırılmasını sağla
 ?>