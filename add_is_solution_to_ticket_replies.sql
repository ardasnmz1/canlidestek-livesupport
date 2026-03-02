-- ticket_replies tablosuna is_solution sütunu ekle
ALTER TABLE `ticket_replies` 
ADD COLUMN `is_solution` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Çözüm olarak işaretlenmiş mi'; 