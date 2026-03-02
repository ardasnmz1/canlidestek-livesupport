<?php 
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Kayıt Ol";
$errors = [];
$success_message = '';

// Öğretmen rolü için gerekli şifre
define('TEACHER_ACCESS_CODE', 'ogretmen123');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $role_name = $_POST['role'];
    $teacher_code = isset($_POST['teacher_code']) ? $_POST['teacher_code'] : '';

    if (empty($name)) {
        $errors[] = "Kullanıcı adı gereklidir.";
    }
    if (empty($email)) {
        $errors[] = "E-posta adresi gereklidir.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçersiz e-posta formatı.";
    }
    if (empty($password)) {
        $errors[] = "Şifre gereklidir.";
    }
    if ($password !== $password_confirm) {
        $errors[] = "Şifreler eşleşmiyor.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Şifre en az 6 karakter olmalıdır.";
    }
    if (empty($role_name) || !in_array($role_name, ['student', 'teacher', 'visitor'])) {
        $errors[] = "Geçersiz kullanıcı rolü seçildi.";
    }

    // Öğretmen rolü seçildiyse şifre kontrolü
    if ($role_name === 'teacher') {
        if (empty($teacher_code)) {
            $errors[] = "Öğretmen erişim kodu gereklidir.";
        } elseif ($teacher_code !== TEACHER_ACCESS_CODE) {
            $errors[] = "Geçersiz öğretmen erişim kodu.";
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email OR username = :username LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':username', $name);
        $stmt->execute();
        $existingUser = $stmt->fetch();
        if ($existingUser) {
            $errors[] = "Bu e-posta veya kullanıcı adı zaten kayıtlı.";
        }
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role_id = getRoleIdByName($pdo, $role_name);
        if (!$role_id) {
            $errors[] = "Rol bulunamadı. Lütfen yönetici ile iletişime geçin.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role_id) VALUES (:username, :email, :password_hash, :role_id)");
            $stmt->bindParam(':username', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password_hash', $hashed_password);
            $stmt->bindParam(':role_id', $role_id);
            if ($stmt->execute()) {
                header("Location: login.php?registration=success");
                exit();
            } else {
                $errors[] = "Kayıt sırasında bir hata oluştu.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Canlı Destek Sistemi</title>
    <link rel="stylesheet" href="assets/css/style.css"> 
</head>
<body class="light-theme auth-page">
    <div class="auth-container">
        <h2><?php echo $page_title; ?></h2>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div>
                <label for="name">Kullanıcı Adı:</label>
                <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
            </div>
            <div>
                <label for="email">E-posta:</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            <div>
                <label for="password">Şifre:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div>
                <label for="password_confirm">Şifre Tekrar:</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <div>
                <label for="role">Rolünüz:</label>
                <select id="role" name="role" required onchange="toggleTeacherCode(this.value)">
                    <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] == 'student') ? 'selected' : ''; ?>>Öğrenci</option>
                    <option value="teacher" <?php echo (isset($_POST['role']) && $_POST['role'] == 'teacher') ? 'selected' : ''; ?>>Öğretmen</option>
                    <option value="visitor" <?php echo (isset($_POST['role']) && $_POST['role'] == 'visitor') ? 'selected' : ''; ?>>Ziyaretçi</option>
                </select>
            </div>
            <div id="teacher_code_container" style="display: none;">
                <label for="teacher_code">Öğretmen Erişim Kodu:</label>
                <input type="password" id="teacher_code" name="teacher_code">
            </div>
            <button type="submit">Kayıt Ol</button>
        </form>
        <p>Zaten bir hesabınız var mı? <a href="login.php">Giriş Yapın</a></p>
    </div>

    <script>
    function toggleTeacherCode(role) {
        const teacherCodeContainer = document.getElementById('teacher_code_container');
        const teacherCodeInput = document.getElementById('teacher_code');
        
        if (role === 'teacher') {
            teacherCodeContainer.style.display = 'block';
            teacherCodeInput.required = true;
        } else {
            teacherCodeContainer.style.display = 'none';
            teacherCodeInput.required = false;
            teacherCodeInput.value = ''; // Rolü değiştirince şifreyi temizle
        }
    }

    // Sayfa yüklendiğinde mevcut role göre şifre alanını göster/gizle
    document.addEventListener('DOMContentLoaded', function() {
        toggleTeacherCode(document.getElementById('role').value);
    });
    </script>
</body>
</html> 