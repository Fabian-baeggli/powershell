<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$receiver_id = $data['receiver_id'] ?? null;
$message = $data['message'] ?? null;

if (!$receiver_id || !$message) {
    echo json_encode(['success' => false, 'message' => 'Fehlende Parameter']);
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
        $receiver_id,
        $receiver_id,
        $_SESSION['user_id']
    ]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Kein Match gefunden']);
        exit;
    }

    // Füge die Nachricht hinzu
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, message, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$_SESSION['user_id'], $receiver_id, $message]);

    // Hole die neue Nachricht mit Sender-Informationen
    $stmt = $pdo->prepare("
        SELECT m.*, u.username as sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.id = ?
    ");
    $stmt->execute([$pdo->lastInsertId()]);
    $new_message = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Nachricht gesendet',
        'data' => $new_message
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
}
?>
