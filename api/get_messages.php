<?php
ob_start(); // Çıktı tamponlamayı başlat
ini_set('display_errors', 0); // Hataları gösterme
error_reporting(E_ALL); // Tüm hataları rapor et

session_start(); // Oturum başlat
require_once '../includes/db_connect.php';
require_once '../includes/functions.php'; // generateChatId için

// Yardımcı fonksiyonu içe aktar
require_once 'fix_json.php';

if (!isLoggedIn()) {
    sendJsonResponse(['success' => false, 'message' => 'Yetkisiz erişim. Mesajlar yüklenemedi.']);
}

// Eğer role_name yoksa dene
if (isset($_SESSION['user_id']) && !isset($_SESSION['role_name'])) {
    $currentUserRole = getRoleNameByUserId($pdo, (int)$_SESSION['user_id']);
    if ($currentUserRole) {
        $_SESSION['role_name'] = $currentUserRole;
    }
}

$currentUserId = $_SESSION['user_id'];
$userId1 = isset($_GET['user_id1']) ? (int)$_GET['user_id1'] : null;
$userId2 = isset($_GET['user_id2']) ? (int)$_GET['user_id2'] : null;
$lastMessageId = isset($_GET['last_message_id']) ? (int)$_GET['last_message_id'] : 0;
$ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : null; // Destek talebi için opsiyonel

if (empty($userId1) || empty($userId2)) {
    sendJsonResponse(['success' => false, 'message' => 'Kullanıcı ID\'leri eksik.']);
}

// Güvenlik kontrolü: İstek yapan kullanıcı, sohbetin taraflarından biri olmalı
if ($userId1 !== $currentUserId && $userId2 !== $currentUserId) {
    sendJsonResponse(['success' => false, 'message' => 'Bu sohbeti görüntüleme yetkiniz yok.']);
}

$chatId = generateChatId($userId1, $userId2);

try {
    // Chat var mı kontrol et, yoksa oluştur (bu aslında send_message.php'de daha mantıklı olabilir,
    // ama get_messages ilk çağrıldığında chat_id'yi döndürmek için burada da bir mantık olabilir)
    // Şimdilik, mesajları çekerken chat varlığını kontrol etmeyeceğiz, direkt mesajları sorgulayacağız.

    $sql = "SELECT m.id, m.chat_id, m.sender_id, m.receiver_id, m.message_text, m.file_path, m.created_at, m.is_read
            FROM messages m
            WHERE m.chat_id = :chat_id AND m.id > :last_message_id
            ORDER BY m.created_at ASC";

    // Eğer ticketId verilmişse ve bu bir destek talebi ise, mesajları o ticket_id'ye göre filtreleyebiliriz.
    // Ancak mevcut veritabanı şemasında messages tablosunda ticket_id kolonu yok.
    // Bu nedenle, şimdilik ticketId parametresi doğrudan SQL sorgusunda kullanılmıyor.
    // Eğer destek talebi mesajları ayrı bir mantıkla saklanıyorsa, o zaman burası güncellenmeli.
    // Şu anki `generateChatId` fonksiyonu zaten kullanıcı ID'lerine dayalı olduğu için,
    // destek talebi sohbetleri de normal kullanıcılar arası sohbet gibi ele alınıyor.

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':chat_id', $chatId, PDO::PARAM_STR);
    $stmt->bindParam(':last_message_id', $lastMessageId, PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Okunmamış mesajları okundu olarak işaretle (sadece alıcı tarafından)
    if (!empty($messages)) {
        $lastFetchedMessage = end($messages);
        if ($lastFetchedMessage['receiver_id'] == $currentUserId && !$lastFetchedMessage['is_read']) {
            $updateSql = "UPDATE messages SET is_read = 1 WHERE chat_id = :chat_id AND receiver_id = :receiver_id AND is_read = 0";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->bindParam(':chat_id', $chatId, PDO::PARAM_STR);
            $updateStmt->bindParam(':receiver_id', $currentUserId, PDO::PARAM_INT);
            $updateStmt->execute();
        }
    }

    sendJsonResponse(['success' => true, 'data' => $messages, 'chat_id' => $chatId]);

} catch (PDOException $e) {
    error_log("Get Messages Error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Mesajlar yüklenirken bir veritabanı hatası oluştu: ' . $e->getMessage()]);
} 