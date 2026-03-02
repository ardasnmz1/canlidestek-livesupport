<?php
require_once __DIR__ . '/../config/config.php'; // SITE_URL ve diğer sabitler için

// Bu dosya, proje genelinde kullanılacak yardımcı PHP fonksiyonlarını içerecektir.

/**
 * Verilen e-posta veya kullanıcı adına göre kullanıcıyı veritabanından getirir.
 *
 * @param PDO $pdo Veritabanı bağlantı nesnesi.
 * @param string $email Aranacak e-posta.
 * @param string $username Aranacak kullanıcı adı.
 * @return array|false Kullanıcı bulunursa kullanıcı verilerini, yoksa false döner.
 */
function getUserByEmailOrUsername(PDO $pdo, string $email, string $username) {
    $sql = "SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = :email OR u.username = :username LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    return $stmt->fetch();
}

/**
 * Verilen e-postaya göre kullanıcıyı veritabanından getirir.
 *
 * @param PDO $pdo Veritabanı bağlantı nesnesi.
 * @param string $email Aranacak e-posta.
 * @return array|false Kullanıcı bulunursa kullanıcı verilerini, yoksa false döner.
 */
function getUserByEmail(PDO $pdo, string $email) {
    $sql = "SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    return $stmt->fetch();
}

/**
 * Verilen kullanıcı adına göre kullanıcıyı veritabanından getirir.
 *
 * @param PDO $pdo Veritabanı bağlantı nesnesi.
 * @param string $username Aranacak kullanıcı adı.
 * @return array|false Kullanıcı bulunursa kullanıcı verilerini, yoksa false döner.
 */
function getUserByUsername(PDO $pdo, string $username) {
    $sql = "SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = :username LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    return $stmt->fetch();
}


/**
 * Verilen rol adına göre rol ID'sini veritabanından alır.
 *
 * @param PDO $pdo Veritabanı bağlantı nesnesi.
 * @param string $role_name_param Rol adı (örn: 'student', 'teacher').
 * @return int|false Rol bulunursa ID'sini, yoksa false döner.
 */
function getRoleIdByName(PDO $pdo, string $role_name_param) {
    $sql = "SELECT id FROM roles WHERE name = :role_name_param LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':role_name_param', $role_name_param);
    $stmt->execute();
    $result = $stmt->fetch();
    return $result ? (int)$result['id'] : false;
}

/**
 * Kullanıcının giriş yapıp yapmadığını kontrol eder.
 * Session'da user_id olup olmadığına bakar.
 *
 * @return boolean True eğer kullanıcı giriş yapmışsa, aksi halde false.
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Kullanıcıyı belirtilen sayfaya yönlendirir.
 *
 * @param string $url Yönlendirilecek URL.
 */
function redirect(string $url) {
    error_log("=== YÖNLENDİRME BAŞLADI ===");
    error_log("Yönlendirilecek URL: " . $url);
    error_log("Mevcut session durumu: " . print_r($_SESSION, true));
    
    // Tam URL mi kontrol et
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        $redirect_url = $url;
    } else {
        // Göreceli URL için site URL'sini kullan
        $redirect_url = SITE_URL . "/" . ltrim($url, '/');
    }
    
    error_log("Hazırlanan yönlendirme URL'si: " . $redirect_url);
    
    // Yönlendirme başlığını ayarla
    header("Location: $redirect_url");
    
    // Loglama
    error_log("Yönlendirme: " . debug_backtrace()[0]['file'] . " dosyasından " . $redirect_url . " sayfasına yönlendiriliyor");
    
    exit;
}

/**
 * Güvenli HTML çıktısı için özel karakterleri dönüştürür.
 *
 * @param string|null $string Temizlenecek string.
 * @return string Temizlenmiş string.
 */
function escape(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * İki kullanıcı ID'sinden benzersiz bir sohbet ID'si oluşturur.
 * ID'ler her zaman aynı sırada birleştirilir (küçük olan önce).
 *
 * @param int $userId1 Birinci kullanıcının ID'si.
 * @param int $userId2 İkinci kullanıcının ID'si.
 * @return string Oluşturulan sohbet ID'si.
 */
function generateChatId(int $userId1, int $userId2): string {
    if ($userId1 < $userId2) {
        return $userId1 . '_' . $userId2;
    }
    return $userId2 . '_' . $userId1;
}

/**
 * Verilen kullanıcı ID'sine göre kullanıcının rol adını veritabanından alır.
 *
 * @param PDO $pdo Veritabanı bağlantı nesnesi.
 * @param int $userId Kullanıcının ID'si.
 * @return string|false Rol adı bulunursa string, yoksa false döner.
 */
function getRoleNameByUserId(PDO $pdo, int $userId): ?string {
    $sql = "SELECT r.name 
            FROM roles r
            JOIN users u ON r.id = u.role_id
            WHERE u.id = :user_id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['name'] : null;
}

?> 