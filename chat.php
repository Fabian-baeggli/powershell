<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$other_user_id = $_GET['user_id'] ?? null;

if (!$other_user_id) {
    header('Location: messages.php');
    exit;
}

// Hole Benutzerinformationen
$stmt = $pdo->prepare("
    SELECT u.*, 
           (SELECT photo_path FROM user_photos WHERE user_id = u.id AND is_primary = 1 LIMIT 1) as photo_path
    FROM users u 
    WHERE u.id = ?
");
$stmt->execute([$other_user_id]);
$other_user = $stmt->fetch();

if (!$other_user) {
    header('Location: messages.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat mit <?php echo htmlspecialchars($other_user['username']); ?> - Dating App</title>
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
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: #ff4b6e;
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-button {
            background: none;
            border: none;
            color: white;
            font-size: 1.2em;
            cursor: pointer;
            padding: 5px;
        }

        .user-photo {
            width: 40px;
            height: 40px;
            border-radius: 20px;
            object-fit: cover;
        }

        .user-info {
            flex: 1;
        }

        .user-info h2 {
            font-size: 1.2em;
            margin: 0;
        }

        .user-info p {
            font-size: 0.9em;
            margin: 0;
            opacity: 0.8;
        }

        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
            background: white;
        }

        .messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .message {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 15px;
            position: relative;
        }

        .message.sent {
            align-self: flex-end;
            background: #ff4b6e;
            color: white;
            border-bottom-right-radius: 5px;
        }

        .message.received {
            align-self: flex-start;
            background: #f0f0f0;
            color: #333;
            border-bottom-left-radius: 5px;
        }

        .message-time {
            font-size: 0.7em;
            opacity: 0.7;
            margin-top: 5px;
            text-align: right;
        }

        .input-container {
            padding: 15px;
            background: white;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }

        .message-input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 25px;
            outline: none;
            font-size: 1em;
        }

        .send-button {
            background: #ff4b6e;
            color: white;
            border: none;
            border-radius: 25px;
            width: 45px;
            height: 45px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
        }

        .send-button:hover {
            background: #ff3357;
        }

        .send-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="header">
        <button onclick="window.location.href='messages.php'" class="back-button">
            <i class="fas fa-arrow-left"></i>
        </button>
        <img src="uploads/<?php echo htmlspecialchars($other_user['photo_path']); ?>" alt="<?php echo htmlspecialchars($other_user['username']); ?>" class="user-photo">
        <div class="user-info">
            <h2><?php echo htmlspecialchars($other_user['username']); ?></h2>
            <p>Online</p>
        </div>
    </div>

    <div class="chat-container">
        <div class="messages" id="messages">
            <!-- Nachrichten werden dynamisch geladen -->
        </div>
        <div class="input-container">
            <input type="text" class="message-input" id="messageInput" placeholder="Schreibe eine Nachricht...">
            <button class="send-button" id="sendButton" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <script>
        const otherUserId = <?php echo $other_user_id; ?>;
        const messagesContainer = document.getElementById('messages');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');

        function loadMessages() {
            fetch(`get_messages.php?user_id=${otherUserId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messagesContainer.innerHTML = '';
                    
                    data.messages.forEach(message => {
                        const messageElement = document.createElement('div');
                        messageElement.className = `message ${message.sender_id == <?php echo $_SESSION['user_id']; ?> ? 'sent' : 'received'}`;
                        messageElement.innerHTML = `
                            <div class="message-content">${message.message}</div>
                            <div class="message-time">${new Date(message.created_at).toLocaleTimeString()}</div>
                        `;
                        messagesContainer.appendChild(messageElement);
                    });
                    
                    // Scrolle zum Ende der Nachrichten
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Fehler beim Laden der Nachrichten');
            });
        }

        function sendMessage() {
            const message = messageInput.value.trim();
            
            if (!message) return;
            
            // Deaktiviere den Senden-Button
            sendButton.disabled = true;
            
            fetch('send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    receiver_id: otherUserId,
                    message: message
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Füge die neue Nachricht hinzu
                    const messageElement = document.createElement('div');
                    messageElement.className = 'message sent';
                    messageElement.innerHTML = `
                        <div class="message-content">${message}</div>
                        <div class="message-time">${new Date().toLocaleTimeString()}</div>
                    `;
                    messagesContainer.appendChild(messageElement);
                    
                    // Leere das Eingabefeld
                    messageInput.value = '';
                    
                    // Scrolle zum Ende der Nachrichten
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Fehler beim Senden der Nachricht');
            })
            .finally(() => {
                // Aktiviere den Senden-Button wieder
                sendButton.disabled = false;
            });
        }

        // Event-Listener für Enter-Taste
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Lade Nachrichten beim Start
        loadMessages();

        // Lade Nachrichten alle 5 Sekunden neu
        setInterval(loadMessages, 5000);
    </script>
</body>
</html>
