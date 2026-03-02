<?php 
ob_start();
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

// Session kontrolü
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
    exit;
}

// Kullanıcı bilgileri
$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'] ?? 'Misafir';
$current_user_role = $_SESSION['role_name'] ?? 'guest';

// URL'den talep ID'sini al
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ticket_id <= 0) {
    redirect('support.php');
    exit;
}

// Tema
$theme = isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : 'light-theme';

// Talep detaylarını ve yetkiyi kontrol et
$ticket = null;
$can_reply = false;
$can_mark_solution = false;

try {
    $sql = "SELECT t.*, u.username AS student_username, 
            a.username AS assigned_username
            FROM support_tickets t
            JOIN users u ON t.student_id = u.id
            LEFT JOIN users a ON t.assigned_to_id = a.id
            WHERE t.id = :ticket_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':ticket_id', $ticket_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        redirect('support.php');
        exit;
    }
    
    // Yetki kontrolü - öğrenci, atanmış kişi veya admin/teacher
    if ($ticket['student_id'] == $current_user_id) {
        $can_reply = true; // Talebi oluşturan öğrenci
    } else if ($ticket['assigned_to_id'] == $current_user_id) {
        $can_reply = true; // Atanmış öğretmen/admin
        $can_mark_solution = true; // Çözüm işaretleme yetkisi
    } else if ($current_user_role == 'admin' || $current_user_role == 'teacher') {
        $can_reply = true; // Herhangi bir admin veya öğretmen
        $can_mark_solution = true; // Çözüm işaretleme yetkisi
    } else {
        // Yetkisi yoksa destek sayfasına yönlendir
        redirect('support.php');
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Ticket Detail Error: " . $e->getMessage());
    redirect('support.php');
    exit;
}

$page_title = "Destek Talebi: " . escape($ticket['subject']);

// Destek talebi cevaplarını al
$replies = [];

