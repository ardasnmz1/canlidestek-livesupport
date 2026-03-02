-- messages tablosuna updated_at sütunu ekle (eğer yoksa)
ALTER TABLE `messages` 
ADD COLUMN IF NOT EXISTS `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(); 