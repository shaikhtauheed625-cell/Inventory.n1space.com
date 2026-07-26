<?php
require_once 'db.php';
include 'includes/header.php';

$query = "SELECT p.name as product_name, p.id as product_id, v.* 
          FROM variations v 
          JOIN products p ON v.product_id = p.id 
          ORDER BY p.name ASC, v.variation_name ASC";
$stmt = $pdo->query($query);
$variations = $stmt->fetchAll();

// Compute stats
$totalStock = array_sum(array_column($variations, 'stock_quantity'));
$lowStockCount = count(array_filter($variations, fn($v) => $v['stock_quantity'] <= $v['stock_limit']));
$outOfStock = count(array_filter($variations, fn($v) => $v['stock_quantity'] <= 0));
?>

<div class="d-flex justify-content-between align-items-center mb-4 animate-fade-in flex-wrap gap-3">
    <div>
        <h2 class="fw-bold mb-1"><i class="fas fa-boxes me-2 text-primary"></i>Product Inventory</h2>
        <p class="text-muted small">Manage your stock variations and pricing.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <div class="input-group" style="width: 260px;">
            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted"><i class="fas fa-search"></i></span>
            <input type="text" id="productSearch" class="form-control" placeholder="Search products...">
        </div>
        <a href="stock_in.php" class="btn btn-outline-info text-nowrap"><i class="fas fa-truck me-1"></i> Stock In</a>
        <?php if (hasPermission('can_manage_products')): ?>
        <a href="add_product.php" class="btn btn-primary text-nowrap"><i class="fas fa-plus me-1"></i> Add Product</a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success bg-success bg-opacity-10 text-success border-success border-opacity-25 animate-fade-in">Product added successfully!</div>
<?php endif; ?>
<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-info bg-info bg-opacity-10 text-info border-info border-opacity-25 animate-fade-in">Variation updated successfully!</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-warning bg-warning bg-opacity-10 text-warning border-warning border-opacity-25 animate-fade-in">Variation deleted successfully!</div>
<?php endif; ?>

<!-- Quick Stats -->
<div class="row g-3 mb-4 animate-fade-in" style="animation-delay:0.05s">
    <div class="col-4">
        <div class="card p-3 text-center">
            <div class="fw-bold fs-4 text-primary"><?php echo count($variations); ?></div>
            <div class="small text-muted">Total SKUs</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card p-3 text-center" style="border-color:rgba(245,158,11,0.3)!important">
            <div class="fw-bold fs-4 text-warning"><?php echo $lowStockCount; ?></div>
            <div class="small text-muted">Low Stock</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card p-3 text-center" style="border-color:rgba(239,68,68,0.3)!important">
            <div class="fw-bold fs-4 text-danger"><?php echo $outOfStock; ?></div>
            <div class="small text-muted">Out of Stock</div>
        </div>
    </div>
</div>

<div class="card overflow-hidden animate-fade-in" style="animation-delay: 0.1s">
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="productsTable">
            <thead>
                <tr>
                    <th class="ps-4">Product / Variation</th>
                    <th>SKU</th>
                    <?php if (hasPermission('can_view_purchase_price')): ?>
                    <th>Cost Price</th>
                    <?php endif; ?>
                    <th>Sell Price</th>
                    <?php if (hasPermission('can_view_purchase_price')): ?>
                    <th>Margin</th>
                    <?php endif; ?>
                    <th>Stock</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($variations as $v): 
                    $margin = ($v['price'] > 0 && $v['cost_price'] > 0) 
                        ? (($v['price'] - $v['cost_price']) / $v['price']) * 100 
                        : null;
                ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-light"><?php echo htmlspecialchars($v['product_name']); ?></div>
                        <small class="text-muted"><?php echo htmlspecialchars($v['variation_name']); ?></small>
                    </td>
                    <td><code class="text-info bg-info bg-opacity-10 px-2 py-1 rounded small"><?php echo htmlspecialchars($v['sku'] ?: 'N/A'); ?></code></td>
                    <?php if (hasPermission('can_view_purchase_price')): ?>
                    <td class="text-muted small"><?php echo $v['cost_price'] > 0 ? '₹'.number_format($v['cost_price'], 2) : '<span class="text-secondary">—</span>'; ?></td>
                    <?php endif; ?>
                    
                    <td class="fw-semibold text-primary">₹<?php echo number_format($v['price'], 2); ?></td>
                    
                    <?php if (hasPermission('can_view_purchase_price')): ?>
                    <td>
                        <?php if ($margin !== null): ?>
                        <span class="badge <?php echo $margin >= 20 ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-warning bg-opacity-10 text-warning border border-warning'; ?> border-opacity-25">
                            <?php echo number_format($margin, 1); ?>%
                        </span>
                        <?php else: ?>
                        <span class="text-secondary small">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td class="<?php echo $v['stock_quantity'] <= $v['stock_limit'] ? 'text-danger fw-bold' : ''; ?>">
                        <?php echo $v['stock_quantity']; ?>
                        <small class="text-muted">/<?php echo $v['stock_limit']; ?></small>
                    </td>
                    <td>
                        <?php if ($v['stock_quantity'] <= 0): ?>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Out of Stock</span>
                        <?php elseif ($v['stock_quantity'] <= $v['stock_limit']): ?>
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">Low Stock</span>
                        <?php else: ?>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">In Stock</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-flex justify-content-end gap-1">
                            <?php if (hasPermission('can_manage_stock')): ?>
                            <a href="stock_in.php" class="btn btn-outline-success btn-sm px-2" title="Restock">
                                <i class="fas fa-plus"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('can_manage_products')): ?>
                            <a href="add_variant.php?product_id=<?php echo $v['product_id']; ?>" class="btn btn-outline-primary btn-sm px-2" title="Add Variant">
                                <i class="fas fa-layer-group"></i>
                            </a>
                            <a href="edit_product.php?id=<?php echo $v['id']; ?>" class="btn btn-outline-info btn-sm px-2" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete_variation.php?id=<?php echo $v['id']; ?>" class="btn btn-outline-danger btn-sm px-2" title="Delete"
                               onclick="return confirm('Delete this variation?')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($variations)): ?>
                <tr><td colspan="8" class="text-center py-5 text-muted">No products found. Add your first product to get started!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('productSearch').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    document.querySelectorAll('#productsTable tbody tr').forEach(row => {
        if (row.querySelector('td')?.colSpan > 1) return;
        row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
