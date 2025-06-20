<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Hole Benutzerinformationen
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nachrichten - Dating App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: #ff4b6e;
            color: white;
            padding: 15px 20px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.5em;
            margin: 0;
        }

        .back-button {
            background: none;
            border: none;
            color: white;
            font-size: 1.2em;
            cursor: pointer;
            padding: 5px;
        }

        .matches-list {
            margin-top: 80px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .match-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .match-item:hover {
            transform: translateY(-2px);
        }

        .match-photo {
            width: 60px;
            height: 60px;
            border-radius: 30px;
            object-fit: cover;
        }

        .match-info {
            flex: 1;
        }

        .match-info h3 {
            margin: 0 0 5px 0;
            font-size: 1.1em;
        }

        .match-info p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }

        .last-message {
            color: #999;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .unread {
            background: #fff0f3;
        }

        .unread .match-info h3 {
            color: #ff4b6e;
        }

        .no-matches {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .no-matches i {
            font-size: 3em;
            color: #ddd;
            margin-bottom: 20px;
        }

        .no-matches h3 {
            margin-bottom: 10px;
        }

        .no-matches p {
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <button onclick="window.location.href='home.php'" class="back-button">
            <i class="fas fa-arrow-left"></i>
        </button>
        <h1>Nachrichten</h1>
        <div style="width: 24px;"></div> <!-- Platzhalter für symmetrisches Layout -->
    </div>

    <div class="container">
        <div class="matches-list" id="matchesList">
            <!-- Matches werden dynamisch geladen -->
        </div>
    </div>

    <script>
        function loadMatches() {
            fetch('get_matches.php')
            .then(response => response.json())
            .then(data => {
                const matchesList = document.getElementById('matchesList');
                
                if (data.success && data.matches.length > 0) {
                    matchesList.innerHTML = '';
                    
                    data.matches.forEach(match => {
                        const matchElement = document.createElement('a');
                        matchElement.href = `chat.php?user_id=${match.id}`;
                        matchElement.className = 'match-item';
                        matchElement.innerHTML = `
                            <img src="uploads/${match.photo_path}" alt="${match.username}" class="match-photo">
                            <div class="match-info">
                                <h3>${match.username}</h3>
                                <p>${match.age} Jahre</p>
                                <div class="last-message">
                                    <i class="fas fa-circle" style="font-size: 0.5em; color: #4CAF50;"></i>
                                    Online
                                </div>
                            </div>
                        `;
                        matchesList.appendChild(matchElement);
                    });
                } else {
                    matchesList.innerHTML = `
                        <div class="no-matches">
                            <i class="fas fa-comments"></i>
                            <h3>Keine Matches gefunden</h3>
                            <p>Finde neue Matches, um Nachrichten zu senden</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Fehler beim Laden der Matches');
            });
        }

        // Lade Matches beim Start
        loadMatches();
    </script>
</body>
</html> 