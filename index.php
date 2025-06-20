<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'register') {
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $email = $_POST['email'];
            $gender = $_POST['gender'];
            $looking_for = $_POST['looking_for'];
            $age = $_POST['age'];
            $bio = '';
            $location = '';
            $occupation = '';
            $height = null;
            $interests = '';

            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, gender, looking_for, age, bio, location, occupation, height, interests) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $password, $email, $gender, $looking_for, $age, $bio, $location, $occupation, $height, $interests]);
                $_SESSION['user_id'] = $pdo->lastInsertId();
                header("Location: home.php");
                exit;
            } catch (PDOException $e) {
                $error = "Registrierung fehlgeschlagen: " . $e->getMessage();
            }
        } elseif ($_POST['action'] == 'login') {
            $username = $_POST['username'];
            $password = $_POST['password'];

            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                header("Location: home.php");
                exit;
            } else {
                $error = "Ungültiger Benutzername oder Passwort";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dating App</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #ff6b6b, #4ecdc4);
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }

        .tabs {
            display: flex;
            margin-bottom: 2rem;
        }

        .tab {
            flex: 1;
            text-align: center;
            padding: 1rem;
            cursor: pointer;
            border-bottom: 2px solid #eee;
        }

        .tab.active {
            border-bottom: 2px solid #ff6b6b;
            color: #ff6b6b;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #666;
        }

        input, select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        button {
            width: 100%;
            padding: 1rem;
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #ff5252;
        }

        .error {
            color: #ff6b6b;
            margin-bottom: 1rem;
        }

        .form {
            display: none;
        }

        .form.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="tabs">
            <div class="tab active" onclick="showTab('login')">Anmelden</div>
            <div class="tab" onclick="showTab('register')">Registrieren</div>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form id="login-form" class="form active" method="POST">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label for="login-username">Benutzername</label>
                <input type="text" id="login-username" name="username" required>
            </div>
            <div class="form-group">
                <label for="login-password">Passwort</label>
                <input type="password" id="login-password" name="password" required>
            </div>
            <button type="submit">Anmelden</button>
        </form>

        <form id="register-form" class="form" method="POST">
            <input type="hidden" name="action" value="register">
            <div class="form-group">
                <label for="register-username">Benutzername</label>
                <input type="text" id="register-username" name="username" required>
            </div>
            <div class="form-group">
                <label for="register-email">E-Mail</label>
                <input type="email" id="register-email" name="email" required>
            </div>
            <div class="form-group">
                <label for="register-password">Passwort</label>
                <input type="password" id="register-password" name="password" required>
            </div>
            <div class="form-group">
                <label for="register-age">Alter</label>
                <input type="number" id="register-age" name="age" min="18" max="100" required>
            </div>
            <div class="form-group">
                <label for="register-gender">Geschlecht</label>
                <select id="register-gender" name="gender" required>
                    <option value="m">Männlich</option>
                    <option value="f">Weiblich</option>
                    <option value="d">Divers</option>
                </select>
            </div>
            <div class="form-group">
                <label for="register-looking-for">Suche nach</label>
                <select id="register-looking-for" name="looking_for" required>
                    <option value="m">Männlich</option>
                    <option value="f">Weiblich</option>
                    <option value="b">Beides</option>
                </select>
            </div>
            <button type="submit">Registrieren</button>
        </form>
    </div>

    <script>
        function showTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.form').forEach(f => f.classList.remove('active'));
            
            document.querySelector(`.tab:nth-child(${tab === 'login' ? 1 : 2})`).classList.add('active');
            document.getElementById(`${tab}-form`).classList.add('active');
        }
    </script>
</body>
</html>
