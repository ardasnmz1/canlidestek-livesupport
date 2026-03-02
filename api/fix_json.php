<?php
/**
 * Bu dosya, API'lerin temiz JSON yanıtları döndürmesini sağlamak için yardımcı
 * fonksiyon içerir.
 */

// Fonksiyonu tekrar tanımlamamak için kontrol ediyoruz
if (!function_exists('sendJsonResponse')) {
    /**
     * JSON yanıtı temiz bir şekilde gönderir ve çıkar
     *
     * @param array $data JSON olarak kodlanacak veri
     * @return void
     */
    function sendJsonResponse($data) {
        // Çıktı tamponunu tamamen temizle
        ob_clean();
        
        // HTTP başlıklarını temizle ve JSON başlık ekle
        header_remove();
        header('Content-Type: application/json; charset=utf-8');
        
        // UTF-8 BOM'u önlemek için boş karakter veya beyaz alan kontrolü
        echo json_encode($data);
        exit;
    }
} 