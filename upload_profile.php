<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$message = '';
$user = null;

// Benutzerdaten laden
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_image'];
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $file['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            // Maximale Dateigröße: 5MB
            if ($file['size'] > 5 * 1024 * 1024) {
                $message = "Die Datei ist zu groß. Maximale Größe: 5MB";
            } else {
                $upload_dir = __DIR__ . '/uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generiere einen eindeutigen Dateinamen
                $new_filename = $upload_dir . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                $db_filename = 'uploads/' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;

                // Versuche das Bild zu verarbeiten
                if (move_uploaded_file($file['tmp_name'], $new_filename)) {
                    // Aktualisiere die Datenbank
                    $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    if ($stmt->execute([$db_filename, $_SESSION['user_id']])) {
                        $message = "Profilbild erfolgreich aktualisiert!";
                        header("Location: home.php");
                        exit;
                    } else {
                        $message = "Fehler beim Speichern in der Datenbank.";
                        // Lösche die hochgeladene Datei, wenn die DB-Aktualisierung fehlschlägt
                        unlink($new_filename);
                    }
                } else {
                    $message = "Fehler beim Hochladen des Bildes. Bitte versuchen Sie es erneut.";
                }
            }
        } else {
            $message = "Nur JPG und PNG Dateien sind erlaubt.";
        }
    } else if (isset($_FILES['profile_image'])) {
        switch ($_FILES['profile_image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $message = "Die Datei ist zu groß.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "Die Datei wurde nur teilweise hochgeladen.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "Keine Datei ausgewählt.";
                break;
            default:
                $message = "Ein unbekannter Fehler ist aufgetreten.";
        }
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
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('hintergrund.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 30px;
            backdrop-filter: blur(10px);
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            background-color: #f8d7da;
            color: #721c24;
        }

        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .file-input-container {
            position: relative;
            text-align: center;
        }

        .file-input-label {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-input-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .file-input {
            display: none;
        }

        .file-name {
            margin-top: 10px;
            color: #666;
            font-size: 0.9em;
        }

        .submit-btn {
            padding: 12px 24px;
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .back-btn:hover {
            color: #333;
        }

        .preview-container {
            margin: 20px 0;
            text-align: center;
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Profilbild ändern</h1>
        <?php if($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="upload-form">
            <div class="file-input-container">
                <label for="profile_image" class="file-input-label">Bild auswählen</label>
                <input type="file" id="profile_image" name="profile_image" class="file-input" accept=".jpg,.jpeg,.png" onchange="updateFileName(this)">
                <div class="file-name" id="file-name">Keine Datei ausgewählt</div>
            </div>

            <?php if($user && $user['profile_image']): ?>
            <div class="preview-container">
                <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Aktuelles Profilbild" class="preview-image">
            </div>
            <?php endif; ?>

            <button type="submit" class="submit-btn">Profilbild aktualisieren</button>
        </form>

        <a href="home.php" class="back-btn">← Zurück zur Startseite</a>
    </div>

    <script>
    function updateFileName(input) {
        const fileName = input.files[0]?.name || 'Keine Datei ausgewählt';
        document.getElementById('file-name').textContent = fileName;
    }
    </script>
</body>
</html> 