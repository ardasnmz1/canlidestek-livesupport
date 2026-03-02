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

// Tema
$theme = isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-theme' : 'light-theme';

$page_title = "Destek Talepleri";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($page_title); ?> - Okul Destek Sistemi</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .support-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 20px;
        }
        
        .support-tickets-list {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .dark-theme .support-tickets-list {
            background-color: #333;
            color: #f9f9f9;
        }
        
        .ticket-form {
            background-color: #f0f0f0;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .dark-theme .ticket-form {
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
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .dark-theme .form-group input, .dark-theme .form-group textarea {
            background-color: #444;
            color: #fff;
            border-color: #555;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .submit-btn:hover {
            background-color: #45a049;
        }
        
        .ticket-card {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: white;
        }
        
        .dark-theme .ticket-card {
            background-color: #444;
            border-color: #555;
        }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .ticket-title {
            font-weight: bold;
            font-size: 18px;
        }
        
        .ticket-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
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
        
        .ticket-date {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .dark-theme .ticket-date {
            color: #ccc;
        }
        
        .ticket-description {
            margin-top: 10px;
            color: #333;
        }
        
        .dark-theme .ticket-description {
            color: #f0f0f0;
        }
        
        .no-tickets {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }
        
        .dark-theme .no-tickets {
            color: #ccc;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
        }
        
        .view-ticket-btn {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .view-ticket-btn:hover {
            background-color: #0b7dda;
        }
        
        .ticket-actions {
            margin-top: 10px;
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
    </style>
</head>
<body class="<?php echo $theme; ?>">
    <div class="nav-bar" style="background-color: #333; padding: 10px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <a href="index.php" style="color: white; text-decoration: none; margin-right: 15px;">Ana Sayfa</a>
            <a href="support.php" style="color: white; text-decoration: none; margin-right: 15px;">Destek Talepleri</a>
        </div>
        <div class="user-menu" style="display: flex; align-items: center;">
            <?php if(isset($_SESSION['user_id'])): ?>
                <img id="header-profile-img" src="<?php echo escape($_SESSION['profile_image_url'] ?? 'assets/images/default_profile.png'); ?>" 
                     alt="Profil" 
                     style="width: 30px; height: 30px; border-radius: 50%; margin-right: 8px; cursor: pointer;"
                     onclick="window.location.href='profile.php';"
                     onerror="this.onerror=null; this.src='assets/images/default_profile.png';">
                <span style="color: white; margin-right: 10px;">Hoş geldin, <?php echo escape($_SESSION['username']); ?>!</span>
                <a href="profile.php" style="color: white; text-decoration: none; margin-right: 10px;">Profilim</a>
                <a href="logout.php" style="color: white; text-decoration: none;">Çıkış Yap</a>
            <?php else: ?>
                <a href="login.php" style="color: white; text-decoration: none; margin-right: 10px;">Giriş Yap</a>
                <a href="register.php" style="color: white; text-decoration: none;">Kayıt Ol</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="support-container">
        <h1><?php echo escape($page_title); ?></h1>
        
        <!-- Destek Talepleri Listesi -->
        <div class="support-tickets-list">
            <h2>Aktif Taleplerim</h2>
            <div id="tickets-container">
                <!-- Talepler JavaScript ile burada listelenecek -->
                <div class="loading">Talepler yükleniyor...</div>
            </div>
        </div>
        
        <!-- Yeni Destek Talebi Formu -->
        <div class="ticket-form">
            <h2>Yeni Destek Talebi Oluştur</h2>
            <form id="support-ticket-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="subject">Konu:</label>
                    <input type="text" id="subject" name="subject" required placeholder="Talebinizin konusunu yazın">
                </div>
                
                <div class="form-group">
                    <label for="description">Açıklama:</label>
                    <textarea id="description" name="description" placeholder="Talebinizin detaylarını yazın"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="attachment">Dosya Ekle (isteğe bağlı):</label>
                    <input type="file" id="attachment" name="attachment">
                </div>
                
                <button type="submit" class="submit-btn">Talebi Gönder</button>
            </form>
        </div>
    </div>

    <script>
        // JavaScript'e PHP değişkenlerini aktarmak için
        const currentUserID = <?php echo json_encode($current_user_id); ?>;
        const currentUserRole = <?php echo json_encode($current_user_role); ?>;
        const siteURL = <?php echo json_encode(SITE_URL); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Form gönderme
            const supportForm = document.getElementById('support-ticket-form');
            supportForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(supportForm);
                
                fetch((siteURL || '') + '/api/create_support_ticket.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Destek talebiniz başarıyla oluşturuldu.');
                        supportForm.reset();
                        loadUserTickets(); // Talepleri yeniden yükle
                    } else {
                        alert('Hata: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Hata:', error);
                    alert('Sistem hatası oluştu, lütfen daha sonra tekrar deneyin.');
                });
            });
            
            // Kullanıcının mevcut taleplerini yükle
            loadUserTickets();
            
            // Tema değiştirme butonu
            const themeToggle = document.getElementById('theme-toggle-button');
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    const body = document.body;
                    const currentTheme = body.classList.contains('dark-theme') ? 'dark' : 'light';
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    
                    body.classList.remove(currentTheme + '-theme');
                    body.classList.add(newTheme + '-theme');
                    
                    // Cookie'yi güncelle
                    document.cookie = `theme=${newTheme}; path=/; max-age=31536000`; // 1 yıl
                });
            }
        });
        
        function loadUserTickets() {
            const ticketsContainer = document.getElementById('tickets-container');
            if (!ticketsContainer) {
                console.error('loadUserTickets: tickets-container elementi bulunamadı!');
                return;
            }
            ticketsContainer.innerHTML = '<div class="loading">Talepler yükleniyor...</div>';
            console.log('loadUserTickets function called, fetch partially uncommented for debugging.');

            // siteURL değişkenini kullanarak tam API yolunu oluştur
            const apiUrl = (typeof siteURL !== 'undefined' ? siteURL : '') + '/api/get_user_tickets.php';
            console.log('Talepler yükleniyor, API URL:', apiUrl);

            fetch(apiUrl) // siteURL ile birleştirilmiş API yolu
            .then(response => {
                if (!response.ok) {
                    console.error('API yanıtı BAŞARISIZ oldu! Durum:', response.status);
                    return response.text().then(text => { 
                        throw new Error('API Hatası: ' + response.status + ' - ' + text);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('API\'den gelen talep verisi (loadUserTickets):', data);
                if (data.success && Array.isArray(data.data)) {
                    if (data.data.length === 0) {
                        ticketsContainer.innerHTML = '<div class="no-tickets">Aktif destek talebi bulunmamaktadır.</div>';
                    } else {
                        ticketsContainer.innerHTML = '';
                        data.data.forEach(ticket => {
                            const ticketCard = createTicketCard(ticket);
                            if (ticketCard) {
                                ticketsContainer.appendChild(ticketCard);
                            }
                        });
                    }
                } else {
                    let errorMessage = 'Talepleri yüklerken bir hata oluştu (API success:false veya veri formatı yanlış).';
                    if (data && typeof data.message !== 'undefined' && data.message !== null) { // data.message varlığını ve null olmadığını kontrol et
                        errorMessage += ' Mesaj: ' + data.message;
                    }
                    ticketsContainer.innerHTML = `<div class="no-tickets">${escapeHTML(errorMessage)}</div>`; // escapeHTML eklendi
                    console.warn('API\'den gelen veri formatı hatalı veya success:false:', data);
                }
            })
            .catch(error => {
                console.error('Talepleri yüklerken FETCH hatası:', error);
                ticketsContainer.innerHTML = '<div class="no-tickets">Bağlantı hatası veya API yanıtı işlenemedi. Konsolu kontrol edin.</div>';
            });
        }
        
        // escapeHTML fonksiyonu (eğer global script.js'de yoksa veya emin olmak için)
        function escapeHTML(str) {
            if (str === null || typeof str === 'undefined') return '';
            return str.toString().replace(/[&<>"\']/g, match => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[match]));
        }

        function createTicketCard(ticket) {
            console.log('Kart oluşturuluyor (createTicketCard), ticket verisi:', ticket);
            if (!ticket || typeof ticket.id === 'undefined') { // Temel bir ticket verisi kontrolü
                console.error('createTicketCard: Geçersiz veya eksik ticket verisi!', ticket);
                return null; // Hatalı veriyle kart oluşturma
            }

            const card = document.createElement('div');
            card.className = 'ticket-card';
            card.addEventListener('click', function() {
                window.location.href = 'ticket_detail.php?id=' + ticket.id;
            });
            card.style.cursor = 'pointer';

            const header = document.createElement('div');
            header.className = 'ticket-header';
            
            const title = document.createElement('div');
            title.className = 'ticket-title';
            title.textContent = ticket.subject;
            
            const status = document.createElement('div');
            status.className = `ticket-status status-${ticket.status}`;
            status.textContent = getStatusText(ticket.status);
            
            header.appendChild(title);
            header.appendChild(status);
            
            const date = document.createElement('div');
            date.className = 'ticket-date';
            date.textContent = 'Oluşturulma: ' + formatDate(ticket.created_at);

            // Talebi oluşturan kullanıcıyı ekleyelim
            const createdBy = document.createElement('div');
            createdBy.className = 'ticket-created-by'; // Yeni bir CSS sınıfı ekleyebiliriz
            createdBy.style.fontSize = '12px'; // Veya doğrudan stil verebiliriz
            createdBy.style.color = '#555';    // Veya doğrudan stil verebiliriz
            // Eğer karanlık tema kullanılıyorsa, metin rengini ayarlayalım
            if (document.body.classList.contains('dark-theme')) {
                createdBy.style.color = '#bbb';
            }
            createdBy.textContent = 'Oluşturan: ' + (ticket.student_username || 'Bilinmiyor');
            
            const description = document.createElement('div');
            description.className = 'ticket-description';
            description.textContent = (ticket.description && ticket.description.length > 100) ? 
                                      (ticket.description.substring(0, 100) + '...') : 
                                      (ticket.description || 'Açıklama bulunmuyor.');
            
            card.appendChild(header);
            card.appendChild(date);
            card.appendChild(createdBy); // Kullanıcı bilgisini tarihten sonra ekleyelim
            card.appendChild(description);
            
            return card;
        }
        
        function getStatusText(status) {
            const statusTexts = {
                'open': 'Açık',
                'pending': 'Beklemede',
                'in_progress': 'İşleniyor',
                'resolved': 'Çözüldü',
                'closed': 'Kapatıldı'
            };
            
            return statusTexts[status] || status;
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('tr-TR');
        }
    </script>
</body>
</html> 