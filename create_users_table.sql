-- Roles ve users tablosunu oluşturma
USE `live_support_chat`;

-- Roles tablosu
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'Rol adı (student, teacher, admin, visitor)',
  `description` text DEFAULT NULL COMMENT 'Rol açıklaması',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rol verilerini ekle
INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'student', 'Öğrenci kullanıcı rolü'),
(2, 'teacher', 'Öğretmen kullanıcı rolü'),
(3, 'admin', 'Yönetici kullanıcı rolü'),
(4, 'visitor', 'Ziyaretçi kullanıcı rolü');

-- Users tablosu
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_online` tinyint(1) DEFAULT 0 COMMENT '0: offline, 1: online',
  `last_seen` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign key kısıtlaması
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE; 