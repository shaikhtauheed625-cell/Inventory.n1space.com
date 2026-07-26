<?php
require_once 'db.php';
include 'includes/header.php';

// Filters
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$methodFilter = $_GET['method'] ?? '';
$dateFrom = $_GET['from'] ?? '';
$dateTo   = $_GET['to']   ?? '';

$where = ["type = 'Sale'"];
$params = [];

if ($search) {
    $where[] = "(customer_name LIKE ? OR customer_phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($statusFilter) {
    $where[] = "payment_status = ?";
    $params[] = $statusFilter;
}
if ($methodFilter) {
    $where[] = "payment_method = ?";
    $params[] = $methodFilter;
}
if ($dateFrom) {
    $where[] = "DATE(sale_date) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where[] = "DATE(sale_date) <= ?";
    $params[] = $dateTo;
}

$whereStr = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT * FROM sales WHERE $whereStr ORDER BY sale_date DESC");
$stmt->execute($params);
$sales = $stmt->fetchAll();

$totalFiltered = array_sum(array_column($sales, 'total_amount'));
?>

<div class="d-flex justify-content-between align-items-start mb-4 animate-fade-in flex-wrap gap-3">
    <div>
        <h2 class="fw-bold mb-1"><i class="fas fa-history me-2 text-primary"></i>Sales History</h2>
        <p class="text-muted small">Track your revenue and customer transactions.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="reports.php" class="btn btn-outline-info btn-sm rounded-pill px-3"><i class="fas fa-chart-bar me-1"></i> Reports</a>
        <a href="add_sale.php" class="btn btn-primary text-nowrap"><i class="fas fa-plus me-1"></i> New Sale</a>
    </div>
</div>

<?php if (isset($_GET['status_updated'])): ?>
<div class="alert alert-success bg-success bg-opacity-10 border-success border-opacity-25 text-success animate-fade-in">
    <i class="fas fa-check-circle me-2"></i> Payment status updated successfully.
</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success bg-success bg-opacity-10 border-success border-opacity-25 text-success animate-fade-in">
    <i class="fas fa-check-circle me-2"></i> Sale record deleted and stock levels restored successfully.
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card p-3 mb-4 animate-fade-in" style="animation-delay:0.05s">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label text-muted small fw-bold mb-1">SEARCH</label>
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted"><i class="fas fa-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Customer name / phone..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label text-muted small fw-bold mb-1">STATUS</label>
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="Paid" <?php echo $statusFilter=='Paid'?'selected':''; ?>>Paid</option>
                <option value="Pending" <?php echo $statusFilter=='Pending'?'selected':''; ?>>Pending</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label text-muted small fw-bold mb-1">METHOD</label>
            <select name="method" class="form-select">
                <option value="">All Methods</option>
                <option value="Cash" <?php echo $methodFilter=='Cash'?'selected':''; ?>>Cash</option>
                <option value="UPI" <?php echo $methodFilter=='UPI'?'selected':''; ?>>UPI</option>
                <option value="Card" <?php echo $methodFilter=='Card'?'selected':''; ?>>Card</option>
                <option value="Bank Transfer" <?php echo $methodFilter=='Bank Transfer'?'selected':''; ?>>Bank Transfer</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label text-muted small fw-bold mb-1">FROM</label>
            <input type="date" name="from" class="form-control" value="<?php echo $dateFrom; ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label text-muted small fw-bold mb-1">TO</label>
            <input type="date" name="to" class="form-control" value="<?php echo $dateTo; ?>">
        </div>
        <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fas fa-filter"></i></button>
            <a href="view_sales.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
        </div>
    </form>
</div>

<!-- Results summary -->
<div class="d-flex justify-content-between align-items-center mb-3 px-1">
    <span class="text-muted small"><i class="fas fa-list me-1"></i> <?php echo count($sales); ?> records found</span>
    <span class="text-primary fw-bold small">Total: ₹<?php echo number_format($totalFiltered, 2); ?></span>
