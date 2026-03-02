<?php
ob_start();
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$profile_user_id = $current_user_id; // Şimdilik sadece kendi profilini göster

// Kullanıcı bilgilerini çek
$user_profile = null;
try {
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.profile_image_url, r.name as role_name 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE u.id = ?");
    $stmt->execute([$profile_user_id]);
    $user_profile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Profile Page Error: " . $e->getMessage());
    // Hata durumunda bir mesaj gösterilebilir veya ana sayfaya yönlendirilebilir
}

if (!$user_profile) {
    // Kullanıcı bulunamazsa bir hata mesajı veya yönlendirme
    echo "Kullanıcı bulunamadı.";
    exit;
}

$page_title = escape($user_profile['username']) . " Profili";
$theme = isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : 'light-theme';

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Okul Destek Sistemi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .dark-theme .profile-container {
            background-color: #333;
            color: #f1f1f1;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid #ddd;
        }
        .dark-theme .profile-image {
            border-color: #555;
        }
        .profile-info h2 {
            margin: 0;
            font-size: 24px;
        }
        .profile-info p {
            margin: 5px 0;
            color: #555;
        }
        .dark-theme .profile-info p {
            color: #ccc;
        }
        .profile-details .detail-item {
            margin-bottom: 10px;
        }
        .profile-details .detail-item strong {
            display: inline-block;
            width: 120px;
        }
    </style>
</head>
<body class="<?php echo $theme; ?>">
    
    <?php /* Header (Navigasyon) buraya dahil edilecek veya oluşturulacak */ ?>
    <div class="nav-bar" style="background-color: #333; padding: 10px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <a href="index.php" style="color: white; text-decoration: none; margin-right: 15px;">Ana Sayfa</a>
            <a href="support.php" style="color: white; text-decoration: none; margin-right: 15px;">Destek Talepleri</a>
        </div>
        <div class="user-menu">
            <span style="color: white; margin-right: 10px;">Hoş geldin, <?php echo escape($_SESSION['username']); ?>!</span>
            <a href="profile.php" style="color: white; text-decoration: none; margin-right: 10px;">Profilim</a>
            <a href="logout.php" style="color: white; text-decoration: none;">Çıkış Yap</a>
        </div>
    </div>

    <div class="profile-container">
        <div class="profile-header">
            <img src="<?php echo escape($user_profile['profile_image_url'] ?? 'assets/images/default_profile.png'); ?>" 
                 alt="Profil Fotoğrafı" class="profile-image" 
                 onerror="this.onerror=null; this.src='assets/images/default_profile.png';">
            <div class="profile-info">
                <h2><?php echo escape($user_profile['username']); ?></h2>
                <p><?php echo escape($user_profile['role_name']); ?></p>
            </div>
        </div>
        
        <div class="profile-details">
            <h3>Profil Bilgileri</h3>
            <div class="detail-item">
                <strong>Kullanıcı Adı:</strong>
                <span><?php echo escape($user_profile['username']); ?></span>
            </div>
            <div class="detail-item">
                <strong>E-posta:</strong>
                <span><?php echo escape($user_profile['email']); ?></span>
            </div>
            <div class="detail-item">
                <strong>Rol:</strong>
                <span><?php echo escape($user_profile['role_name']); ?></span>
            </div>
            <?php /* İleride buraya daha fazla profil detayı eklenebilir */ ?>
        </div>

        <div class="profile-edit-section" style="margin-top: 30px;">
            <h4>Profil Fotoğrafını Güncelle</h4>
            <form id="profile-image-form" enctype="multipart/form-data">
                <input type="file" name="profile_image" id="profile_image_input" accept="image/jpeg, image/png, image/gif">
                <button type="submit">Fotoğrafı Yükle</button>
                <div id="profile-image-message" style="margin-top: 10px;"></div>
            </form>
        </div>
        
        <a href="#" onclick="alert('Diğer profil düzenleme özellikleri yakında eklenecektir.'); return false;" style="display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">Diğer Bilgileri Düzenle</a>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileImageForm = document.getElementById('profile-image-form');
            const profileImageMessage = document.getElementById('profile-image-message');
            const currentProfileImageElement = document.querySelector('.profile-image');

            if (profileImageForm) {
                profileImageForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    profileImageMessage.textContent = 'Yükleniyor...';
                    profileImageMessage.style.color = 'inherit';

                    const formData = new FormData(this);

                    try {
                        const response = await fetch('api/upload_profile_image.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();

                        if (result.success) {
                            profileImageMessage.textContent = result.message;
                            profileImageMessage.style.color = 'green';
                            if (result.new_image_url && currentProfileImageElement) {
                                // Rastgele bir query string ekleyerek tarayıcı önbelleğini atlat
                                currentProfileImageElement.src = result.new_image_url + '?t=' + new Date().getTime();
                                // Header'daki küçük profil resmini de güncelle (eğer varsa ve id'si biliniyorsa)
                                const headerProfileImg = document.getElementById('header-profile-img'); // Bu ID'yi header'a eklemeniz gerekebilir
                                if(headerProfileImg) {
                                     headerProfileImg.src = result.new_image_url + '?t=' + new Date().getTime();
                                }
                            }
                            // Formu sıfırla
                            document.getElementById('profile_image_input').value = ''; 
                        } else {
                            profileImageMessage.textContent = 'Hata: ' + result.message;
                            profileImageMessage.style.color = 'red';
                        }
                    } catch (error) {
                        console.error('Profil fotoğrafı yükleme hatası:', error);
                        profileImageMessage.textContent = 'Bir ağ hatası veya sunucu hatası oluştu.';
                        profileImageMessage.style.color = 'red';
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?> 