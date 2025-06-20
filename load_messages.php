<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Keine Benutzer-ID angegeben']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$other_user_id = $_GET['user_id'];
$last_time = isset($_GET['last_time']) ? intval($_GET['last_time']) : 0;

try {
    // Hole Nachrichten
    $stmt = $pdo->prepare("
        SELECT m.*, u.username 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE (
            (m.sender_id = ? AND m.receiver_id = ?) 
            OR (m.sender_id = ? AND m.receiver_id = ?)
        )
        AND UNIX_TIMESTAMP(m.created_at) * 1000 > ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([
        $current_user_id, 
        $other_user_id, 
        $other_user_id, 
        $current_user_id,
        $last_time
    ]);
    $messages = $stmt->fetchAll();

    // Formatiere Nachrichten
    $formatted_messages = array_map(function($message) {
        return [
            'message' => $message['message'],
            'time' => $message['created_at'],
            'sender_id' => $message['sender_id']
        ];
    }, $messages);

    echo json_encode([
        'success' => true,
        'messages' => $formatted_messages
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Datenbankfehler'
    ]);
}
