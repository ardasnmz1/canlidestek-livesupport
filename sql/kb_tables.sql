-- Bilgi Bankası Kategorileri
CREATE TABLE IF NOT EXISTS kb_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bilgi Bankası Makaleleri
CREATE TABLE IF NOT EXISTS kb_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    views INT DEFAULT 0,
    helpful_votes INT DEFAULT 0,
    not_helpful_votes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES kb_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Makale Etiketleri
CREATE TABLE IF NOT EXISTS kb_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Makale-Etiket İlişkileri
CREATE TABLE IF NOT EXISTS kb_article_tags (
    article_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES kb_articles(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES kb_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Makale Geri Bildirimleri
CREATE TABLE IF NOT EXISTS kb_article_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    user_id INT NOT NULL,
    is_helpful BOOLEAN NOT NULL,
    feedback_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES kb_articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_feedback (article_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Örnek Kategoriler
INSERT INTO kb_categories (name, description) VALUES
('Genel Bilgiler', 'Sistem hakkında genel bilgiler ve sık sorulan sorular'),
('Hesap İşlemleri', 'Hesap oluşturma, şifre değiştirme ve profil yönetimi'),
('Destek Talepleri', 'Destek talebi oluşturma ve yönetimi hakkında bilgiler'),
('Dosya Paylaşımı', 'Dosya yükleme ve paylaşım kuralları'),
('Güvenlik', 'Hesap güvenliği ve gizlilik ayarları');

-- Örnek Makaleler
INSERT INTO kb_articles (category_id, title, content) VALUES
(1, 'Canlı Destek Sistemi Nedir?', 'Canlı destek sistemi, öğrencilerin öğretmenlerle ve yöneticilerle doğrudan iletişim kurabildiği bir platformdur...'),
(2, 'Şifremi Nasıl Değiştirebilirim?', 'Şifrenizi değiştirmek için aşağıdaki adımları takip edebilirsiniz:\n1. Profilim sayfasına gidin\n2. Şifre Değiştir butonuna tıklayın...'),
(3, 'Destek Talebi Nasıl Oluşturulur?', 'Yeni bir destek talebi oluşturmak için:\n1. Ana sayfada Destek Talepleri butonuna tıklayın\n2. Yeni Talep Oluştur butonunu seçin...'),
(4, 'İzin Verilen Dosya Türleri', 'Sistemde paylaşabileceğiniz dosya türleri:\n- Resim dosyaları (jpg, png, gif)\n- Doküman dosyaları (pdf, doc, docx)\n- Sıkıştırılmış dosyalar (zip, rar)...'); 