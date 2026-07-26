<?php
require_once 'db.php';
session_start();

if (!hasPermission('can_manage_stock')) {
    die("Access Denied: You do not have permission to manage stock.");
}
include 'includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock'])) {
    $variationId  = (int)$_POST['variation_id'];
    $qty          = (int)$_POST['quantity'];
    $costPerUnit  = (float)($_POST['cost_per_unit'] ?? 0);
    $supplier     = trim($_POST['supplier'] ?? '');
    $notes        = trim($_POST['notes'] ?? '');

    if ($variationId > 0 && $qty > 0) {
        $pdo->beginTransaction();
        // Log stock in
        $stmt = $pdo->prepare("INSERT INTO stock_in (variation_id, quantity, cost_per_unit, supplier, notes) VALUES (?,?,?,?,?)");
        $stmt->execute([$variationId, $qty, $costPerUnit, $supplier, $notes]);
        // Update stock
        $pdo->prepare("UPDATE variations SET stock_quantity = stock_quantity + ? WHERE id = ?")->execute([$qty, $variationId]);
        // Update cost_price if provided
        if ($costPerUnit > 0) {
            $pdo->prepare("UPDATE variations SET cost_price = ? WHERE id = ?")->execute([$costPerUnit, $variationId]);
        }
        $pdo->commit();
        header("Location: stock_in.php?success=1");
        exit;
    } else {
        $error = "Please select a variation and enter a valid quantity.";
    }
}

