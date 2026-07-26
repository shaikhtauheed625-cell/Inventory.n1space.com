<?php
require_once 'db.php';
session_start();

if (!hasPermission('can_manage_products')) {
    die("Access Denied: You do not have permission to manage products.");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Check if there are sales associated with this variation
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sale_items WHERE variation_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        die("Cannot delete variation: It has associated sales history. You should archive it or set stock to 0 instead.");
    }
    
    $stmt = $pdo->prepare("DELETE FROM variations WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: products.php?deleted=1");
exit;
