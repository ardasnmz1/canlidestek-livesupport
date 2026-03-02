<?php
// Bu script, çağrıldığı sayfada session kontrolü yapar ve oturum güvenliğini sağlar

// Daha önceden session başlatılmış mı kontrol et
if (session_status() == PHP_SESSION_NONE) {
    // Session'ı başlat
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

// Geçerli sayfayı al
$current_page = basename($_SERVER['PHP_SELF']);

// Login sayfası kontrolü
if (basename($_SERVER['PHP_SELF']) === 'login.php') {
    return;
}

// Login sayfası dışındaki tüm sayfalar için oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "/login.php");
    exit;
}

// Oturum süresi kontrolü - 3 saat
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 10800)) {
    session_unset();
    session_destroy();
    header("Location: " . SITE_URL . "/login.php?auth=timeout");
    exit;
}

// Tarayıcı kontrolü
if (isset($_SESSION['browser']) && $_SESSION['browser'] !== ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown')) {
    session_unset();
    session_destroy();
    header("Location: " . SITE_URL . "/login.php?auth=security");
    exit;
}

// Kullanıcı gerçekten var mı kontrol et (isteğe bağlı - performans için devre dışı bırakılmış)
/*
try {
    require_once __DIR__ . '/db_connect.php';
    $checkUserSql = "SELECT id FROM users WHERE id = :user_id LIMIT 1";
    $checkUserStmt = $pdo->prepare($checkUserSql);
    $checkUserStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $checkUserStmt->execute();
    
    if (!$checkUserStmt->fetch()) {
        // Kullanıcı veritabanında yok, session'ı temizle
        $_SESSION = [];
        session_destroy();
        redirect('login.php?auth=invalid');
        exit;
    }
} catch (Exception $e) {
    error_log("Auth Check DB Error: " . $e->getMessage());
}
*/
?> 