try {
    $sql = "SELECT r.*, u.username, u.role_id, ro.name AS role_name,
            (SELECT COUNT(*) FROM likes WHERE reply_id = r.id) AS like_count,
            (SELECT COUNT(*) FROM likes WHERE reply_id = r.id AND user_id = :current_user_id) AS user_liked
            FROM ticket_replies r
            JOIN users u ON r.user_id = u.id
            JOIN roles ro ON u.role_id = ro.id
            WHERE r.ticket_id = :ticket_id
            ORDER BY r.created_at ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':ticket_id', $ticket_id, PDO::PARAM_INT);
    $stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Ticket Replies Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($page_title); ?> - Okul Destek Sistemi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .ticket-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .ticket-header {
            background-color: #f0f0f0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .dark-theme .ticket-header {
            background-color: #333;
            color: #f9f9f9;
        }
        
        .ticket-title {
            font-size: 24px;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .ticket-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        .dark-theme .ticket-meta {
            color: #ccc;
        }
        
        .ticket-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-open {
            background-color: #ffeb3b;
            color: #333;
        }
        
        .status-pending {
            background-color: #ff9800;
            color: white;
        }
        
        .status-in_progress {
            background-color: #2196F3;
            color: white;
        }
        
        .status-resolved {
            background-color: #4CAF50;
            color: white;
        }
        
        .status-closed {
            background-color: #9e9e9e;
            color: white;
        }
        
        .ticket-description {
            margin-top: 10px;
            white-space: pre-wrap;
        }
        
        .ticket-replies {
            margin-top: 30px;
        }
        
        .reply-card {
            background-color: white;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #ccc;
        }
        
        .dark-theme .reply-card {
            background-color: #444;
            color: #f9f9f9;
            border-left-color: #666;
        }
        
        .reply-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .reply-author {
            font-weight: bold;
        }
        
        .role-tag {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .role-student {
            background-color: #E3F2FD;
            color: #0D47A1;
        }
        
        .role-teacher {
            background-color: #E8F5E9;
            color: #2E7D32;
        }
        
        .role-admin {
            background-color: #FFEBEE;
            color: #C62828;
        }
        
        .dark-theme .role-student {
            background-color: #0D47A1;
            color: #E3F2FD;
        }
        
        .dark-theme .role-teacher {
            background-color: #2E7D32;
            color: #E8F5E9;
        }
        
        .dark-theme .role-admin {
            background-color: #C62828;
            color: #FFEBEE;
        }
        
        .reply-date {
            color: #666;
            font-size: 12px;
        }
        
        .dark-theme .reply-date {
            color: #ccc;
        }
        
        .reply-text {
            margin-top: 10px;
            white-space: pre-wrap;
        }
        
        .reply-actions {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            align-items: center;
        }
        
        .like-button {
            background: none;
            border: none;
            padding: 0;
            color: #666;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        
        .dark-theme .like-button {
            color: #ccc;
        }
        
        .liked {
            color: #e91e63;
        }
        
        .dark-theme .liked {
            color: #f06292;
        }
        
        .solution-badge {
            background-color: #4CAF50;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: auto;
        }
        
        .mark-solution-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-left: auto;
        }
        
        .mark-solution-button:hover {
            background-color: #45a049;
        }
        
        .reply-attachment {
            margin-top: 15px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        
        .dark-theme .reply-attachment {
            background-color: #333;
        }
        
        .attachment-link {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #2196F3;
            text-decoration: none;
        }
        
        .dark-theme .attachment-link {
            color: #64B5F6;
        }
        
        .attachment-link:hover {
            text-decoration: underline;
        }
        
        .reply-form {
            background-color: #f0f0f0;
            border-radius: 6px;
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .dark-theme .reply-form {
            background-color: #333;
            color: #f9f9f9;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 100px;
            resize: vertical;
            font-size: 14px;
        }
        
        .dark-theme .form-group textarea {
            background-color: #444;
            color: #fff;
            border-color: #555;
        }
        
        .checkbox-group {
            margin-top: 10px;
        }
        
        .submit-btn {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .submit-btn:hover {
            background-color: #0b7dda;
        }
        
        .nav-bar {
            display: flex;
            background-color: #333;
            padding: 10px 20px;
            margin-bottom: 20px;
        }
        
        .nav-bar a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            font-weight: bold;
        }
        
        .nav-bar a:hover {
            text-decoration: underline;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 10px;
            color: #2196F3;
            text-decoration: none;
        }
        
        .dark-theme .back-link {
            color: #64B5F6;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        /* Durum düğmeleri için stil */
        .status-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .status-button {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
        }
        
        .status-button-open {
            background-color: #ffeb3b;
            color: #333;
        }
        
        .status-button-in_progress {
            background-color: #2196F3;
            color: white;
        }
        
        .status-button-resolved {
            background-color: #4CAF50;
            color: white;
        }
        
        .status-button-closed {
            background-color: #9e9e9e;
            color: white;
        }
    </style>
</head>
<body class="<?php echo $theme; ?>">
    <div class="nav-bar">
        <a href="index.php">Ana Sayfa</a>
        <a href="support.php">Destek Talepleri</a>
        <a href="logout.php">Çıkış Yap</a>
    </div>

    <div class="ticket-container">
        <a href="support.php" class="back-link">← Destek Taleplerine Dön</a>
        
        <div class="ticket-header">
            <div>
                <span class="ticket-title"><?php echo escape($ticket['subject']); ?></span>
                <span class="ticket-status status-<?php echo $ticket['status']; ?>"><?php echo getStatusText($ticket['status']); ?></span>
            </div>
            
            <div class="ticket-meta">
                <div>Oluşturan: <?php echo escape($ticket['student_username']); ?></div>
                <div>Atanan: <?php echo $ticket['assigned_username'] ? escape($ticket['assigned_username']) : 'Henüz atanmadı'; ?></div>
                <div>Oluşturma: <?php echo formatDateTime($ticket['created_at']); ?></div>
                <div>Güncelleme: <?php echo formatDateTime($ticket['updated_at']); ?></div>
            </div>
            
            <?php if ($ticket['description']): ?>
            <div class="ticket-description"><?php echo escape($ticket['description']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($ticket['file_path']) && !empty(trim($ticket['file_path']))): ?>
            <div class="ticket-main-attachment" style="margin-top:15px; padding:10px; background-color: #e9ecef; border: 1px solid #dee2e6; border-radius:5px;">
                <strong>Ana Talep Eki:</strong><br>
                <?php
                $mainFilePath = escape(trim($ticket['file_path']));
                $mainFileExtension = strtolower(pathinfo($mainFilePath, PATHINFO_EXTENSION));
                $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                $baseSiteUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
                // Eğer $mainFilePath zaten tam bir URL ise (http ile başlıyorsa) SITE_URL ekleme.
                // Değilse ve SITE_URL tanımlıysa, SITE_URL ile birleştir.
                // SITE_URL tanımlı değilse ve http ile başlamıyorsa, göreceli yol olarak bırak (pek istenmez ama fallback).
                if (strpos($mainFilePath, 'http') === 0) {
                    $fullUrl = $mainFilePath;
                } elseif (!empty($baseSiteUrl)) {
                    $fullUrl = $baseSiteUrl . '/' . ltrim($mainFilePath, '/');
                } else {
                    $fullUrl = ltrim($mainFilePath, '/'); // Göreceli yol olarak fallback
                }
                ?>
                <?php if (in_array($mainFileExtension, $imageExtensions)): ?>
                    <a href="<?php echo $fullUrl; ?>" target="_blank">
                         <img src="<?php echo $fullUrl; ?>" alt="Ekli Resim" style="max-width:100%; max-height:300px; display:block; margin-top:8px; border-radius:4px; border: 1px solid #ccc;">
                    </a>
                <?php else: ?>
                    <a href="<?php echo $fullUrl; ?>" target="_blank" class="attachment-link" style="display:inline-block; margin-top:8px;">📎 Dosyayı Görüntüle/İndir (<?php echo basename($mainFilePath); ?>)</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($can_mark_solution): ?>
            <div class="status-buttons">
                <h4>Durumu Değiştir:</h4>
                <button class="status-button status-button-open" data-status="open">Açık</button>
                <button class="status-button status-button-in_progress" data-status="in_progress">İşleniyor</button>
                <button class="status-button status-button-resolved" data-status="resolved">Çözüldü</button>
                <button class="status-button status-button-closed" data-status="closed">Kapatıldı</button>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="ticket-replies">
            <h2>Cevaplar</h2>
            
            <?php if (empty($replies)): ?>
            <p>Henüz hiç cevap yok.</p>
            <?php else: ?>
                <?php foreach ($replies as $reply): ?>
                <div class="reply-card">
                    <div class="reply-header">
                        <div>
                            <span class="reply-author"><?php echo escape($reply['username']); ?></span>
                            <span class="role-tag role-<?php echo strtolower($reply['role_name']); ?>"><?php echo ucfirst(escape($reply['role_name'])); ?></span>
                        </div>
                        <span class="reply-date"><?php echo formatDateTime($reply['created_at']); ?></span>
                    </div>
                    
                    <div class="reply-text"><?php echo escape($reply['reply_text']); ?></div>
                    
                    <?php if (isset($reply['file_path']) && $reply['file_path']): ?>
                    <div class="reply-attachment">
                        <a href="<?php echo escape($reply['file_path']); ?>" class="attachment-link" target="_blank">
                            📎 Ek Dosya
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="reply-actions">
                        <?php if ($reply['user_id'] != $current_user_id): ?>
                        <button class="like-button <?php echo $reply['user_liked'] ? 'liked' : ''; ?>" 
                                data-reply-id="<?php echo $reply['id']; ?>"
                                data-action="<?php echo $reply['user_liked'] ? 'unlike' : 'like'; ?>">
                            <?php echo $reply['user_liked'] ? '❤️' : '🤍'; ?> 
                            <span class="like-count"><?php echo (int)$reply['like_count']; ?></span>
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($reply['is_solution']): ?>
                        <div class="solution-badge">✓ Çözüm</div>
                        <?php elseif ($can_mark_solution && $ticket['status'] !== 'resolved' && $ticket['status'] !== 'closed'): ?>
                        <button class="mark-solution-button" data-reply-id="<?php echo $reply['id']; ?>">
                            Çözüm Olarak İşaretle
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($can_reply && $ticket['status'] !== 'resolved' && $ticket['status'] !== 'closed'): ?>
        <div class="reply-form">
            <h2>Cevap Yaz</h2>
            <form id="reply-form" enctype="multipart/form-data">
                <input type="hidden" name="ticket_id" value="<?php echo $ticket_id; ?>">
                
                <div class="form-group">
                    <label for="reply_text">Cevabınız:</label>
                    <textarea id="reply_text" name="reply_text" required placeholder="Cevabınızı yazın..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="attachment">Dosya Ekle (isteğe bağlı):</label>
                    <input type="file" id="attachment" name="attachment">
                </div>
                
                <?php if ($can_mark_solution): ?>
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="is_solution" value="1"> Çözüm olarak işaretle
                    </label>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="submit-btn">Cevabı Gönder</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // JavaScript'e PHP değişkenlerini aktarmak için
        const currentUserID = <?php echo json_encode($current_user_id); ?>;
        const currentUserRole = <?php echo json_encode($current_user_role); ?>;
        const siteURL = <?php echo json_encode(SITE_URL); ?>;
        const ticketId = <?php echo json_encode($ticket_id); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Cevap formunu gönderme
            const replyForm = document.getElementById('reply-form');
            if (replyForm) {
                replyForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(replyForm);
                    
                    // Cevabı gönder
                    fetch('api/reply_support_ticket.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Sayfayı yenile ve cevapları güncelle
                            window.location.reload();
                        } else {
                            alert('Hata: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Hata:', error);
                        alert('Sistem hatası oluştu, lütfen daha sonra tekrar deneyin.');
                    });
                });
            }
            
            // Durum değiştirme düğmeleri
            const statusButtons = document.querySelectorAll('.status-button');
            if (statusButtons) {
                statusButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const newStatus = this.dataset.status;
                        
                        // Durumu değiştirme işlemi
                        fetch('api/update_ticket_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `ticket_id=${ticketId}&status=${newStatus}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Sayfayı yenile
                                window.location.reload();
                            } else {
                                alert('Hata: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Hata:', error);
                            alert('Sistem hatası oluştu, lütfen daha sonra tekrar deneyin.');
                        });
                    });
                });
            }
            
            // Beğenme düğmeleri
            const likeButtons = document.querySelectorAll('.like-button');
            likeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const replyId = this.dataset.replyId;
                    const action = this.dataset.action;
                    
                    // Beğen/Beğenmeyi Kaldır
                    fetch('api/like_reply.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `reply_id=${replyId}&action=${action}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // UI güncelle
                            const likeCountSpan = button.querySelector('.like-count');
                            likeCountSpan.textContent = data.like_count;
                            
                            if (action === 'like') {
                                button.classList.add('liked');
                                button.dataset.action = 'unlike';
                                button.innerHTML = `❤️ <span class="like-count">${data.like_count}</span>`;
                            } else {
                                button.classList.remove('liked');
                                button.dataset.action = 'like';
                                button.innerHTML = `🤍 <span class="like-count">${data.like_count}</span>`;
                            }
                        } else {
                            alert('Hata: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Hata:', error);
                        alert('Sistem hatası oluştu, lütfen daha sonra tekrar deneyin.');
                    });
                });
            });
            
            // Çözüm olarak işaretleme düğmeleri
            const solutionButtons = document.querySelectorAll('.mark-solution-button');
            solutionButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const replyId = this.dataset.replyId;
                    
                    // Çözüm olarak işaretle
                    const formData = new FormData();
                    formData.append('ticket_id', ticketId);
                    formData.append('reply_id', replyId);
                    formData.append('is_solution', '1');
                    formData.append('reply_text', 'Bu cevap çözüm olarak işaretlendi.');
                    
                    fetch('api/reply_support_ticket.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Sayfayı yenile
                            window.location.reload();
                        } else {
                            alert('Hata: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Hata:', error);
                        alert('Sistem hatası oluştu, lütfen daha sonra tekrar deneyin.');
                    });
                });
            });
        });
    </script>
</body>
</html>

<?php
/**
 * Durum adını Türkçeye çevir
 */
function getStatusText($status) {
    $statusTexts = [
        'open' => 'Açık',
        'pending' => 'Beklemede',
        'in_progress' => 'İşleniyor',
        'resolved' => 'Çözüldü',
        'closed' => 'Kapatıldı'
    ];
    
    return $statusTexts[$status] ?? $status;
}

/**
 * Tarih formatla
 */
function formatDateTime($dateTime) {
    $date = new DateTime($dateTime);
    return $date->format('d.m.Y H:i');
} 