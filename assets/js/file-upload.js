document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('file-input');
    const fileNameDisplay = document.getElementById('file-name-display');
    const dropZone = document.getElementById('message-input-area');
    const maxFileSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'application/x-rar-compressed'
    ];

    // Dosya seçildiğinde
    fileInput.addEventListener('change', handleFileSelect);

    // Sürükle-bırak olayları
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.add('drag-over');
    });

    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('drag-over');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('drag-over');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFileSelect({ target: fileInput });
        }
    });

    function handleFileSelect(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Dosya boyutu kontrolü
        if (file.size > maxFileSize) {
            alert('Dosya boyutu 10MB\'dan büyük olamaz.');
            e.target.value = '';
            fileNameDisplay.textContent = '';
            return;
        }

        // Dosya türü kontrolü
        if (!allowedTypes.includes(file.type)) {
            alert('Bu dosya türü desteklenmiyor. Lütfen izin verilen dosya türlerinden birini seçin.');
            e.target.value = '';
            fileNameDisplay.textContent = '';
            return;
        }

        // Dosya adını göster
        fileNameDisplay.textContent = file.name;
        
        // Dosya türüne göre simge ekle
        const fileIcon = getFileIcon(file.type);
        fileNameDisplay.innerHTML = `${fileIcon} ${file.name}`;
    }

    function getFileIcon(fileType) {
        const icons = {
            'image': '🖼️',
            'application/pdf': '📄',
            'application/msword': '📝',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': '📝',
            'application/vnd.ms-excel': '📊',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': '📊',
            'application/zip': '🗜️',
            'application/x-rar-compressed': '🗜️'
        };

        if (fileType.startsWith('image/')) return icons.image;
        return icons[fileType] || '📎';
    }
}); 