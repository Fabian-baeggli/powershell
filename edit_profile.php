<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$message = '';
$error = '';

// Hole Benutzerdaten
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Hole alle Fotos des Benutzers
$stmt = $pdo->prepare("
    SELECT * FROM user_photos 
    WHERE user_id = ? 
    ORDER BY is_primary DESC, display_order ASC
");
$stmt->execute([$_SESSION['user_id']]);
$photos = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Aktualisiere Benutzerdaten
        $stmt = $pdo->prepare("
            UPDATE users 
            SET bio = ?, 
                location = ?, 
                interests = ?, 
                occupation = ?, 
                height = ?, 
                gender = ?, 
                looking_for = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['bio'],
            $_POST['location'],
            $_POST['interests'],
            $_POST['occupation'],
            $_POST['height'],
            $_POST['gender'],
            $_POST['looking_for'],
            $_SESSION['user_id']
        ]);

        $message = "Profil erfolgreich aktualisiert!";
    } catch (PDOException $e) {
        $error = "Fehler beim Speichern des Profils: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil bearbeiten</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        input, textarea, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        textarea {
            height: 100px;
            resize: vertical;
        }

        .photos-section {
            margin-bottom: 30px;
        }

        .photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .photo-item {
            position: relative;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .photo-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .primary-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #4CAF50;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            text-align: center;
            transition: background 0.3s ease;
        }

        .btn-primary {
            background: #4CAF50;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Profil bearbeiten</h1>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="photos-section">
            <h2>Meine Fotos</h2>
            <div class="photos-grid">
                <?php foreach ($photos as $photo): ?>
                    <div class="photo-item">
                        <img src="<?php echo htmlspecialchars($photo['photo_path']); ?>" alt="Profilbild">
                        <?php if ($photo['is_primary']): ?>
                            <div class="primary-badge">Primär</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="upload_photos.php" class="btn btn-primary">Fotos verwalten</a>
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="bio">Über mich</label>
                <textarea id="bio" name="bio"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="location">Standort</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="occupation">Beruf</label>
                <input type="text" id="occupation" name="occupation" value="<?php echo htmlspecialchars($user['occupation'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="height">Größe (cm)</label>
                <input type="number" id="height" name="height" value="<?php echo htmlspecialchars($user['height'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="interests">Interessen (durch Komma getrennt)</label>
                <input type="text" id="interests" name="interests" value="<?php echo htmlspecialchars($user['interests'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="gender">Geschlecht</label>
                <select id="gender" name="gender">
                    <option value="m" <?php echo ($user['gender'] ?? '') === 'm' ? 'selected' : ''; ?>>Männlich</option>
                    <option value="f" <?php echo ($user['gender'] ?? '') === 'f' ? 'selected' : ''; ?>>Weiblich</option>
                    <option value="d" <?php echo ($user['gender'] ?? '') === 'd' ? 'selected' : ''; ?>>Divers</option>
                </select>
            </div>

            <div class="form-group">
                <label for="looking_for">Suche nach</label>
                <select id="looking_for" name="looking_for">
                    <option value="m" <?php echo ($user['looking_for'] ?? '') === 'm' ? 'selected' : ''; ?>>Männlich</option>
                    <option value="f" <?php echo ($user['looking_for'] ?? '') === 'f' ? 'selected' : ''; ?>>Weiblich</option>
                    <option value="b" <?php echo ($user['looking_for'] ?? '') === 'b' ? 'selected' : ''; ?>>Beides</option>
                </select>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="home.php" class="btn btn-secondary">Zurück</a>
            </div>
        </form>
    </div>
</body>
</html> 