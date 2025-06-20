<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

try {
    // Hole alle Matches
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.age, 
               (SELECT photo_path FROM user_photos WHERE user_id = u.id AND is_primary = 1 LIMIT 1) as photo_path
        FROM users u
        JOIN likes l1 ON u.id = l1.liked_user_id
        JOIN likes l2 ON u.id = l2.user_id
        WHERE l1.user_id = ? AND l2.liked_user_id = ?
        ORDER BY u.username
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'matches' => $matches
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
}
?> 