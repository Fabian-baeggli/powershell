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

$action = $_POST['action'] ?? '';
$photoId = $_POST['photo_id'] ?? 0;

if (!$photoId) {
    echo json_encode(['success' => false, 'message' => 'Keine Foto-ID angegeben']);
    exit;
}

try {
    // Prüfe, ob das Foto dem Benutzer gehört
    $stmt = $pdo->prepare("SELECT * FROM user_photos WHERE id = ? AND user_id = ?");
    $stmt->execute([$photoId, $_SESSION['user_id']]);
    $photo = $stmt->fetch();

    if (!$photo) {
        echo json_encode(['success' => false, 'message' => 'Foto nicht gefunden']);
        exit;
    }

    switch ($action) {
        case 'delete':
            // Lösche das Foto
            if (file_exists($photo['photo_path'])) {
                unlink($photo['photo_path']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM user_photos WHERE id = ?");
            $stmt->execute([$photoId]);

            // Wenn es das primäre Foto war, setze ein anderes als primär
            if ($photo['is_primary']) {
                $stmt = $pdo->prepare("
                    UPDATE user_photos 
                    SET is_primary = 1 
                    WHERE user_id = ? 
                    ORDER BY display_order ASC 
                    LIMIT 1
                ");
                $stmt->execute([$_SESSION['user_id']]);
            }

            echo json_encode(['success' => true]);
            break;

        case 'set_primary':
            // Setze alle Fotos des Benutzers auf nicht-primär
            $stmt = $pdo->prepare("UPDATE user_photos SET is_primary = 0 WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);

            // Setze das ausgewählte Foto als primär
            $stmt = $pdo->prepare("UPDATE user_photos SET is_primary = 1 WHERE id = ?");
            $stmt->execute([$photoId]);

            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Ungültige Aktion']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
} 