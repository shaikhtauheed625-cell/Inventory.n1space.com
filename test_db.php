<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=n1_shopping', 'root', '');
    $stmt = $pdo->query("SHOW DATABASES");
    $dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Databases: " . implode(", ", $dbs);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
