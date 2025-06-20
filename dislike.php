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

$disliked_user_id = $_POST['user_id'] ?? null;

if (!$disliked_user_id) {
    echo json_encode(['success' => false, 'message' => 'Fehlende Benutzer-ID']);
    exit;
}

try {
    // Starte eine Transaktion
    $pdo->beginTransaction();

    // Prüfe, ob der Benutzer bereits gedisliked wurde
    $stmt = $pdo->prepare("
        SELECT 1 FROM dislikes 
        WHERE user_id = ? AND disliked_user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $disliked_user_id]);
    
    if ($stmt->fetch()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Benutzer wurde bereits gedisliked']);
        exit;
    }

    // Prüfe, ob der Benutzer bereits geliked wurde
    $stmt = $pdo->prepare("
        SELECT 1 FROM likes 
        WHERE user_id = ? AND liked_user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $disliked_user_id]);
    
    if ($stmt->fetch()) {
        // Entferne den Like
        $stmt = $pdo->prepare("
            DELETE FROM likes 
            WHERE user_id = ? AND liked_user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $disliked_user_id]);
    }

    // Füge den Dislike hinzu
    $stmt = $pdo->prepare("
        INSERT INTO dislikes (user_id, disliked_user_id, created_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$_SESSION['user_id'], $disliked_user_id]);

    // Füge eine Aktivität hinzu
    $stmt = $pdo->prepare("
        INSERT INTO user_activity (user_id, activity_type, activity_data, created_at)
        VALUES (?, 'dislike', ?, NOW())
    ");
    $activityData = json_encode([
        'disliked_user' => $disliked_user_id
    ]);
    $stmt->execute([$_SESSION['user_id'], $activityData]);

    // Commit die Transaktion
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Dislike erfolgreich'
    ]);
} catch (PDOException $e) {
    // Rollback bei Fehler
    $pdo->rollBack();
    error_log("Dislike Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Ein Fehler ist aufgetreten. Bitte versuche es später erneut.'
    ]);
}

set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Serverfehler: ' . $e->getMessage()]);
    exit;
});
?> 