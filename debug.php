<?php
// debug.php - Zeigt das PHP-Error-Log im Browser an (nur für DEV!)
$logfile = '/var/log/php_errors.log'; // Passe den Pfad ggf. an
header('Content-Type: text/html; charset=utf-8');
echo "<h2>PHP Error Log</h2>";
if (file_exists($logfile)) {
    echo "<pre style='background:#222;color:#0f0;padding:1em;max-width:900px;overflow:auto;'>";
    echo htmlspecialchars(file_get_contents($logfile));
    echo "</pre>";
} else {
    echo "<b>Logfile nicht gefunden:</b> $logfile";
}
?> 