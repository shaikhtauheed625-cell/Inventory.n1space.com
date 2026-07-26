<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: quotations.php");
    exit;
}

$quoteId = (int)$_GET['id'];

try {
    $pdo->beginTransaction();

    // Verify it's a quote
    $stmt = $pdo->prepare("SELECT type FROM sales WHERE id = ?");
    $stmt->execute([$quoteId]);
    $type = $stmt->fetchColumn();

    if ($type !== 'Quotation') {
        throw new Exception("This is not a valid quotation.");
    }

    // Get all items to check stock and calculate earnings
    $iStmt = $pdo->prepare("SELECT variation_id, quantity, unit_price FROM sale_items WHERE sale_id = ? AND variation_id IS NOT NULL");
    $iStmt->execute([$quoteId]);
    $items = $iStmt->fetchAll();

    $totalEarnings = 0;
    
    // Check stock first
    foreach ($items as $item) {
        $pStmt = $pdo->prepare("SELECT stock_quantity, cost_price, name FROM variations v JOIN products p ON v.product_id = p.id WHERE v.id = ? FOR UPDATE");
        $pStmt->execute([$item['variation_id']]);
        $var = $pStmt->fetch();
        
        if ($var['stock_quantity'] < $item['quantity']) {
            throw new Exception("Not enough stock for {$var['name']}. Available: {$var['stock_quantity']}.");
        }
        
        $totalEarnings += ($item['unit_price'] - $var['cost_price']) * $item['quantity'];
    }

    // Deduct stock
    $uStmt = $pdo->prepare("UPDATE variations SET stock_quantity = stock_quantity - ? WHERE id = ?");
    foreach ($items as $item) {
        $uStmt->execute([$item['quantity'], $item['variation_id']]);
    }

    // Update sales record
    $updStmt = $pdo->prepare("UPDATE sales SET type = 'Sale', payment_status = 'Pending', sale_date = CURRENT_TIMESTAMP, total_earnings = ? WHERE id = ?");
    $updStmt->execute([$totalEarnings, $quoteId]);

    $pdo->commit();
    header("Location: quotations.php?converted=1");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    $error = urlencode($e->getMessage());
    header("Location: quotations.php?error=$error");
    exit;
}
