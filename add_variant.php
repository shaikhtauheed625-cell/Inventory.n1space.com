<?php
require_once 'db.php';
session_start();

if (!hasPermission('can_manage_products')) {
    die("Access Denied: You do not have permission to manage products.");
}

if (!isset($_GET['product_id'])) {
    header("Location: products.php");
    exit;
}

$productId = (int)$_GET['product_id'];

// Fetch Product Info
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $vStmt = $pdo->prepare("INSERT INTO variations (product_id, variation_name, sku, price, cost_price, stock_quantity, stock_limit) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $successCount = 0;
        foreach ($_POST['variations'] as $v) {
            if (!empty($v['name'])) {
                $vStmt->execute([
                    $productId, 
                    $v['name'], 
                    $v['sku'] ?? '', 
                    $v['price'], 
                    $v['cost_price'] ?? 0, 
                    $v['stock'] ?? 0, 
                    $v['limit'] ?? 5
                ]);
                $successCount++;
            }
        }
        
        $pdo->commit();
        
        if ($successCount > 0) {
            header("Location: products.php?success=1");
            exit;
        } else {
            $error = "No variations were added. Please fill in the variation details.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center animate-fade-in">
    <div class="col-md-8">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0"><i class="fas fa-plus-circle me-2 text-primary"></i>Add New Variant</h2>
                <a href="products.php" class="btn btn-outline-light btn-sm">Cancel</a>
            </div>
            
            <div class="mb-4">
                <label class="form-label text-muted small uppercase">Product</label>
                <div class="h5"><?php echo htmlspecialchars($product['name']); ?></div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
                    <h5 class="fw-bold mb-0">New Variations</h5>
                    <button type="button" id="add-variation" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-plus me-1"></i> Add Another Row
                    </button>
                </div>

                <div id="variations-container">
                    <div class="variation-row mb-3 p-3 border border-secondary border-opacity-10 rounded-3 bg-dark bg-opacity-25">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Name (Size/Color)</label>
                                <input type="text" name="variations[0][name]" class="form-control" required placeholder="e.g. XL / Blue">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">SKU</label>
                                <input type="text" name="variations[0][sku]" class="form-control" placeholder="Optional">
                            </div>
                            <?php if (hasPermission('can_view_purchase_price')): ?>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Cost Price</label>
                                <input type="number" step="0.01" name="variations[0][cost_price]" class="form-control" placeholder="0.00">
                            </div>
                            <?php endif; ?>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Sell Price</label>
                                <input type="number" step="0.01" name="variations[0][price]" class="form-control" required>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small text-muted">Stock</label>
                                <input type="number" name="variations[0][stock]" class="form-control px-2" value="0">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small text-muted">Limit</label>
                                <input type="number" name="variations[0][limit]" class="form-control px-2" value="5">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger w-100 remove-var"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold">Save New Variants</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let varCount = 1;
document.getElementById('add-variation').addEventListener('click', function() {
    const container = document.getElementById('variations-container');
    const newRow = document.createElement('div');
    newRow.className = 'variation-row mb-3 p-3 border border-secondary border-opacity-10 rounded-3 bg-dark bg-opacity-25 animate-fade-in';
    newRow.innerHTML = `
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label small text-muted">Name</label>
                <input type="text" name="variations[${varCount}][name]" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">SKU</label>
                <input type="text" name="variations[${varCount}][sku]" class="form-control">
            </div>
            <?php if (hasPermission('can_view_purchase_price')): ?>
            <div class="col-md-2">
                <label class="form-label small text-muted">Cost Price</label>
                <input type="number" step="0.01" name="variations[${varCount}][cost_price]" class="form-control" placeholder="0.00">
            </div>
            <?php endif; ?>
            <div class="col-md-2">
                <label class="form-label small text-muted">Sell Price</label>
                <input type="number" step="0.01" name="variations[${varCount}][price]" class="form-control" required>
            </div>
            <div class="col-md-1">
                <label class="form-label small text-muted">Stock</label>
                <input type="number" name="variations[${varCount}][stock]" class="form-control px-2" value="0">
            </div>
            <div class="col-md-1">
                <label class="form-label small text-muted">Limit</label>
                <input type="number" name="variations[${varCount}][limit]" class="form-control px-2" value="5">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger w-100 remove-var"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
    varCount++;
});

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.remove-var');
    if (btn) {
        const row = btn.closest('.variation-row');
        if (document.querySelectorAll('.variation-row').length > 1) {
            row.remove();
        } else {
            alert('You must have at least one variation row.');
            // Optionally clear the row
            row.querySelectorAll('input').forEach(input => input.value = '');
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
