<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$me = $_SESSION['user_id'];

try {
    // Einen zufälligen User holen, den wir noch nicht geliked/disliked haben
    $stmt = $pdo->prepare("
        SELECT id, username FROM users 
        WHERE id != ? 
        AND id NOT IN (
            SELECT liked_id FROM likes WHERE liker_id = ?
            UNION
            SELECT disliked_id FROM dislikes WHERE disliker_id = ?
        )
        ORDER BY RAND() 
        LIMIT 1
    ");
    $stmt->execute([$me, $me, $me]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => htmlspecialchars($user['username'])
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'user' => null
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>