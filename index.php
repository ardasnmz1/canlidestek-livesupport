<?php 
ob_start(); // Start output buffering at the very top

// session_start() burada auth_check.php içinde yapılıyor olmalı
require_once __DIR__ . '/includes/auth_check.php'; // Giriş kontrolü

// Buraya kadar auth_check.php'den geçen kullanıcı, giriş yapmış olmalı
error_log("index.php after auth_check: SESSION dump: " . print_r($_SESSION, true));

require_once __DIR__ . '/includes/db_connect.php'; // $pdo için
require_once __DIR__ . '/includes/functions.php'; // getRoleNameByUserId ve escape için

$page_title = "Canlı Destek";

// Session'dan kullanıcı bilgilerini al
$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'] ?? '';
$current_user_role = $_SESSION['role_name'] ?? '';

// Tema (cookie veya session'dan okunabilir, varsayılan light)
$theme = isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : 'light-theme';

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($page_title); ?> - Okul Destek Sistemi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:,"> <!-- Boş favicon -->
</head>
<body class="<?php echo $theme; ?>">

    <div id="chat-container">
        <header>
            <div class="header-left">
                <button id="menu-toggle-button" class="header-button mobile-only-flex">☰</button>
                <h1>Canlı Destek Sistemi</h1>
            </div>
            <div class="header-right">
                <div id="user-info">
                    <span id="user-display">Hoş geldin, <?php echo escape($current_username); ?> (<?php echo escape($current_user_role); ?>)</span>
                </div>
                <a href="knowledge_base.php" class="header-button">Bilgi Bankası</a>
                <a href="support.php" class="header-button">Destek Talepleri</a>
                <a href="profile.php" class="header-button">Profilim</a>
                <button id="theme-toggle-button">Tema Değiştir</button>
                <button class="logout-button-styling" onclick="window.location.href='logout.php'">Çıkış Yap</button>
            </div>
        </header>

        <main id="main-chat-area">
            <aside id="sidebar">
                <!-- <div id="sidebar-navigation" class="mobile-only-block"> -->
                    <!-- Mobil sidebar navigasyonu buraya gelecek -->
                    <!-- <a href="support.php" class="sidebar-nav-item">Destek Talepleri</a> -->
                    <!-- <a href="profile.php" class="sidebar-nav-item">Profilim</a> -->
                    <!-- <button id="theme-toggle-button-sidebar" class="sidebar-nav-item">Tema Değiştir</button> -->
                    <!-- <button class="sidebar-nav-item logout-button-styling" onclick="window.location.href='logout.php'">Çıkış Yap</button> -->
                <!-- </div> -->
                <div id="user-list-container">
                    <h2>Kullanıcılar</h2>
                    <div class="search-user-container">
                        <input type="text" id="search-user-input" placeholder="Kullanıcı ara...">
                    </div>
                    <ul id="user-list">
                        <!-- Kullanıcılar JavaScript ile buraya yüklenecek -->
                        <li class="loading-users">Kullanıcılar yükleniyor...</li>
                    </ul>
                </div>
                <?php if ($current_user_role === 'teacher' || $current_user_role === 'admin'): ?>
                <div id="support-tickets">
                    <h2>Destek Talepleri</h2>
                    <ul id="support-requests-list">
                        <!-- Destek talepleri JavaScript ile buraya yüklenecek -->
                        <li class="loading-tickets">Talepler yükleniyor...</li>
                    </ul>
                </div>
                <?php endif; ?>
            </aside>

            <section id="chat-window-section">
                <div id="chat-window" style="display: none;"> 
                    <div id="chat-partner-info">
                        Sohbet Edilen: <span id="chat-partner-display"></span>
                    </div>
                    <div id="messages-container">
                        <!-- Mesajlar JavaScript ile buraya yüklenecek -->
                    </div>
                    <div id="message-input-area">
                        <textarea id="message-input" placeholder="Mesajınızı yazın..." rows="3"></textarea>
                        <div id="file-upload-area">
                             <label for="file-input" class="file-input-label" title="Dosya Ekle">📎</label>
                             <input type="file" id="file-input" style="display: none;">
                             <span id="file-name-display"></span>
                        </div>
                        <button id="send-message-button" type="button">Gönder</button>
                    </div>
                </div>
                <div id="no-chat-selected">
                    <p>Sohbet etmek için sol menüden bir kullanıcı veya destek talebi seçin.</p>
                </div>
            </section>
        </main>
    </div>

    <script>
        // JavaScript'e PHP değişkenlerini aktarmak için (güvenli bir şekilde)
        const currentUserID = <?php echo json_encode($current_user_id); ?>;
        const currentUserRole = <?php echo json_encode($current_user_role); ?>;
        const siteURL = <?php echo json_encode(SITE_URL); ?>; // API çağrıları için
    </script>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/file-upload.js"></script>
</body>
</html> 