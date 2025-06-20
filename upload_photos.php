<?php
session_start();
require 'db.php';

// Erstelle uploads Ordner, falls er nicht existiert
$upload_dir = __DIR__ . '/uploads';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Setze Berechtigungen für den uploads Ordner
chmod($upload_dir, 0777);

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$message = '';
$error = '';

// Verarbeite hochgeladene Dateien
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photos'])) {
    $allowed_types = ['image/jpeg', 'image/png'];
    $max_size = 5 * 1024 * 1024; // 5MB

    $uploaded_files = [];
    $errors = [];

    foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
            $file_type = $_FILES['photos']['type'][$key];
            $file_size = $_FILES['photos']['size'][$key];

            if (!in_array($file_type, $allowed_types)) {
                $errors[] = "Datei " . $_FILES['photos']['name'][$key] . " ist kein gültiges Bildformat (nur JPG/PNG erlaubt)";
                continue;
            }

            if ($file_size > $max_size) {
                $errors[] = "Datei " . $_FILES['photos']['name'][$key] . " ist zu groß (max 5MB)";
                continue;
            }

            $file_extension = pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . '/' . $new_filename;

            if (move_uploaded_file($tmp_name, $target_path)) {
                $uploaded_files[] = $new_filename;
            } else {
                $errors[] = "Fehler beim Hochladen von " . $_FILES['photos']['name'][$key];
            }
        }
    }

    if (!empty($uploaded_files)) {
        try {
            // Beginne Transaktion
            $pdo->beginTransaction();

            // Hole aktuelle Anzahl der Fotos des Users
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_photos WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $current_photo_count = $stmt->fetchColumn();

            // Füge neue Fotos hinzu
            $stmt = $pdo->prepare("INSERT INTO user_photos (user_id, photo_path, is_primary, display_order) VALUES (?, ?, ?, ?)");
            
            foreach ($uploaded_files as $index => $filename) {
                $is_primary = ($current_photo_count === 0 && $index === 0) ? 1 : 0;
                $display_order = $current_photo_count + $index;
                $stmt->execute([$_SESSION['user_id'], $filename, $is_primary, $display_order]);
            }

            $pdo->commit();
            $message = "Fotos erfolgreich hochgeladen!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Datenbankfehler beim Speichern der Fotos.";
        }
    }

    if (!empty($errors)) {
        $error = implode("<br>", $errors);
    }
}

// Hole alle Fotos des Users
$stmt = $pdo->prepare("SELECT * FROM user_photos WHERE user_id = ? ORDER BY display_order");
$stmt->execute([$_SESSION['user_id']]);
$photos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fotos hochladen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .upload-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .photo-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 10px;
            overflow: hidden;
        }
        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-actions {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            padding: 10px;
            display: flex;
            justify-content: space-around;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .photo-item:hover .photo-actions {
            opacity: 1;
        }
        .photo-actions button {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 5px 10px;
        }
        .photo-actions button:hover {
            color: #007bff;
        }
        .primary-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        .upload-area:hover {
            border-color: #007bff;
        }
        .upload-area i {
            font-size: 48px;
            color: #6c757d;
            margin-bottom: 10px;
        }
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="upload-container">
            <h2 class="text-center mb-4">Meine Fotos</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="post" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-area" id="dropZone">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h4>Fotos hierher ziehen oder klicken zum Auswählen</h4>
                    <p class="text-muted">Unterstützte Formate: JPG, PNG (max. 5MB)</p>
                    <input type="file" name="photos[]" id="fileInput" multiple accept="image/jpeg,image/png" style="display: none;">
                </div>
            </form>

            <div class="photo-grid">
                <?php foreach ($photos as $photo): ?>
                    <div class="photo-item">
                        <img src="uploads/<?php echo htmlspecialchars($photo['photo_path']); ?>" alt="Profilfoto">
                        <?php if ($photo['is_primary']): ?>
                            <div class="primary-badge">Hauptfoto</div>
                        <?php endif; ?>
                        <div class="photo-actions">
                            <?php if (!$photo['is_primary']): ?>
                                <button onclick="setPrimary(<?php echo $photo['id']; ?>)">
                                    <i class="fas fa-star"></i> Hauptfoto
                                </button>
                            <?php endif; ?>
                            <button onclick="deletePhoto(<?php echo $photo['id']; ?>)">
                                <i class="fas fa-trash"></i> Löschen
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="nav-buttons">
                <a href="home.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Zurück zur Startseite
                </a>
                <a href="edit_profile.php" class="btn btn-secondary">
                    <i class="fas fa-user-edit"></i> Profil bearbeiten
                </a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Drag & Drop Funktionalität
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');

        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#007bff';
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.style.borderColor = '#ddd';
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#ddd';
            fileInput.files = e.dataTransfer.files;
            document.getElementById('uploadForm').submit();
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                document.getElementById('uploadForm').submit();
            }
        });

        // Foto-Aktionen
        function setPrimary(photoId) {
            fetch('photo_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=set_primary&photo_id=' + photoId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ein Fehler ist aufgetreten');
            });
        }

        function deletePhoto(photoId) {
            if (confirm('Möchten Sie dieses Foto wirklich löschen?')) {
                fetch('photo_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=delete&photo_id=' + photoId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ein Fehler ist aufgetreten');
                });
            }
        }
    </script>
</body>
</html> 