-- ticket_replies tablosuna updated_at sütunu ekle
ALTER TABLE `ticket_replies` 
ADD COLUMN `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Son güncellenme tarihi'; 