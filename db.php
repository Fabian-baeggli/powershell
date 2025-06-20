<?php
/**
 * Datenbankverbindung und Konfiguration
 */

// Lade Umgebungsvariablen oder setze Standardwerte
$config = [
    'host' => $_ENV['MYSQL_HOST'] ?? 'db',
    'dbname' => $_ENV['MYSQL_DATABASE'] ?? 'dating_app',
    'username' => $_ENV['MYSQL_USER'] ?? 'pi',
    'password' => $_ENV['MYSQL_PASSWORD'] ?? 'pi',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        PDO::ATTR_PERSISTENT => true, // Persistente Verbindungen für bessere Performance
        PDO::ATTR_TIMEOUT => 5, // Timeout nach 5 Sekunden
    ]
];

// DSN (Data Source Name) erstellen
$dsn = sprintf(
    "mysql:host=%s;dbname=%s;charset=%s",
    $config['host'],
    $config['dbname'],
    $config['charset']
);

try {
    // Verbindung zur Datenbank herstellen
    $pdo = new PDO('mysql:host=localhost;dbname=dating_app', 'root', '');
    
    // Verbindung testen
    $pdo->query('SELECT 1');
    
} catch (PDOException $e) {
    // Fehler loggen
    error_log(sprintf(
        "Datenbankfehler [%s]: %s in %s:%d",
        $e->getCode(),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));
    
    // Benutzerfreundliche Fehlermeldung
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        die("Verbindungsfehler: Falsche Zugangsdaten");
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        die("Verbindungsfehler: Datenbank existiert nicht");
    } elseif (strpos($e->getMessage(), 'Could not connect') !== false) {
        die("Verbindungsfehler: Datenbank-Server nicht erreichbar");
    } else {
        die("Verbindungsfehler: Bitte versuche es später erneut");
    }
}

/**
 * Hilfsfunktionen für Datenbankoperationen
 */

/**
 * Führt eine SQL-Abfrage aus und gibt das Ergebnis zurück
 * 
 * @param string $sql SQL-Abfrage
 * @param array $params Parameter für prepared statements
 * @return PDOStatement|false
 */
function db_query($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Holt eine einzelne Zeile aus der Datenbank
 * 
 * @param string $sql SQL-Abfrage
 * @param array $params Parameter für prepared statements
 * @return array|false
 */
function db_fetch($sql, $params = []) {
    $stmt = db_query($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

/**
 * Holt alle Zeilen aus der Datenbank
 * 
 * @param string $sql SQL-Abfrage
 * @param array $params Parameter für prepared statements
 * @return array|false
 */
function db_fetch_all($sql, $params = []) {
    $stmt = db_query($sql, $params);
    return $stmt ? $stmt->fetchAll() : false;
}

/**
 * Fügt einen neuen Datensatz ein
 * 
 * @param string $table Tabellenname
 * @param array $data Daten als assoziatives Array
 * @return int|false ID des neuen Datensatzes oder false bei Fehler
 */
function db_insert($table, $data) {
    global $pdo;
    try {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Insert Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Aktualisiert einen Datensatz
 * 
 * @param string $table Tabellenname
 * @param array $data Daten als assoziatives Array
 * @param string $where WHERE-Bedingung
 * @param array $where_params Parameter für WHERE-Bedingung
 * @return int|false Anzahl der betroffenen Zeilen oder false bei Fehler
 */
function db_update($table, $data, $where, $where_params = []) {
    global $pdo;
    try {
        $fields = array_map(function($field) {
            return "$field = ?";
        }, array_keys($data));
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $fields),
            $where
        );
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge(array_values($data), $where_params));
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Update Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Löscht einen Datensatz
 * 
 * @param string $table Tabellenname
 * @param string $where WHERE-Bedingung
 * @param array $params Parameter für WHERE-Bedingung
 * @return int|false Anzahl der betroffenen Zeilen oder false bei Fehler
 */
function db_delete($table, $where, $params = []) {
    global $pdo;
    try {
        $sql = sprintf("DELETE FROM %s WHERE %s", $table, $where);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Delete Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Beginnt eine Transaktion
 * 
 * @return bool
 */
function db_begin_transaction() {
    global $pdo;
    return $pdo->beginTransaction();
}

/**
 * Führt ein Commit für eine Transaktion aus
 * 
 * @return bool
 */
function db_commit() {
    global $pdo;
    return $pdo->commit();
}

/**
 * Führt ein Rollback für eine Transaktion aus
 * 
 * @return bool
 */
function db_rollback() {
    global $pdo;
    return $pdo->rollBack();
}

// Hilfsfunktionen für die Datenbank
function getUserById($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function getMatches($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.* FROM users u
        WHERE u.id IN (
            SELECT liked_user_id FROM likes WHERE user_id = ? 
            INTERSECT
            SELECT user_id FROM likes WHERE liked_user_id = ?
        )
    ");
    $stmt->execute([$userId, $userId]);
    return $stmt->fetchAll();
}

function getPendingLikes($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.* FROM likes l
        JOIN users u ON u.id = l.user_id
        WHERE l.liked_user_id = ? AND l.user_id NOT IN (
            SELECT liked_user_id FROM likes WHERE user_id = ?
        )
    ");
    $stmt->execute([$userId, $userId]);
    return $stmt->fetchAll();
}

function getNextUser($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE id != ? 
        AND id NOT IN (
            SELECT liked_user_id FROM likes WHERE user_id = ?
            UNION
            SELECT disliked_user_id FROM dislikes WHERE user_id = ?
        )
        ORDER BY RAND() 
        LIMIT 1
    ");
    $stmt->execute([$userId, $userId, $userId]);
    return $stmt->fetch();
}

function addLike($likerId, $likedId) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        // Like hinzufügen
        $stmt = $pdo->prepare("
            INSERT INTO likes (liker_id, liked_id, created_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$likerId, $likedId]);
        
        // Prüfen ob es ein Match ist
        $stmt = $pdo->prepare("
            SELECT * FROM likes 
            WHERE liker_id = ? AND liked_id = ?
        ");
        $stmt->execute([$likedId, $likerId]);
        $isMatch = $stmt->fetch() ? true : false;
        
        // Wenn es ein Match ist, Nachrichten erstellen
        if ($isMatch) {
            $stmt = $pdo->prepare("
                INSERT INTO messages (sender_id, receiver_id, message, created_at)
                VALUES (?, ?, 'Es ist ein Match!', NOW())
            ");
            $stmt->execute([$likerId, $likedId]);
            $stmt->execute([$likedId, $likerId]);
        }
        
        $pdo->commit();
        return ['success' => true, 'match' => $isMatch];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function addDislike($dislikerId, $dislikedId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO dislikes (disliker_id, disliked_id, created_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$dislikerId, $dislikedId]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getUnreadMessages($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM messages 
        WHERE receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function markMessagesAsRead($senderId, $receiverId) {
    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$senderId, $receiverId]);
}

function getMessages($user1Id, $user2Id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?)
        OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    $stmt->execute([$user1Id, $user2Id, $user2Id, $user1Id]);
    return $stmt->fetchAll();
}

function addMessage($senderId, $receiverId, $message) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, message, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$senderId, $receiverId, $message]);
}

function updateProfileImage($userId, $imagePath) {
    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE users 
        SET profile_image = ? 
        WHERE id = ?
    ");
    $stmt->execute([$imagePath, $userId]);
}

function updateBio($userId, $bio) {
    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE users 
        SET bio = ? 
        WHERE id = ?
    ");
    $stmt->execute([$bio, $userId]);
}
?>
