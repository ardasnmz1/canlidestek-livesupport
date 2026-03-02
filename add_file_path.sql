-- file_path sütunu ekle
ALTER TABLE `ticket_replies` 
ADD COLUMN `file_path` varchar(255) DEFAULT NULL COMMENT 'Ekli dosya yolu' 
AFTER `reply_text`; 