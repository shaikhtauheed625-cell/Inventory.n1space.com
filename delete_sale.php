<?php
require_once 'db.php';
session_start();

if (!hasPermission('can_delete_sales')) {
    die("Access Denied.");
}

if (!isset($_GET['id'])) {
    die("Sale ID not specified.");
}

$saleId = $_GET['id'];

try {
    $pdo->beginTransaction();

    // 1. Fetch items to restore stock
    $stmt = $pdo->prepare("SELECT variation_id, quantity FROM sale_items WHERE sale_id = ? AND variation_id IS NOT NULL");
    $stmt->execute([$saleId]);
    $items = $stmt->fetchAll();

    // 2. Restore stock
    $uStmt = $pdo->prepare("UPDATE variations SET stock_quantity = stock_quantity + ? WHERE id = ?");
    foreach ($items as $item) {
        $uStmt->execute([$item['quantity'], $item['variation_id']]);
    }

    // 3. Delete sale (cascades to sale_items)
    $dStmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
    $dStmt->execute([$saleId]);

    $pdo->commit();
    header("Location: view_sales.php?deleted=1");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    die("Error deleting sale: " . $e->getMessage());
}
?>
