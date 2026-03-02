-- support_tickets tablosuna file_path sütunu ekle
ALTER TABLE `support_tickets` 
ADD COLUMN `file_path` varchar(255) DEFAULT NULL COMMENT 'Destek talebine eklenen dosya yolu'; 