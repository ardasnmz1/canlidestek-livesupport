<?php
// Veritabanı Ayarları
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'live_support_chat');

// Site Ayarları
define('SITE_URL', rtrim('http://localhost/canlidestek', '/'));
define('DEBUG_MODE', false);

// Saat Dilimi
date_default_timezone_set('Europe/Istanbul');

// Hata Raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Bu dosya sadece oturum başlatılmadan önce çağrılmalı
if (session_status() === PHP_SESSION_NONE) {
    session_name('LiveSupportSession');
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // HTTPS kullanıyorsan 1 yap
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', 10800); // 3 saat

    session_start();
}



// Session süresini kontrol et (sadece giriş yapmış kullanıcılar için)
if (isset($_SESSION['user_id']) && isset($_SESSION['login_time'])) {
    if (time() - $_SESSION['login_time'] > 10800) { // 3 saat
        session_unset();
        session_destroy();
        if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
            header("Location: " . SITE_URL . "/login.php?auth=timeout");
            exit;
        }
    } else {
        // Session süresini yenile
        $_SESSION['login_time'] = time();
    }
}
?> 