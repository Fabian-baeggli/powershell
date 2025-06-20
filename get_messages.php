<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

$other_user_id = $_GET['user_id'] ?? null;

if (!$other_user_id) {
    echo json_encode(['success' => false, 'message' => 'Fehlende Benutzer-ID']);
    exit;
}

try {
    // Prüfe, ob es ein Match gibt
    $stmt = $pdo->prepare("
        SELECT 1 FROM likes 
        WHERE (user_id = ? AND liked_user_id = ?)
        OR (user_id = ? AND liked_user_id = ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $other_user_id,
        $other_user_id,
        $_SESSION['user_id']
    ]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Kein Match gefunden']);
        exit;
    }

    // Hole die Nachrichten
    $stmt = $pdo->prepare("
        SELECT m.*, u.username as sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
        OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $other_user_id,
        $other_user_id,
        $_SESSION['user_id']
    ]);
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
}
?> 