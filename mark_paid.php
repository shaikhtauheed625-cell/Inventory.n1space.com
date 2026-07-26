<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: view_sales.php");
    exit;
}

$saleId = (int)$_GET['id'];
$status = $_GET['status'] ?? 'Paid';

try {
    $stmt = $pdo->prepare("UPDATE sales SET payment_status = ? WHERE id = ?");
    $stmt->execute([$status, $saleId]);
    header("Location: view_sales.php?status_updated=1");
    exit;
} catch (Exception $e) {
    $error = urlencode($e->getMessage());
    header("Location: view_sales.php?error=$error");
    exit;
}
