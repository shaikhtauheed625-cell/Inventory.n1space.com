<?php
require_once 'db.php';
session_start();

if (!hasPermission('can_manage_products')) {
    die("Access Denied: You do not have permission to manage products.");
}

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$variationId = $_GET['id'];

// Fetch Variation and Product Info
$stmt = $pdo->prepare("SELECT v.*, p.name as product_name 
                       FROM variations v 
                       JOIN products p ON v.product_id = p.id 
                       WHERE v.id = ?");
$stmt->execute([$variationId]);
$v = $stmt->fetch();

if (!$v) {
    die("Variation not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $uStmt = $pdo->prepare("UPDATE variations SET variation_name = ?, sku = ?, price = ?, cost_price = ?, stock_quantity = ?, stock_limit = ? WHERE id = ?");
        $uStmt->execute([
            $_POST['variation_name'],
            $_POST['sku'],
            $_POST['price'],
            $_POST['cost_price'] ?? 0,
            $_POST['stock_quantity'],
            $_POST['stock_limit'],
            $variationId
        ]);
        
        header("Location: products.php?updated=1");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Variation</h2>
                <a href="products.php" class="btn btn-outline-light btn-sm">Back</a>
            </div>
            
            <div class="mb-4">
                <label class="form-label text-muted small uppercase">Product</label>
                <div class="h5"><?php echo htmlspecialchars($v['product_name']); ?></div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Variation Name</label>
                    <input type="text" name="variation_name" class="form-control" value="<?php echo htmlspecialchars($v['variation_name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">SKU</label>
                    <input type="text" name="sku" class="form-control" value="<?php echo htmlspecialchars($v['sku']); ?>">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Selling Price (₹)</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $v['price']; ?>" required>
                    </div>
                    <?php if (hasPermission('can_view_purchase_price')): ?>
                    <div class="col-md-6">
                        <label class="form-label">Cost Price (₹) <small class="text-muted">(for margin calc)</small></label>
                        <input type="number" step="0.01" name="cost_price" class="form-control" value="<?php echo $v['cost_price'] ?? 0; ?>" placeholder="0.00">
                    </div>
                    <?php endif; ?>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-control" value="<?php echo $v['stock_quantity']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Stock Limit (Alert level)</label>
                        <input type="number" name="stock_limit" class="form-control" value="<?php echo $v['stock_limit']; ?>" required>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg text-dark fw-bold">Update Variation</button>
                    <button type="button" class="btn btn-outline-danger" onclick="if(confirm('Are you sure you want to delete this variation? This cannot be undone.')) window.location.href='delete_variation.php?id=<?php echo $v['id']; ?>'">
                        <i class="fas fa-trash me-1"></i> Delete Variation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