// Fetch all variations
$variations = $pdo->query("
    SELECT v.id, p.name as product_name, v.variation_name, v.stock_quantity, v.cost_price, v.price, v.sku
    FROM variations v
    JOIN products p ON v.product_id = p.id
    ORDER BY p.name ASC, v.variation_name ASC
")->fetchAll();

// Fetch recent stock-in logs
$logs = $pdo->query("
    SELECT si.*, v.variation_name, p.name as product_name, v.sku
    FROM stock_in si
    JOIN variations v ON si.variation_id = v.id
    JOIN products p ON v.product_id = p.id
    ORDER BY si.created_at DESC
    LIMIT 30
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-start mb-4 animate-fade-in flex-wrap gap-3">
    <div>
        <h2 class="fw-bold mb-1"><i class="fas fa-truck me-2 text-primary"></i>Stock In / Purchase Orders</h2>
        <p class="text-muted small">Add stock to your inventory and log restocking history.</p>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success bg-success bg-opacity-10 text-success border-success border-opacity-25 animate-fade-in">
    <i class="fas fa-check-circle me-2"></i> Stock updated successfully! Inventory levels have been increased.
</div>
<?php endif; ?>
<?php if (isset($error)): ?>
<div class="alert alert-danger bg-danger bg-opacity-10 text-danger border-danger border-opacity-25"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row g-4 mb-5">
    <!-- Restock Form -->
    <div class="col-lg-5 animate-fade-in" style="animation-delay:0.1s">
        <div class="card p-4 h-100">
            <h5 class="fw-bold mb-4"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Stock</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold text-uppercase">Product Variation *</label>
                    <select name="variation_id" class="form-select" required id="variationSelect">
                        <option value="">Select a variation...</option>
                        <?php foreach ($variations as $v): ?>
                        <option value="<?php echo $v['id']; ?>" 
                                data-stock="<?php echo $v['stock_quantity']; ?>"
                                data-cost="<?php echo $v['cost_price']; ?>"
                                data-price="<?php echo $v['price']; ?>">
                            <?php echo htmlspecialchars($v['product_name'] . ' — ' . $v['variation_name']); ?>
                            (Stock: <?php echo $v['stock_quantity']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Stock info preview -->
                <div id="stockPreview" class="p-3 rounded-3 mb-3 d-none" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07)">
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="small text-muted">Current Stock</div>
                            <div class="fw-bold text-primary" id="previewStock">—</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Sell Price</div>
                            <div class="fw-bold text-success" id="previewPrice">—</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Cost Price</div>
                            <div class="fw-bold text-muted" id="previewCost">—</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold text-uppercase">Quantity to Add *</label>
                        <input type="number" name="quantity" class="form-control" min="1" placeholder="e.g. 50" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold text-uppercase">Cost per Unit (₹)</label>
                        <input type="number" step="0.01" name="cost_per_unit" class="form-control" placeholder="Purchase price">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold text-uppercase">Supplier / Source</label>
                    <input type="text" name="supplier" class="form-control" placeholder="e.g. ABC Wholesalers">
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold text-uppercase">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
                </div>

                <button type="submit" name="restock" class="btn btn-primary w-100 btn-lg">
                    <i class="fas fa-plus-circle me-2"></i> Add to Inventory
                </button>
            </form>
        </div>
    </div>

    <!-- Low Stock Alert Panel -->
    <div class="col-lg-7 animate-fade-in" style="animation-delay:0.2s">
        <div class="card overflow-hidden h-100">
            <div class="p-4 border-bottom border-secondary border-opacity-10">
                <h5 class="fw-bold mb-0"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Items Needing Restock</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Product / Variation</th>
                            <th>SKU</th>
                            <th>Stock</th>
                            <th>Sell Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $lowStock = array_filter($variations, fn($v) => $v['stock_quantity'] <= 10);
                        usort($lowStock, fn($a,$b) => $a['stock_quantity'] - $b['stock_quantity']);
                        foreach (array_slice($lowStock, 0, 12) as $v): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-light"><?php echo htmlspecialchars($v['product_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($v['variation_name']); ?></small>
                            </td>
                            <td><code class="text-info bg-info bg-opacity-10 px-2 py-1 rounded small"><?php echo $v['sku'] ?: 'N/A'; ?></code></td>
                            <td class="<?php echo $v['stock_quantity'] <= 0 ? 'text-danger' : 'text-warning'; ?> fw-bold"><?php echo $v['stock_quantity']; ?></td>
                            <td class="text-muted small">₹<?php echo number_format($v['price'], 2); ?></td>
                            <td>
                                <?php if ($v['stock_quantity'] <= 0): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Out of Stock</span>
                                <?php else: ?>
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">Low Stock</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($lowStock)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">All items are well-stocked! 🎉</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Restocking History -->
<div class="card overflow-hidden animate-fade-in" style="animation-delay:0.3s">
    <div class="p-4 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0"><i class="fas fa-history me-2 text-info"></i>Restocking History</h5>
        <span class="badge bg-secondary bg-opacity-20 text-muted"><?php echo count($logs); ?> entries</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Date</th>
                    <th>Product</th>
                    <th>Qty Added</th>
                    <th>Cost/Unit</th>
                    <th>Total Cost</th>
                    <th>Supplier</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="ps-4 small text-muted"><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                    <td>
                        <div class="fw-bold text-light small"><?php echo htmlspecialchars($log['product_name']); ?></div>
                        <small class="text-muted"><?php echo htmlspecialchars($log['variation_name']); ?></small>
                    </td>
                    <td><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">+<?php echo $log['quantity']; ?></span></td>
                    <td class="text-muted small"><?php echo $log['cost_per_unit'] ? '₹'.number_format($log['cost_per_unit'],2) : '—'; ?></td>
                    <td class="text-primary fw-semibold small"><?php echo $log['cost_per_unit'] ? '₹'.number_format($log['cost_per_unit']*$log['quantity'],2) : '—'; ?></td>
                    <td class="small text-muted"><?php echo htmlspecialchars($log['supplier'] ?: '—'); ?></td>
                    <td class="small text-muted"><?php echo htmlspecialchars($log['notes'] ?: '—'); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No restocking history yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
/* Dark theme select2 overrides */
.select2-container--default .select2-selection--single {
    background-color: #212529;
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 0.375rem;
    height: calc(3.5rem + 2px);
    display: flex;
    align-items: center;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #f8f9fa;
    line-height: normal;
    padding-left: 0.75rem;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 100%;
    right: 10px;
}
.select2-dropdown {
    background-color: #2b3035;
    border: 1px solid rgba(255,255,255,0.15);
}
.select2-search--dropdown .select2-search__field {
    background-color: #212529;
    color: white;
    border: 1px solid rgba(255,255,255,0.15);
}
.select2-container--default .select2-results__option--selected {
    background-color: rgba(13, 110, 253, 0.25);
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: #0d6efd;
    color: white;
}
</style>
<script>
$(document).ready(function() {
    $('#variationSelect').select2({
        placeholder: "Search for a product or variation...",
        allowClear: true,
        width: '100%'
    });
    
    // Update preview on change
    $('#variationSelect').on('change', function() {
        const opt = $(this).find('option:selected');
        const preview = document.getElementById('stockPreview');
        if (this.value) {
            preview.classList.remove('d-none');
            document.getElementById('previewStock').textContent = opt.data('stock');
            document.getElementById('previewPrice').textContent = '₹' + parseFloat(opt.data('price')).toFixed(2);
            document.getElementById('previewCost').textContent = opt.data('cost') > 0 ? '₹' + parseFloat(opt.data('cost')).toFixed(2) : 'Not set';
        } else {
            preview.classList.add('d-none');
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
