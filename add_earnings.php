<?php
require 'db.php';
try {
    $pdo->exec("ALTER TABLE sales ADD COLUMN total_earnings DECIMAL(10,2) DEFAULT 0 AFTER total_amount");
    echo "Earnings column added to sales table.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
