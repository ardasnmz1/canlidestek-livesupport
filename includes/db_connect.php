<?php
require_once __DIR__ . '/../config/config.php';

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Sonuçları associatif array olarak al
    PDO::ATTR_EMULATE_PREPARES   => false, // Güvenlik için önemli
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Geliştirme ortamında detaylı hata, canlıda genel bir mesaj
    // error_log("Veritabanı bağlantı hatası: " . $e->getMessage());
    // die("Veritabanına bağlanırken bir sorun oluştu. Lütfen daha sonra tekrar deneyin.");
    throw new PDOException($e->getMessage(), (int)$e->getCode()); // Geliştirme için hatayı göster
}
?> 