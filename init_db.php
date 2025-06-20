<?php
require 'db.php';

try {
    // Users Tabelle aktualisieren
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            profile_image VARCHAR(255),
            bio TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Likes Tabelle
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            liker_id INT NOT NULL,
            liked_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (liker_id) REFERENCES users(id),
            FOREIGN KEY (liked_id) REFERENCES users(id),
            UNIQUE KEY unique_like (liker_id, liked_id)
        )
    ");

    // Dislikes Tabelle
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS dislikes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            disliker_id INT NOT NULL,
            disliked_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (disliker_id) REFERENCES users(id),
            FOREIGN KEY (disliked_id) REFERENCES users(id),
            UNIQUE KEY unique_dislike (disliker_id, disliked_id)
        )
    ");

    // Messages Tabelle
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id),
            FOREIGN KEY (receiver_id) REFERENCES users(id)
        )
    ");

    echo "Datenbank erfolgreich initialisiert!";
} catch (PDOException $e) {
    die("Datenbankfehler: " . $e->getMessage());
}
