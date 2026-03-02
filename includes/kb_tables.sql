-- Kategoriler tablosu
CREATE TABLE IF NOT EXISTS kb_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Makaleler tablosu
CREATE TABLE IF NOT EXISTS kb_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    views INT DEFAULT 0,
    helpful_votes INT DEFAULT 0,
    not_helpful_votes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES kb_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- FULLTEXT indeksini ayrı bir ALTER TABLE komutu ile ekliyoruz
ALTER TABLE kb_articles ADD FULLTEXT INDEX article_search_idx (title, content);

-- Makale etiketleri tablosu
CREATE TABLE IF NOT EXISTS kb_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Makale-Etiket ilişki tablosu
CREATE TABLE IF NOT EXISTS kb_article_tags (
    article_id INT,
    tag_id INT,
    PRIMARY KEY (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES kb_articles(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES kb_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Makale geri bildirimleri tablosu
CREATE TABLE IF NOT EXISTS kb_article_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT,
    user_id INT,
    is_helpful BOOLEAN,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES kb_articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Örnek kategori verileri
INSERT INTO kb_categories (name, description, icon) VALUES
('Genel Bilgiler', 'Sistem hakkında genel bilgiler ve kullanım kılavuzu', 'fas fa-info-circle'),
('Hesap İşlemleri', 'Hesap yönetimi ve güvenlik ayarları', 'fas fa-user-cog'),
('Destek Talepleri', 'Destek talebi oluşturma ve yönetimi', 'fas fa-ticket-alt'),
('Dosya Paylaşımı', 'Dosya yükleme ve paylaşım kuralları', 'fas fa-file-upload'),
('Güvenlik', 'Güvenlik politikaları ve öneriler', 'fas fa-shield-alt');

-- Örnek makale verileri
INSERT INTO kb_articles (category_id, title, content) VALUES
(1, 'Sisteme Hoş Geldiniz', 'Bu makale, sistemimizin temel özelliklerini ve nasıl kullanılacağını anlatır...'),
(2, 'Hesap Güvenliği Nasıl Sağlanır?', 'Hesabınızı güvende tutmak için yapmanız gerekenler...'),
(3, 'Destek Talebi Nasıl Oluşturulur?', 'Destek talebi oluşturma adımları ve dikkat edilmesi gerekenler...'),
(4, 'Dosya Paylaşım Kuralları', 'Dosya paylaşımında uyulması gereken kurallar ve sınırlamalar...'); 