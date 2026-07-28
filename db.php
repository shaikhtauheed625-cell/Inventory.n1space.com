<?php
// Auto-detect environment: live server vs local XAMPP
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
    // ── LIVE SERVER (inventory.n1space.com) ──
    $host = '127.0.0.1';
    $db   = 'oatgwnis_inventory';
    $user = 'oatgwnis_inventory';
    $pass = 'oatgwnis_inventory';
} else {
    // ── LOCAL XAMPP ──
    $host = '127.0.0.1';
    $db   = 'n1_shopping';
    $user = 'root';
    $pass = '';
}
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // If the database doesn't exist, we might need to handle it or the user creates it via setup.sql
     die("Database connection failed: " . $e->getMessage());
}

/**
 * Check if the currently logged in user has a specific permission.
 * Admins have all permissions automatically.
 */
function hasPermission($action) {
    global $pdo;
    
    if (!isset($_SESSION['role'])) return false;
    if ($_SESSION['role'] === 'admin') return true;
    
    static $userPerms = null;
    if ($userPerms === null && isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT permissions FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $val = $stmt->fetchColumn();
            $userPerms = $val ? json_decode($val, true) : [];
        } catch (Exception $e) {
            $userPerms = [];
        }
    }
    
    return !empty($userPerms[$action]);
}
?>
