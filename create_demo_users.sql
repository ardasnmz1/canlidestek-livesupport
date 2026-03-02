-- Demo kullanıcılar oluşturma
USE `live_support_chat`;

-- Parolalar 'password123' olarak ayarlanmıştır (bcrypt hash)
INSERT INTO `users` (`username`, `email`, `password_hash`, `role_id`, `is_online`) VALUES
('admin', 'admin@example.com', '$2y$10$JwPCT8aPsw9hbUFJX0FG5.owrJMUu80aA4RRVtPRfLFz9QbZy6uLe', 3, 1),
('teacher1', 'teacher1@example.com', '$2y$10$JwPCT8aPsw9hbUFJX0FG5.owrJMUu80aA4RRVtPRfLFz9QbZy6uLe', 2, 1),
('student1', 'student1@example.com', '$2y$10$JwPCT8aPsw9hbUFJX0FG5.owrJMUu80aA4RRVtPRfLFz9QbZy6uLe', 1, 1),
('student2', 'student2@example.com', '$2y$10$JwPCT8aPsw9hbUFJX0FG5.owrJMUu80aA4RRVtPRfLFz9QbZy6uLe', 1, 0); 