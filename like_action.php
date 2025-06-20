<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

if (!isset($_POST['action']) || !isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Fehlende Parameter']);
    exit;
}

$action = $_POST['action'];
$user_id = $_SESSION['user_id'];
$target_id = $_POST['user_id'];

try {
    if ($action === 'like') {
        // Prüfen ob bereits ein Like existiert
        $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND liked_user_id = ?");
        $stmt->execute([$user_id, $target_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Bereits geliked']);
            exit;
        }

        // Like hinzufügen
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, liked_user_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $target_id]);

        // Prüfen ob es ein Match gibt
        $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND liked_user_id = ?");
        $stmt->execute([$target_id, $user_id]);
        $isMatch = $stmt->fetch();

        echo json_encode([
            'success' => true,
            'message' => $isMatch ? 'Neues Match!' : 'Erfolgreich geliked',
            'isMatch' => $isMatch ? true : false
        ]);

    } elseif ($action === 'dislike') {
        // Prüfen ob bereits ein Dislike existiert
        $stmt = $pdo->prepare("SELECT id FROM dislikes WHERE user_id = ? AND disliked_user_id = ?");
        $stmt->execute([$user_id, $target_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Bereits gedisliked']);
            exit;
        }

        // Dislike hinzufügen
        $stmt = $pdo->prepare("INSERT INTO dislikes (user_id, disliked_user_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $target_id]);

        echo json_encode(['success' => true, 'message' => 'Erfolgreich gedisliked']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Ungültige Aktion']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
}
?>