</div>

<div class="card overflow-hidden animate-fade-in" style="animation-delay: 0.1s">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Sale ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Amount</th>
                    <th>Earnings</th>
                    <th>Status</th>
                    <th>Method</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $sale): 
                    $iStmt = $pdo->prepare("SELECT si.quantity, 
                                                   COALESCE(p.name, si.manual_product_name) as product_name, 
                                                   COALESCE(v.variation_name, si.manual_variation_name) as variation_name 
                                            FROM sale_items si 
                                            LEFT JOIN variations v ON si.variation_id = v.id 
                                            LEFT JOIN products p ON v.product_id = p.id 
                                            WHERE si.sale_id = ?");
                    $iStmt->execute([$sale['id']]);
                    $items = $iStmt->fetchAll();
                    $itemCount = array_sum(array_column($items, 'quantity'));
                ?>
                <tr>
                    <td class="ps-4 text-muted small">#<?php echo str_pad($sale['id'], 5, '0', STR_PAD_LEFT); ?></td>
                    <td>
                        <div class="fw-bold text-light"><?php echo htmlspecialchars($sale['customer_name']); ?></div>
                        <?php if ($sale['customer_phone']): ?>
                        <small class="text-muted"><i class="fas fa-phone-alt me-1" style="font-size:0.6rem"></i><?php echo htmlspecialchars($sale['customer_phone']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?php echo date('M d, Y H:i', strtotime($sale['sale_date'])); ?></td>
                    <td>
                        <span class="badge bg-secondary bg-opacity-10 text-muted border border-secondary border-opacity-25"><?php echo $itemCount; ?> items</span>
                        <div class="small text-muted" style="font-size: 0.72rem; margin-top: 3px;">
                            <?php foreach (array_slice($items, 0, 2) as $item): ?>
                                <div><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['product_name']); ?></div>
                            <?php endforeach; ?>
                            <?php if (count($items) > 2): ?><div class="fst-italic">+<?php echo count($items)-2; ?> more...</div><?php endif; ?>
                        </div>
                    </td>
                    <td class="text-primary fw-bold">₹<?php echo number_format($sale['total_amount'], 2); ?></td>
                    <td class="text-success fw-bold">₹<?php echo number_format($sale['total_earnings'], 2); ?></td>
                    <td>
                        <?php if ($sale['payment_status'] === 'Paid'): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Paid</span>
                        <?php else: ?>
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?php echo htmlspecialchars($sale['payment_method'] ?: 'Cash'); ?></td>
                    <td class="text-end pe-4">
                        <div class="d-flex justify-content-end gap-1 flex-wrap">
                            <a href="invoice.php?id=<?php echo $sale['id']; ?>" class="btn btn-outline-info btn-sm rounded-pill px-3">
                                <i class="fas fa-file-invoice me-1"></i> Invoice
                            </a>
                            <?php if ($sale['payment_status'] === 'Pending'): ?>
                            <a href="mark_paid.php?id=<?php echo $sale['id']; ?>&status=Paid" class="btn btn-outline-success btn-sm rounded-pill px-2" title="Mark as Paid">
                                <i class="fas fa-check"></i>
                            </a>
                            <?php else: ?>
                            <a href="mark_paid.php?id=<?php echo $sale['id']; ?>&status=Pending" class="btn btn-outline-warning btn-sm rounded-pill px-2" title="Mark as Pending">
                                <i class="fas fa-undo"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('can_delete_sales')): ?>
                            <a href="delete_sale.php?id=<?php echo $sale['id']; ?>" 
                               class="btn btn-outline-danger btn-sm rounded-pill px-2"
                               onclick="return confirm('Delete this sale? Stock will be restored.')">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($sales)): ?>
                <tr><td colspan="9" class="text-center py-5 text-muted">No sales found matching your filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
