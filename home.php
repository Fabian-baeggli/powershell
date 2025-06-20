<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dating App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007AFF;
            --secondary-color: #5856D6;
            --success-color: #34C759;
            --danger-color: #FF3B30;
            --background-color: #F2F2F7;
            --card-background: #FFFFFF;
            --text-primary: #000000;
            --text-secondary: #8E8E93;
            --border-color: #C6C6C8;
            --shadow-color: rgba(0, 0, 0, 0.1);
        }
        body {
            background: var(--background-color);
            color: var(--text-primary);
            min-height: 100vh;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px 0 100px 0;
            min-height: 80vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .profile-card {
            width: 100%;
            min-height: 500px;
            background: var(--card-background);
            border-radius: 20px;
            box-shadow: 0 8px 20px var(--shadow-color);
            margin-top: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: box-shadow 0.3s;
        }
        .menu-bar {
            background: var(--card-background);
            padding: 15px;
            display: flex;
            justify-content: space-around;
            box-shadow: 0 -2px 10px var(--shadow-color);
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 10;
        }
        .menu-item {
            text-decoration: none;
            color: var(--text-secondary);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            transition: color 0.3s ease;
        }
        .menu-item.active {
            color: var(--primary-color);
        }
        .menu-item i {
            font-size: 1.5em;
        }
        .menu-item span {
            font-size: 0.9em;
            font-weight: 500;
        }
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            color: #fff;
            background: var(--danger-color);
            padding: 10px 18px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 2px 8px var(--shadow-color);
            transition: background 0.2s;
            z-index: 1000;
        }
        .logout-btn:hover {
            background: #d32f2f;
        }
        .no-more-users {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }
        .no-more-users i {
            font-size: 4em;
            color: var(--border-color);
            margin-bottom: 20px;
        }
        .no-more-users h3 {
            margin-bottom: 10px;
            font-size: 1.5em;
            color: var(--text-primary);
        }
        .no-more-users p {
            color: var(--text-secondary);
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    <div class="container">
        <div class="profile-card">
            <!-- Profil-Karte wird dynamisch geladen -->
        </div>
    </div>
    <div class="menu-bar">
        <a href="home.php" class="menu-item active">
            <i class="fas fa-home"></i>
            <span>Entdecken</span>
        </a>
        <a href="messages.php" class="menu-item">
            <i class="fas fa-comment"></i>
            <span>Nachrichten</span>
        </a>
        <a href="profile.php" class="menu-item">
            <i class="fas fa-user"></i>
            <span>Profil</span>
        </a>
    </div>
    <script>
        let currentUserId = null;
        let currentPhotoIndex = 0;
        let currentPhotos = [];
        function loadNextUser() {
            fetch('get_next_user.php')
            .then(response => response.json())
            .then(data => {
                console.log('Antwort von get_next_user.php:', data); // Debug-Ausgabe
                if (data.success) {
                    if (data.user && data.html) {
                        document.querySelector('.profile-card').innerHTML = data.html;
                        currentUserId = data.user.id;
                        currentPhotos = data.photos;
                        // updatePhotoGallery(); // Optional: Wenn du eine Galerie hast
                    } else if (data.user === null) {
                        document.querySelector('.profile-card').innerHTML = `
                            <div class=\"no-more-users\">
                                <i class=\"fas fa-heart-broken\"></i>
                                <h3>Keine weiteren Profile verfügbar</h3>
                                <p>Lege einen zweiten Testuser an, um das System zu testen!</p>
                            </div>
                        `;
                    } else {
                        document.querySelector('.profile-card').innerHTML = `<div style=\"color:red; padding:2em;\">Fehler: Keine Profilkarte erhalten.<br>Debug: <pre>${JSON.stringify(data, null, 2)}</pre></div>`;
                    }
                } else {
                    if (data.message && data.message.includes('Nicht eingeloggt')) {
                        alert('Deine Sitzung ist abgelaufen. Bitte logge dich erneut ein.');
                        window.location.href = 'index.php';
                    } else {
                        document.querySelector('.profile-card').innerHTML = `<div style=\"color:red; padding:2em;\">Fehler: ${data.message}</div>`;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.querySelector('.profile-card').innerHTML = `<div style=\"color:red; padding:2em;\">Fehler beim Laden der Daten.<br>${error}</div>`;
            });
        }
        function likeUser(userId) {
            fetch('like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'user_id=' + encodeURIComponent(userId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.isMatch) {
                        showMatchPopup(data.match);
                    } else {
                        loadNextUser();
                    }
                } else {
                    if (data.message && data.message.includes('Nicht eingeloggt')) {
                        alert('Deine Sitzung ist abgelaufen. Bitte logge dich erneut ein.');
                        window.location.href = 'index.php';
                    } else {
                        alert(data.message);
                        loadNextUser();
                    }
                }
            })
            .catch(error => {
                console.error('Like Error:', error);
                alert('Ein Fehler ist aufgetreten');
                loadNextUser();
            });
        }
        function dislikeUser(userId) {
            fetch('dislike.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'user_id=' + encodeURIComponent(userId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNextUser();
                } else {
                    if (data.message && data.message.includes('Nicht eingeloggt')) {
                        alert('Deine Sitzung ist abgelaufen. Bitte logge dich erneut ein.');
                        window.location.href = 'index.php';
                    } else {
                        alert(data.message);
                        loadNextUser();
                    }
                }
            })
            .catch(error => {
                console.error('Dislike Error:', error);
                alert('Ein Fehler ist aufgetreten');
                loadNextUser();
            });
        }
        // Lade den ersten Benutzer beim Start
        loadNextUser();
    </script>
</body>
</html>