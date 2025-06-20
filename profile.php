<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

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
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mein Profil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f5f6fa;
            min-height: 100vh;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        .profile-header-bg {
            background: linear-gradient(135deg, #007AFF 0%, #5856D6 100%);
            height: 180px;
            border-bottom-left-radius: 40px;
            border-bottom-right-radius: 40px;
            position: relative;
        }
        .container {
            max-width: 420px;
            margin: -80px auto 0 auto;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.13);
            padding: 32px 20px 24px 20px;
            position: relative;
            z-index: 2;
            animation: fadeInUp 0.7s cubic-bezier(.39,.575,.565,1) both;
        }
        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(40px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .avatar-wrapper {
            display: flex;
            justify-content: center;
            margin-top: -70px;
            margin-bottom: 12px;
        }
        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #fff;
            box-shadow: 0 4px 16px rgba(0,0,0,0.10);
            background: #eee;
        }
        .profile-info {
            text-align: center;
            margin-bottom: 18px;
        }
        .profile-info h2 {
            margin: 0 0 6px 0;
            font-size: 2em;
            color: #222;
        }
        .profile-info .info-row {
            color: #666;
            font-size: 1em;
            margin: 2px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .photos-gallery {
            margin: 18px 0 0 0;
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding-bottom: 6px;
        }
        .photos-gallery img {
            width: 72px;
            height: 72px;
            border-radius: 14px;
            object-fit: cover;
            border: 2px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            transition: transform 0.2s;
        }
        .photos-gallery img:hover {
            transform: scale(1.07);
        }
        .section {
            margin-top: 22px;
        }
        .section h3 {
            margin-bottom: 8px;
            color: #5856D6;
            font-size: 1.1em;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .bio {
            background: #f7f7fa;
            border-radius: 10px;
            padding: 14px 14px 10px 14px;
            color: #333;
            font-size: 1.08em;
            min-height: 40px;
        }
        .interests {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .interest-tag {
            background: linear-gradient(90deg, #e0e7ff 0%, #f3f0ff 100%);
            color: #5856D6;
            border-radius: 16px;
            padding: 5px 14px;
            font-size: 0.98em;
            box-shadow: 0 1px 4px rgba(88,86,214,0.07);
        }
        .edit-btn {
            position: fixed;
            right: 24px;
            bottom: 32px;
            background: linear-gradient(90deg, #007AFF 0%, #5856D6 100%);
            color: #fff;
            padding: 16px 32px;
            border-radius: 32px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 4px 16px rgba(0,0,0,0.13);
            transition: background 0.2s, box-shadow 0.2s;
            z-index: 10;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .edit-btn:hover {
            background: linear-gradient(90deg, #005ecb 0%, #3d3d8a 100%);
            box-shadow: 0 6px 20px rgba(0,0,0,0.18);
        }
        @media (max-width: 600px) {
            .container {
                max-width: 98vw;
                padding: 18px 2vw 18vw 2vw;
            }
            .edit-btn {
                right: 10px;
                bottom: 18px;
                padding: 13px 20px;
                font-size: 1em;
            }
            .profile-photo {
                width: 90px;
                height: 90px;
            }
        }
        .back-btn {
            position: absolute;
            top: 24px;
            left: 24px;
            background: rgba(255,255,255,0.85);
            color: #5856D6;
            padding: 10px 18px;
            border-radius: 24px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
            z-index: 20;
        }
        .back-btn:hover {
            background: #e0e7ff;
        }
    </style>
</head>
<body>
    <a href="home.php" class="back-btn"><i class="fas fa-arrow-left"></i> Zurück</a>
    <div class="profile-header-bg"></div>
    <div class="container">
        <div class="avatar-wrapper">
            <img class="profile-photo" src="<?php echo isset($photos[0]) ? 'uploads/' . htmlspecialchars($photos[0]['photo_path']) : 'https://via.placeholder.com/120?text=Profil'; ?>" alt="Profilbild">
        </div>
        <div class="profile-info">
            <h2><?php echo htmlspecialchars($user['username'] ?? ''); ?><?php if (!empty($user['age'])) echo ', ' . $user['age']; ?></h2>
            <div class="info-row"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($user['location'] ?? ''); ?></div>
            <div class="info-row"><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($user['occupation'] ?? ''); ?></div>
            <div class="info-row"><i class="fas fa-ruler-vertical"></i> <?php echo htmlspecialchars($user['height'] ?? ''); ?> cm</div>
        </div>
        <?php if (count($photos) > 1): ?>
            <div class="photos-gallery">
                <?php foreach ($photos as $photo): ?>
                    <img src="uploads/<?php echo htmlspecialchars($photo['photo_path']); ?>" alt="Profilfoto">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="section">
            <h3><i class="fas fa-user"></i> Bio</h3>
            <div class="bio"><?php echo nl2br(htmlspecialchars($user['bio'] ?? '')); ?></div>
        </div>
        <div class="section">
            <h3><i class="fas fa-star"></i> Interessen</h3>
            <div class="interests">
                <?php foreach (explode(',', $user['interests'] ?? '') as $interest): ?>
                    <?php if (trim($interest)): ?>
                        <span class="interest-tag"><?php echo htmlspecialchars(trim($interest)); ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <a href="edit_profile.php" class="edit-btn"><i class="fas fa-user-edit"></i> Profil bearbeiten</a>
</body>
</html> 