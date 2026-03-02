<?php
require_once 'db_connect.php';

try {
    // SQL dosyasını oku
    $sql = file_get_contents(__DIR__ . '/kb_tables.sql');
    
    // SQL komutlarını ayır
    $commands = array_filter(
        array_map(
            'trim',
            explode(';', $sql)
        ),
        'strlen'
    );
    
    // Her bir komutu ayrı ayrı çalıştır
    foreach ($commands as $command) {
        try {
            $pdo->exec($command);
        } catch (PDOException $e) {
            // Eğer tablo zaten varsa veya benzer hatalar olursa devam et
            if ($e->getCode() != '42S01') { // 42S01: Table already exists
                throw $e;
            }
        }
    }
    
    echo "Bilgi bankası tabloları başarıyla oluşturuldu!";
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
} 