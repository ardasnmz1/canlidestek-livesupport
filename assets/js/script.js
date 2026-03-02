document.addEventListener('DOMContentLoaded', () => {
    // DOM Öğeleri
    const themeToggleButton = document.getElementById('theme-toggle-button');
    const chatContainer = document.getElementById('chat-container');
    const userListElement = document.getElementById('user-list');
    const supportTicketsContainer = document.getElementById('support-tickets'); // Öğretmen/Admin için ana div
    const supportTicketsListElement = document.getElementById('support-requests-list'); // Taleplerin listeleneceği UL
    const chatPartnerDisplayElement = document.getElementById('chat-partner-display');
    const messagesContainer = document.getElementById('messages-container');
    const messageInput = document.getElementById('message-input');
    const sendMessageButton = document.getElementById('send-message-button');
    const fileInput = document.getElementById('file-input');
    const fileNameDisplay = document.getElementById('file-name-display');
    const logoutButton = document.getElementById('logout-button');
    const searchUserInput = document.getElementById('search-user-input'); // Kullanıcı arama girişi
    const menuToggleButton = document.getElementById('menu-toggle-button'); // Hamburger menü butonu

    const chatWindow = document.getElementById('chat-window');
    const noChatSelected = document.getElementById('no-chat-selected');
    const sidebar = document.getElementById('sidebar'); // sidebar değişkeni yukarıda tanımlanmalı

    // PHP'den gelen değişkenler (index.php'de tanımlanmış olmalı)
    // const currentUserID = <?php echo json_encode($current_user_id); ?>;
    // const currentUserRole = <?php echo json_encode($current_user_role); ?>;
    // const siteURL = <?php echo json_encode(SITE_URL); ?>;
    // Yukarıdakiler index.php'de <script> tagları içinde global JS değişkenleri olarak tanımlandı.

    let selectedUserId = null;
    let selectedUserName = null;
    let activeChatId = null;
    let messagePollingInterval = null;
    let userListPollingInterval = null;

    console.log('[script.js] currentUserID:', typeof currentUserID !== 'undefined' ? currentUserID : 'Tanımsız');
    console.log('[script.js] currentUserRole:', typeof currentUserRole !== 'undefined' ? currentUserRole : 'Tanımsız');
    console.log('[script.js] siteURL:', typeof siteURL !== 'undefined' ? siteURL : 'Tanımsız');

    function showChatWindow() {
        if (chatWindow) chatWindow.style.display = 'flex'; // veya 'block', CSS'e bağlı
        if (noChatSelected) noChatSelected.style.display = 'none';
    }

    function showNoChatSelected() {
        if (chatWindow) chatWindow.style.display = 'none';
        if (noChatSelected) noChatSelected.style.display = 'flex'; // veya 'block'
    }

    // Başlangıçta sohbet seçili değilse göster
    showNoChatSelected();

    // Mobil Menü Toggle
    if (menuToggleButton && sidebar) {
        menuToggleButton.addEventListener('click', (event) => {
            sidebar.classList.toggle('open');
            event.stopPropagation();
        });
    }

    // Sidebar dışına tıklayınca kapat (mobil için)
    document.addEventListener('click', (event) => {
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('open')) {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnMenuButton = menuToggleButton ? menuToggleButton.contains(event.target) : false;

            if (!isClickInsideSidebar && !isClickOnMenuButton) {
                sidebar.classList.remove('open');
            }
        }
    });

    // Tema Değiştirme (Orijinal Buton)
    if (themeToggleButton) {
        themeToggleButton.addEventListener('click', () => {
            document.body.classList.toggle('dark-theme');
            localStorage.setItem('theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
        });
    }

    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-theme');
    }

    // AJAX İstekleri için Yardımcı Fonksiyon
    async function ajaxRequest(url, method = 'GET', data = null) {
        const options = {
            method: method,
            headers: {
                // FormData için Content-Type başlığı tarayıcı tarafından otomatik ayarlanır
            },
        };
        if (method !== 'GET' && data && !(data instanceof FormData)) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        } else if (data && data instanceof FormData && method !== 'GET') {
            options.body = data;
        } else if (data && method === 'GET') {
            url += '?' + new URLSearchParams(data).toString();
        }

        try {
            const response = await fetch(siteURL + '/' + url, options);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: 'Bilinmeyen sunucu hatası' }));
                console.error('AJAX Hatası:', response.status, errorData);
                alert(`Hata: ${errorData.message || response.statusText}`);
                return null;
            }
            return await response.json();
        } catch (error) {
            console.error('Fetch Hatası:', error);
            alert('Ağ hatası veya sunucuya ulaşılamıyor.');
            return null;
        }
    }

    // Kullanıcı Listesini Yükle
    async function loadUserList() {
        if (!userListElement) return;
        const usersData = await ajaxRequest('api/get_users.php', 'GET');
        userListElement.innerHTML = ''; // Önceki listeyi temizle

        if (usersData && usersData.success && usersData.data) {
            usersData.data.forEach(user => {
                if (user.id.toString() === currentUserID.toString()) return;

                const userDiv = document.createElement('li'); // li olarak değiştirildi
                userDiv.classList.add('user-item');
                userDiv.dataset.userId = user.id;
                userDiv.dataset.userName = user.username;
                userDiv.innerHTML = `
                    <span>${escapeHTML(user.username)} (${escapeHTML(user.role_name)})</span>
                    <span class="status-circle ${user.is_online ? 'online' : 'offline'}"></span>
                `;
                userDiv.addEventListener('click', () => {
                    document.querySelectorAll('#user-list .user-item').forEach(item => item.classList.remove('selected'));
                    userDiv.classList.add('selected');
                    
                    selectedUserId = user.id;
                    selectedUserName = user.username;
                    if (chatPartnerDisplayElement) chatPartnerDisplayElement.textContent = escapeHTML(user.username);
                    messagesContainer.innerHTML = '';
                    activeChatId = null;
                    stopMessagePolling();

                    let canChat = false;
                    if (currentUserRole === 'visitor') {
                        if (user.role_name === 'teacher' || user.role_name === 'admin') canChat = true;
                    } else if (currentUserRole === 'student') {
                        if (user.role_name === 'teacher' || user.role_name === 'admin') canChat = true;
                    } else { // teacher veya admin herkesle konuşabilir
                        canChat = true;
                    }

                    if (canChat) {
                        showChatWindow();
                        loadMessages(currentUserID, selectedUserId);
                        // Ziyaretçi veya öğrenci bir öğretmen/admin ile konuşmaya başlarsa destek talebi oluşturulabilir.
                        // Bu mantık burada veya loadMessages içinde genişletilebilir.
                        if ((currentUserRole === 'visitor' || currentUserRole === 'student') && 
                            (user.role_name === 'teacher' || user.role_name === 'admin')) {
                            // createSupportTicketIfNeeded(currentUserID, selectedUserId, user.username);
                        }
                    } else {
                        alert('Bu kullanıcıyla sohbet başlatma yetkiniz yok.');
                        if (chatPartnerDisplayElement) chatPartnerDisplayElement.textContent = '';
                        selectedUserId = null;
                        selectedUserName = null;
                        userDiv.classList.remove('selected');
                        showNoChatSelected();
                    }
                });
                userListElement.appendChild(userDiv);
            });

            // Kullanıcı arama fonksiyonunu çağır
            if (searchUserInput) {
                filterUserList();
            }
        } else if (usersData && !usersData.success) {
            userListElement.innerHTML = '<li class="error-item">Kullanıcı listesi yüklenemedi.</li>';
            console.warn('Kullanıcı listesi yüklenemedi:', usersData.message);
        }
    }

    // Kullanıcı Arama İşlevselliği
    function filterUserList() {
        if (!searchUserInput) return;

        searchUserInput.addEventListener('input', () => {
            const searchTerm = searchUserInput.value.toLowerCase().trim();
            const userItems = userListElement.querySelectorAll('.user-item');
            
            userItems.forEach(item => {
                const userName = item.dataset.userName.toLowerCase();
                if (userName.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Eğer hiç sonuç yoksa bilgi mesajı göster
            let visibleCount = 0;
            userItems.forEach(item => {
                if (item.style.display !== 'none') visibleCount++;
            });
            
            // Önceki "sonuç bulunamadı" mesajını kaldır
            const existingNoResult = userListElement.querySelector('.no-result-item');
            if (existingNoResult) {
                userListElement.removeChild(existingNoResult);
            }
            
            // Görünür öğe yoksa ve arama terimi varsa "sonuç bulunamadı" mesajı ekle
            if (visibleCount === 0 && searchTerm.length > 0) {
                const noResultItem = document.createElement('li');
                noResultItem.classList.add('no-result-item');
                noResultItem.textContent = 'Sonuç bulunamadı';
                userListElement.appendChild(noResultItem);
            }
        });
    }

    // Mesajları Yükle
    async function loadMessages(userId1, userId2, isSupportTicket = false, ticketId = null) {
        if (!userId1 || !userId2) return;
        stopMessagePolling();

        const params = { user_id1: userId1, user_id2: userId2, last_message_id: getLastMessageId() };
        if (isSupportTicket && ticketId) params.ticket_id = ticketId;

        const response = await ajaxRequest('api/get_messages.php', 'GET', params);

        if (response && response.success) {
            if (response.chat_id) activeChatId = response.chat_id;
            if (response.data && response.data.length > 0) {
                response.data.forEach(msg => displayMessage(msg));
                scrollToBottom();
            }
             // Eğer ilk yükleme ise (lastMessageId 0 ise) ve hiç mesaj yoksa bile polling'i başlat
            if (getLastMessageId() === 0 && (!response.data || response.data.length === 0)) {
                // Hiç mesaj yoksa bile, activeChatId ayarlandıysa ve polling yoksa başlat.
            } 
            startMessagePolling(userId1, userId2, isSupportTicket, ticketId);

        } else if (response && !response.success) {
            console.warn('Mesajlar yüklenemedi:', response.message);
            if(response.chat_id) activeChatId = response.chat_id; // Hata durumunda da chat_id gelebilir
            startMessagePolling(userId1, userId2, isSupportTicket, ticketId); // Hata olsa da polling'i dene
        } else {
            console.error('Mesaj yükleme sırasında sunucudan geçerli bir yanıt alınamadı.');
        }
    }
    
    function startMessagePolling(userId1, userId2, isSupportTicket = false, ticketId = null) {
        stopMessagePolling(); // Önceki interval'ı temizle
        messagePollingInterval = setInterval(async () => {
            const params = { user_id1: userId1, user_id2: userId2, last_message_id: getLastMessageId() };
            if (isSupportTicket && ticketId) params.ticket_id = ticketId;
            
            const newMessagesResponse = await ajaxRequest('api/get_messages.php', 'GET', params);
            if (newMessagesResponse && newMessagesResponse.success && newMessagesResponse.data && newMessagesResponse.data.length > 0) {
                newMessagesResponse.data.forEach(newMsg => displayMessage(newMsg));
                scrollToBottom();
            }
        }, 3000);
    }

    function getLastMessageId() {
        const lastMessageElement = messagesContainer.lastElementChild;
        return lastMessageElement ? parseInt(lastMessageElement.dataset.messageId) : 0;
    }

    function stopMessagePolling() {
        if (messagePollingInterval) {
            clearInterval(messagePollingInterval);
            messagePollingInterval = null;
        }
    }

    // Mesaj Gönderme
    async function sendMessage() {
        const text = messageInput.value.trim();
        const file = fileInput.files[0];

        if (!text && !file) return;
        if (!selectedUserId) {
            alert('Lütfen sohbet etmek için bir kullanıcı seçin.');
            return;
        }

        const formData = new FormData();
        formData.append('sender_id', currentUserID);
        formData.append('receiver_id', selectedUserId);
        formData.append('message_text', text);
        if (activeChatId) formData.append('chat_id', activeChatId);
        if (file) formData.append('attachment', file);

        const result = await ajaxRequest('api/send_message.php', 'POST', formData);

        if (result && result.success && result.data) {
            displayMessage(result.data); 
            messageInput.value = '';
            fileInput.value = ''; 
            if(fileNameDisplay) fileNameDisplay.textContent = '';
            scrollToBottom();
            if (!activeChatId && result.chat_id) {
                activeChatId = result.chat_id;
                // İlk mesaj sonrası polling'i yeniden başlatmaya gerek yok, zaten var olan devam eder veya loadMessages tetikler
            }
        } else {
            alert('Mesaj gönderilemedi: ' + (result ? result.message : 'Bilinmeyen bir hata oluştu.'));
        }
    }

    // Mesajı Görüntüleme
    function displayMessage(msg) {
        const div = document.createElement('div');
        div.classList.add('message'); // CSS .message kullanıyor
        div.dataset.messageId = msg.id;

        const senderClass = msg.sender_id.toString() === currentUserID.toString() ? 'sent' : 'received';
        div.classList.add(senderClass);

        // Mesaj düzenleme/silme menüsü için kontroller
        const canEdit = msg.sender_id.toString() === currentUserID.toString();
        const canDelete = canEdit || currentUserRole === 'admin' || currentUserRole === 'teacher';
        
        let messageActionsHTML = '';
        if (canEdit || canDelete) {
            messageActionsHTML = `
                <div class="message-actions">
                    <div class="message-actions-toggle">⋮</div>
                    <div class="message-actions-menu">
                        ${canEdit ? '<button class="edit-message-btn">Düzenle</button>' : ''}
                        ${canDelete ? '<button class="delete-message-btn">Sil</button>' : ''}
                    </div>
                </div>
            `;
        }

        let fileHTML = '';
        if (msg.file_path) {
            const fileName = msg.file_path.split('/').pop();
            const fileExtension = fileName.split('.').pop().toLowerCase();
            const fullFilePath = siteURL + '/' + msg.file_path; // Dosya yolu siteURL ile birleştirilmeli
            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
                fileHTML = `<div class="file-attachment"><img src="${escapeHTML(fullFilePath)}" alt="Ek" style="max-width: 200px; max-height: 200px; border-radius: 5px; margin-top: 5px;"></div>`;
            } else {
                fileHTML = `<div class="file-attachment"><a href="${escapeHTML(fullFilePath)}" target="_blank">Ek: ${escapeHTML(fileName)}</a></div>`;
            }
        }

        // Mesaj tarihini formatlama
        const messageDate = new Date(msg.created_at).toLocaleString('tr-TR', { hour: '2-digit', minute: '2-digit'});
        
        // Düzenlendi bilgisi
        const editedInfo = msg.updated_at && msg.created_at && msg.updated_at !== msg.created_at ? '<span class="edited-info">(düzenlendi)</span>' : '';
        
        div.innerHTML = `
            ${senderClass === 'received' ? `<span class="sender">${escapeHTML(selectedUserName || 'Kullanıcı')}</span>` : ''}
            <div class="message-content">
                <div class="text">${escapeHTML(msg.message_text || '')}</div>
                ${fileHTML}
                <div class="message-footer">
                    ${editedInfo}
                    <span class="timestamp">${messageDate}</span>
                </div>
            </div>
            ${messageActionsHTML}
        `;
        
        // Mesaj düzenleme ve silme işlevselliği
        if (canEdit || canDelete) {
            // Menü açma/kapama
            const toggleBtn = div.querySelector('.message-actions-toggle');
            const menu = div.querySelector('.message-actions-menu');
            
            if (toggleBtn && menu) {
                toggleBtn.addEventListener('click', () => {
                    menu.classList.toggle('show');
                });
                
                // Menü dışına tıklandığında menüyü kapat
                document.addEventListener('click', (e) => {
                    if (!div.contains(e.target) && menu.classList.contains('show')) {
                        menu.classList.remove('show');
                    }
                });
            }
            
            // Silme işlevi
            if (canDelete) {
                const deleteBtn = div.querySelector('.delete-message-btn');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', async () => {
                        await deleteMessage(msg.id);
                    });
                }
            }
            
            // Düzenleme işlevi
            if (canEdit) {
                const editBtn = div.querySelector('.edit-message-btn');
                if (editBtn) {
                    editBtn.addEventListener('click', () => {
                        editMessage(msg.id, msg.message_text || '');
                    });
                }
            }
        }
        
        messagesContainer.appendChild(div);
    }
    
    // Mesaj silme fonksiyonu
    async function deleteMessage(messageId) {
        if (!messageId) return;
        
        const confirmDelete = confirm('Bu mesajı silmek istediğinize emin misiniz?');
        if (!confirmDelete) return;
        
        try {
            const formData = new FormData();
            formData.append('message_id', messageId);
            
            const response = await ajaxRequest('api/delete_message.php', 'POST', formData);
            
            if (response && response.success) {
                // Mesajı DOM'dan kaldır
                const messageElement = messagesContainer.querySelector(`.message[data-message-id="${messageId}"]`);
                if (messageElement) {
                    messageElement.remove();
                }
            } else {
                alert('Mesaj silinemedi: ' + (response ? response.message : 'Bilinmeyen bir hata oluştu.'));
            }
        } catch (error) {
            console.error('Mesaj silme hatası:', error);
            alert('Mesaj silinirken bir hata oluştu.');
        }
    }
    
    // Mesaj düzenleme fonksiyonu
    function editMessage(messageId, currentText) {
        if (!messageId) return;
        
        // Mevcut mesaj elementini bul
        const messageElement = messagesContainer.querySelector(`.message[data-message-id="${messageId}"]`);
        if (!messageElement) return;
        
        // Mesaj içeriğini bul
        const messageTextElement = messageElement.querySelector('.text');
        if (!messageTextElement) return;
        
        // Düzenleme menüsünü gizle
        const messageActionsMenu = messageElement.querySelector('.message-actions-menu');
        if (messageActionsMenu) {
            messageActionsMenu.classList.remove('show');
        }
        
        // Düzenleme modunu hazırla
        const originalText = currentText;
        
        // Mesaj içeriğini düzenleme alanıyla değiştir
        messageTextElement.innerHTML = `
            <div class="edit-message-container">
                <textarea class="edit-message-textarea">${escapeHTML(originalText)}</textarea>
                <div class="edit-message-buttons">
                    <button class="save-edit-btn">Kaydet</button>
                    <button class="cancel-edit-btn">İptal</button>
                </div>
            </div>
        `;
        
        // Düzenleme alanına odaklan
        const textarea = messageTextElement.querySelector('.edit-message-textarea');
        textarea.focus();
        
        // İptal butonu işlevi
        const cancelBtn = messageTextElement.querySelector('.cancel-edit-btn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                // Orijinal içeriğe geri dön
                messageTextElement.innerHTML = escapeHTML(originalText);
            });
        }
        
        // Kaydet butonu işlevi
        const saveBtn = messageTextElement.querySelector('.save-edit-btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', async () => {
                const newText = textarea.value.trim();
                
                if (!newText) {
                    alert('Mesaj içeriği boş olamaz.');
                    return;
                }
                
                if (newText === originalText) {
                    // Değişiklik yoksa, sadece düzenleme modundan çık
                    messageTextElement.innerHTML = escapeHTML(originalText);
                    return;
                }
                
                try {
                    const formData = new FormData();
                    formData.append('message_id', messageId);
                    formData.append('message_text', newText);
                    
                    const response = await ajaxRequest('api/edit_message.php', 'POST', formData);
                    
                    if (response && response.success) {
                        // Mesaj içeriğini güncelle
                        messageTextElement.innerHTML = escapeHTML(newText);
                        
                        // Düzenlendi bilgisini ekle
                        const messageFooter = messageElement.querySelector('.message-footer');
                        if (messageFooter) {
                            let editedInfo = messageFooter.querySelector('.edited-info');
                            if (!editedInfo) {
                                editedInfo = document.createElement('span');
                                editedInfo.classList.add('edited-info');
                                editedInfo.textContent = '(düzenlendi)';
                                messageFooter.insertBefore(editedInfo, messageFooter.firstChild);
                            }
                        }
                    } else {
                        // Hata durumunda orijinal içeriğe geri dön
                        messageTextElement.innerHTML = escapeHTML(originalText);
                        alert('Mesaj düzenlenemedi: ' + (response ? response.message : 'Bilinmeyen bir hata oluştu.'));
                    }
                } catch (error) {
                    console.error('Mesaj düzenleme hatası:', error);
                    messageTextElement.innerHTML = escapeHTML(originalText);
                    alert('Mesaj düzenlenirken bir hata oluştu.');
                }
            });
        }
    }

    // Destek Taleplerini Yükle
    async function loadSupportTickets() {
        // supportTicketsContainer elementini global scope'tan değil, buradan alalım
        const supportTicketsListElement = document.getElementById('support-requests-list');
        const supportTicketsDiv = document.getElementById('support-tickets'); // Ana div

        if (!supportTicketsDiv || (typeof currentUserRole === 'undefined' || (currentUserRole !== 'teacher' && currentUserRole !== 'admin'))) {
            if(supportTicketsDiv) supportTicketsDiv.style.display = 'none'; // Eğer rol uygun değilse bölümü gizle
            console.log('loadSupportTickets: Rol uygun değil veya currentUserRole tanımsız, talepler yüklenmeyecek.');
            return;
        }
        if (!supportTicketsListElement) {
            console.error('loadSupportTickets: support-requests-list elementi bulunamadı!');
            return;
        }
        if(supportTicketsDiv) supportTicketsDiv.style.display = 'block'; // Rol uygunsa bölümü göster

        // console.log('loadSupportTickets çağrıldı. Rol:', currentUserRole);
        const response = await ajaxRequest('api/get_support_tickets.php', 'GET');
        supportTicketsListElement.innerHTML = ''; 

        if (response && response.success && response.data) {
            if (response.data.length === 0) {
                supportTicketsListElement.innerHTML = '<li class="info-item">Aktif destek talebi bulunmamaktadır.</li>';
                return;
            }
            response.data.forEach(ticket => {
                const li = document.createElement('li');
                li.classList.add('ticket-item');
                li.dataset.ticketId = ticket.id;
                li.dataset.studentId = ticket.student_id;
                li.dataset.studentName = ticket.student_username;
                
                const deleteButtonHTML = `<button class="delete-ticket-btn" title="Destek talebini sil">🗑️</button>`;
                
                li.innerHTML = `
                    <div class="ticket-info">
                        <span>${escapeHTML(ticket.student_username)} (${escapeHTML(ticket.subject || 'Genel')})</span>
                        <span class="status-indicator ${escapeHTML(ticket.status ? ticket.status.toLowerCase() : 'unknown')}">${escapeHTML(ticket.status || 'Bilinmiyor')}</span>
                    </div>
                    ${currentUserRole === 'admin' ? deleteButtonHTML : ''} ${/* Sadece admin silebilir */''}
                `;
                
                li.querySelector('.ticket-info').addEventListener('click', () => {
                    document.querySelectorAll('#support-requests-list .ticket-item').forEach(item => item.classList.remove('selected-support'));
                    li.classList.add('selected-support');
                    
                    selectedUserId = ticket.student_id;
                    selectedUserName = ticket.student_username;
                    if (chatPartnerDisplayElement) chatPartnerDisplayElement.textContent = `Destek: ${escapeHTML(ticket.student_username)}`;
                    messagesContainer.innerHTML = '';
                    activeChatId = null;
                    stopMessagePolling();
                    showChatWindow();
                    loadMessages(currentUserID, ticket.student_id, true, ticket.id);
                });
                
                if (currentUserRole === 'admin') {
                    const deleteBtn = li.querySelector('.delete-ticket-btn');
                    if (deleteBtn) {
                        deleteBtn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            deleteTicket(ticket.id, ticket.student_username);
                        });
                    }
                }
                
                supportTicketsListElement.appendChild(li);
            });
        } else if (response && !response.success) {
            supportTicketsListElement.innerHTML = `<li class="error-item">Destek talepleri yüklenemedi: ${response.message}</li>`;
        } else {
            supportTicketsListElement.innerHTML = '<li class="error-item">Destek talepleri yüklenemedi (sunucudan yanıt yok veya format hatalı).</li>';
        }
    }
    
    // Destek Talebini Silme
    async function deleteTicket(ticketId, studentName) {
        if (!ticketId) return;
        
        // Onay al
        const confirmDelete = confirm(`"${studentName}" kullanıcısına ait destek talebini ve tüm mesajlarını silmek istediğinize emin misiniz?`);
        
        if (!confirmDelete) return;
        
        try {
            const formData = new FormData();
            formData.append('ticket_id', ticketId);
            
            const response = await ajaxRequest('api/delete_support_ticket.php', 'POST', formData);
            
            if (response && response.success) {
                // Eğer şu anda silinen talep görüntüleniyorsa, görünümü sıfırla
                const selectedTicket = supportTicketsListElement.querySelector('.selected-support');
                if (selectedTicket && selectedTicket.dataset.ticketId == ticketId) {
                    messagesContainer.innerHTML = '';
                    if (chatPartnerDisplayElement) chatPartnerDisplayElement.textContent = '';
                    showNoChatSelected();
                }
                
                // Destek talepleri listesini yenile
                loadSupportTickets();
                
                alert('Destek talebi başarıyla silindi.');
            } else {
                alert('Destek talebi silinemedi: ' + (response ? response.message : 'Bilinmeyen bir hata oluştu.'));
            }
        } catch (error) {
            console.error('Silme işlemi sırasında hata:', error);
            alert('Silme işlemi sırasında bir hata oluştu.');
        }
    }

    function scrollToBottom() {
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }

    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return str.toString().replace(/[&<>"']/g, match => ({'&': '&amp;','<': '&lt;','>': '&gt;','"': '&quot;',"'": '&#39;'}[match]));
    }

    if (logoutButton) {
        logoutButton.addEventListener('click', async () => {
            // İsteğe bağlı: Kullanıcının online durumunu sunucuda güncelle
            // await ajaxRequest('api/update_user_status.php', 'POST', { user_id: currentUserID, status: 'offline' });
            window.location.href = siteURL + '/logout.php'; // siteURL kullanımı
        });
    }

    if (sendMessageButton && messageInput) {
        sendMessageButton.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }
    
    if(fileInput && fileNameDisplay){
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                fileNameDisplay.textContent = escapeHTML(fileInput.files[0].name);
            } else {
                fileNameDisplay.textContent = '';
            }
        });
    }

    // Başlangıç Yüklemeleri ve Periyodik Güncellemeler
    if (typeof loadUserList === 'function') loadUserList();
    
    // currentUserRole tanımlı ve doğru ise destek taleplerini yükle
    if (typeof currentUserRole !== 'undefined' && (currentUserRole === 'teacher' || currentUserRole === 'admin')) {
        if (typeof loadSupportTickets === 'function') loadSupportTickets();
    } else {
        // Rol uygun değilse ilgili bölümü gizleyebiliriz (index.php için)
        const supportTicketsDivOnIndex = document.getElementById('support-tickets');
        if (supportTicketsDivOnIndex) {
            supportTicketsDivOnIndex.style.display = 'none';
        }
    }

    // Kullanıcı listesini ve (admin/öğretmen için) destek taleplerini periyodik olarak güncelle
    stopUserListPolling(); 
    userListPollingInterval = setInterval(() => {
        if (typeof loadUserList === 'function') loadUserList();
        if (typeof currentUserRole !== 'undefined' && (currentUserRole === 'teacher' || currentUserRole === 'admin')) {
            if (typeof loadSupportTickets === 'function') loadSupportTickets();
        }
    }, 10000);

    function stopUserListPolling(){
        if(userListPollingInterval) clearInterval(userListPollingInterval);
    }

    // Sayfadan ayrılırken veya sekmeyi kapatırken polling'leri temizle (isteğe bağlı)
    window.addEventListener('beforeunload', () => {
        stopMessagePolling();
        stopUserListPolling();
        // Gerekirse burada kullanıcının online durumunu 'offline' olarak işaretlemek için bir AJAX isteği yapılabilir,
        // ancak 'beforeunload' güvenilir olmayabilir (navigator.sendBeacon daha iyi bir seçenek olabilir).
    });
}); 