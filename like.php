<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

header('Content-Type: application/json');

// SMTP-Konfiguration für PHPMailer
$smtp_host = getenv('SMTP_HOST') ?: 'smtp.example.com';
$smtp_user = getenv('SMTP_USER') ?: 'user@example.com';
$smtp_pass = getenv('SMTP_PASS') ?: 'password';
$smtp_port = getenv('SMTP_PORT') ?: 587;
$smtp_from = getenv('SMTP_FROM') ?: 'noreply@example.com';
$smtp_from_name = getenv('SMTP_FROM_NAME') ?: 'Dating App';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage']);
    exit;
}

$liked_user_id = $_POST['user_id'] ?? null;

if (!$liked_user_id) {
    echo json_encode(['success' => false, 'message' => 'Fehlende Benutzer-ID']);
    exit;
}

try {
    // Starte eine Transaktion
    $pdo->beginTransaction();

    // Prüfe, ob der Benutzer bereits geliked wurde
    $stmt = $pdo->prepare("
        SELECT 1 FROM likes 
        WHERE user_id = ? AND liked_user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $liked_user_id]);
    
    if ($stmt->fetch()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Benutzer wurde bereits geliked']);
        exit;
    }

    // Prüfe, ob der Benutzer bereits gedisliked wurde
    $stmt = $pdo->prepare("
        SELECT 1 FROM dislikes 
        WHERE user_id = ? AND disliked_user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $liked_user_id]);
    
    if ($stmt->fetch()) {
        // Entferne den Dislike
        $stmt = $pdo->prepare("
            DELETE FROM dislikes 
            WHERE user_id = ? AND disliked_user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $liked_user_id]);
    }

    // Füge den Like hinzu
    $stmt = $pdo->prepare("
        INSERT INTO likes (user_id, liked_user_id, created_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$_SESSION['user_id'], $liked_user_id]);

    // Hole E-Mail und Namen des geliketen Users
    $stmt = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
    $stmt->execute([$liked_user_id]);
    $likedUser = $stmt->fetch(PDO::FETCH_ASSOC);

    // Hole Namen des likenden Users
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $likerUser = $stmt->fetch(PDO::FETCH_ASSOC);

    // Mailversand bei Like (nicht bei Match)
    if ($likedUser && !empty($likedUser['email'])) {
        $to = $likedUser['email'];
        $subject = 'Du hast einen neuen Like!';
        $body = 'Hallo ' . $likedUser['username'] . ', du wurdest soeben geliked!';

        $data = [
            'to' => $to,
            'subject' => $subject,
            'body' => $body
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'timeout' => 2
            ]
        ];
        $result = @file_get_contents("http://localhost:8088/", false, stream_context_create($options));
        if ($result === false) {
            error_log("PowerShell-Mailversand fehlgeschlagen: " . print_r(error_get_last(), true));
        }
    }

    // Prüfe auf Match
    $stmt = $pdo->prepare("
        SELECT 1 FROM likes 
        WHERE user_id = ? AND liked_user_id = ?
    ");
    $stmt->execute([$liked_user_id, $_SESSION['user_id']]);
    
    $isMatch = $stmt->fetch() !== false;

    if ($isMatch) {
        // Hole die Benutzerinformationen für das Match-Popup
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, 
                   (SELECT photo_path FROM user_photos WHERE user_id = u.id AND is_primary = 1 LIMIT 1) as photo_path
            FROM users u 
            WHERE u.id = ?
        ");
        $stmt->execute([$liked_user_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        // Erstelle einen neuen Chat für das Match
        $stmt = $pdo->prepare("
            INSERT INTO matches (user1_id, user2_id, created_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$_SESSION['user_id'], $liked_user_id]);

        // Füge eine Aktivität hinzu
        $stmt = $pdo->prepare("
            INSERT INTO user_activity (user_id, activity_type, activity_data, created_at)
            VALUES (?, 'match', ?, NOW())
        ");
        $activityData = json_encode([
            'matched_with' => $liked_user_id,
            'match_type' => 'mutual_like'
        ]);
        $stmt->execute([$_SESSION['user_id'], $activityData]);
        $stmt->execute([$liked_user_id, $activityData]);
    }

    // Füge eine Aktivität für den Like hinzu
    $stmt = $pdo->prepare("
        INSERT INTO user_activity (user_id, activity_type, activity_data, created_at)
        VALUES (?, 'like', ?, NOW())
    ");
    $activityData = json_encode([
        'liked_user' => $liked_user_id
    ]);
    $stmt->execute([$_SESSION['user_id'], $activityData]);

    // Commit die Transaktion
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Like erfolgreich',
        'isMatch' => $isMatch,
        'match' => $isMatch ? $match : null
    ]);
} catch (PDOException $e) {
    // Rollback bei Fehler
    $pdo->rollBack();
    error_log("Like Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}

set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Serverfehler: ' . $e->getMessage()]);
    exit;
});
?> 