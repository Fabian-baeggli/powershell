<?php
// Notfall-Debug: Immer reines JSON, nie HTML!
header('Content-Type: application/json');

// Keine Whitespaces oder BOM vor diesem Tag!

session_start();
require 'db.php';

set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Serverfehler: ' . $e->getMessage(),
        'session' => $_SESSION,
        'post' => $_POST,
        'get' => $_GET
    ]);
    exit;
});

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Nicht eingeloggt',
        'session' => $_SESSION
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT u.*,
               (SELECT photo_path FROM user_photos WHERE user_id = u.id AND is_primary = 1 LIMIT 1) as primary_photo
        FROM users u
        WHERE u.id != ?
        AND u.id NOT IN (SELECT liked_user_id FROM likes WHERE user_id = ?)
        AND u.id NOT IN (SELECT disliked_user_id FROM dislikes WHERE user_id = ?)
        ORDER BY RAND()
        LIMIT 1
    ");
    $success = $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Hole alle Fotos des Benutzers (kann leer sein)
        $stmt = $pdo->prepare("
            SELECT * FROM user_photos 
            WHERE user_id = ? 
            ORDER BY is_primary DESC, display_order
        ");
        $stmt->execute([$user['id']]);
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Wenn keine Fotos vorhanden, Platzhalter-Bild verwenden
        if (count($photos) === 0) {
            $photos = [[
                'photo_path' => '../bilder/hintergrund.png',
                'is_primary' => 1,
                'display_order' => 0
            ]];
        }
        // Generiere HTML für die Benutzerkarte
        $html = '
            <div class="photo-gallery">
                ' . implode('', array_map(function($photo, $index) {
                    return '<img src="' . htmlspecialchars($photo['photo_path']) . '" 
                                alt="Profilbild" 
                                style="display: ' . ($index === 0 ? 'block' : 'none') . '">';
                }, $photos, array_keys($photos))) . '
                <div class="photo-dots">
                    ' . implode('', array_map(function($index) {
                        return '<span class="dot' . ($index === 0 ? ' active' : '') . '" 
                                     onclick="showPhoto(' . $index . ')"></span>';
                    }, range(0, count($photos) - 1))) . '
                </div>
            </div>
            <div class="profile-info">
                <h2>' . htmlspecialchars($user['username']) . ', ' . $user['age'] . '</h2>
                <p><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($user['location'] ?? '') . '</p>
                <p><i class="fas fa-briefcase"></i> ' . htmlspecialchars($user['occupation'] ?? '') . '</p>
                <p><i class="fas fa-ruler-vertical"></i> ' . htmlspecialchars($user['height'] ?? '') . ' cm</p>
                <p class="bio">' . nl2br(htmlspecialchars($user['bio'] ?? '')) . '</p>
                <div class="interests">
                    <h3>Interessen</h3>
                    <div class="interest-tags">';
        $interests = explode(',', $user['interests'] ?? '');
        foreach ($interests as $interest) {
            if (trim($interest) !== '') {
                $html .= '<span class="interest-tag">' . htmlspecialchars(trim($interest)) . '</span>';
            }
        }
        $html .= '
                    </div>
                </div>
            </div>
            <div class="action-buttons">
                <button class="action-button dislike-button" onclick="dislikeUser(' . $user['id'] . ')">
                    <i class="fas fa-times"></i>
                </button>
                <button class="action-button like-button" onclick="likeUser(' . $user['id'] . ')">
                    <i class="fas fa-heart"></i>
                </button>
            </div>
        ';
        echo json_encode([
            'success' => true,
            'user' => $user,
            'photos' => $photos,
            'html' => $html
        ]);
    } else {
        $debug_users = [];
        $debug_stmt = $pdo->prepare("SELECT id, username, gender, looking_for FROM users WHERE id != ?");
        $debug_stmt->execute([$_SESSION['user_id']]);
        $debug_users = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => false,
            'user' => null,
            'message' => 'Alle Benutzer wurden bereits geliked oder gedisliked oder es gibt ein Problem.',
            'debug_users' => $debug_users,
            'session' => $_SESSION
        ]);
    }
} catch (Throwable $e) {
    error_log('DB-Fehler in get_next_user.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Datenbankfehler',
        'error' => $e->getMessage(),
        'session' => $_SESSION
    ]);
    exit;
}
?>