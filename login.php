<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db_connect.php';

// Zaten giriş yapmış kullanıcıyı ana sayfaya yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "E-posta ve şifre gereklidir.";
    } else {
        try {
            // Kullanıcıyı sorgula
            $stmt = $pdo->prepare("SELECT u.*, r.name as role_name 
                                 FROM users u 
                                 JOIN roles r ON u.role_id = r.id 
                                 WHERE u.email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Session'ı temizle
                session_regenerate_id(true);
                $_SESSION = array();
                
                // Kullanıcı bilgilerini session'a kaydet
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['login_time'] = time();
                $_SESSION['profile_image_url'] = $user['profile_image_url'];
                
                // Ana sayfaya yönlendir
                header("Location: index.php");
                exit;
            } else {
                $error = "Geçersiz email veya şifre";
            }
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            $error = "Bir hata oluştu, lütfen daha sonra tekrar deneyin.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Canlı Destek Sistemi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h2>Giriş Yap</h2>
        
        <?php if (!empty($error)): ?>
            <div class="errors">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div>
                <label for="email">E-posta:</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>
            <div>
                <label for="password">Şifre:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Giriş Yap</button>
        </form>
        <p>Hesabınız yok mu? <a href="register.php">Kayıt Olun</a></p>
    </div>
</body>
</html> 