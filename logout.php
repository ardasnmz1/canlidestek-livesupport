<?php 
require_once __DIR__ . '/includes/db_connect.php'; // $pdo değişkeni için
require_once __DIR__ . '/includes/functions.php'; // redirect fonksiyonu ve SESSION_NAME için

// Kullanıcı giriş yapmış mı kontrol et
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Çıkış yapılacak kullanıcının bilgilerini kaydet
$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'Bilinmeyen Kullanıcı';
$sessionId = session_id();

if (isset($_SESSION['user_id'])) {
    error_log("Logout işlemi başlatıldı: user_id={$user_id}, username={$username}, session_id={$sessionId}");

    // Online durumu güncelle
    try {
        $updateOnlineStatusSql = "UPDATE users SET is_online = FALSE, last_seen = CURRENT_TIMESTAMP WHERE id = :user_id";
        $stmtOnline = $pdo->prepare($updateOnlineStatusSql);
        $stmtOnline->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmtOnline->execute();
        error_log("Kullanıcı çevrimdışı olarak işaretlendi: user_id={$user_id}");
    } catch (PDOException $e) {
        // Hata olsa bile çıkış işlemine devam et
        error_log("Çıkış sırasında online durum güncellenemedi: " . $e->getMessage());
    }
}

// Session değişkenlerini temizle
$_SESSION = array();

// Session cookie'sini sil
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session'ı yok et
session_destroy();

if ($user_id) {
    error_log("Logout işlemi tamamlandı: user_id={$user_id}, username={$username}, session_id={$sessionId}");
}

// Kesinlikle yeni bir sayfa yükleyelim - çerezleri temizlemek için ayrıca JavaScript kullanacağız
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Çıkış Yapılıyor...</title>
    <script>
    // Tarayıcıdaki tüm çerezleri temizle
    function clearAllCookies() {
        document.cookie.split(";").forEach(function(c) {
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
        });
    }
    
    // Çıkış işlemi tamamlandığında login sayfasına git
    window.onload = function() {
        clearAllCookies();
        window.location.href = "<?php echo SITE_URL; ?>/login.php?logout=success";
    };
    </script>
</head>
<body>
    <div style="text-align: center; padding: 50px;">
        <h2>Çıkış yapılıyor, lütfen bekleyin...</h2>
        <p>Otomatik olarak giriş sayfasına yönlendirileceksiniz.</p>
        <p>Yönlendirilmezseniz <a href="login.php?logout=success">buraya tıklayın</a>.</p>
    </div>
</body>
</html> 