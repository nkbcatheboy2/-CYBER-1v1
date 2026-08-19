<?php
// config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'gaming_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// User fetch function
function get_user($pdo) {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Alias to prevent any naming conflicts
function get_logged_in_user($pdo) {
    return get_user($pdo);
}
?>