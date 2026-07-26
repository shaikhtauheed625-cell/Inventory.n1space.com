<?php
require_once 'db.php';
include 'includes/header.php';

// Filters
$search = trim($_GET['search'] ?? '');
$dateFrom = $_GET['from'] ?? '';
$dateTo   = $_GET['to']   ?? '';

$where = ["type = 'Quotation'"];
$params = [];

if ($search) {
    $where[] = "(customer_name LIKE ? OR customer_phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
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
$quotations = $stmt->fetchAll();

$totalFiltered = array_sum(array_column($quotations, 'total_amount'));
?>

<div class="d-flex justify-content-between align-items-start mb-4 animate-fade-in flex-wrap gap-3">
    <div>
        <h2 class="fw-bold mb-1"><i class="fas fa-file-invoice-dollar me-2 text-info"></i>Quotations</h2>
        <p class="text-muted small">Manage quotes and estimates for your customers.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="add_quotation.php" class="btn btn-info text-nowrap text-dark fw-bold"><i class="fas fa-plus me-1"></i> Create Quotation</a>
    </div>
</div>

<?php if (isset($_GET['converted'])): ?>
<div class="alert alert-success bg-success bg-opacity-10 border-success border-opacity-25 text-success animate-fade-in">
    <i class="fas fa-check-circle me-2"></i> Quotation successfully converted to a Sale! Stock has been deducted.
</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-info bg-info bg-opacity-10 border-info border-opacity-25 text-info animate-fade-in">
    <i class="fas fa-info-circle me-2"></i> Quotation deleted successfully.
</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger animate-fade-in">
    <i class="fas fa-exclamation-triangle me-2"></i> Error: <?php echo htmlspecialchars($_GET['error']); ?>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card p-3 mb-4 animate-fade-in" style="animation-delay:0.05s">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label text-muted small fw-bold mb-1">SEARCH</label>
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted"><i class="fas fa-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Customer name / phone..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted small fw-bold mb-1">FROM</label>
            <input type="date" name="from" class="form-control" value="<?php echo $dateFrom; ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted small fw-bold mb-1">TO</label>
            <input type="date" name="to" class="form-control" value="<?php echo $dateTo; ?>">
        </div>
        <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fas fa-filter"></i></button>
            <a href="quotations.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
        </div>
    </form>
</div>

<!-- Results summary -->
<div class="d-flex justify-content-between align-items-center mb-3 px-1">
    <span class="text-muted small"><i class="fas fa-list me-1"></i> <?php echo count($quotations); ?> quotes found</span>
    <span class="text-info fw-bold small">Total Quoted: ₹<?php echo number_format($totalFiltered, 2); ?></span>
</div>

<div class="card overflow-hidden animate-fade-in" style="animation-delay: 0.1s">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Quote ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quotations as $quote): 
                    $iStmt = $pdo->prepare("SELECT si.quantity, 
                                                   COALESCE(p.name, si.manual_product_name) as product_name, 
                                                   COALESCE(v.variation_name, si.manual_variation_name) as variation_name 
                                            FROM sale_items si 
                                            LEFT JOIN variations v ON si.variation_id = v.id 
                                            LEFT JOIN products p ON v.product_id = p.id 
                                            WHERE si.sale_id = ?");
                    $iStmt->execute([$quote['id']]);
                    $items = $iStmt->fetchAll();
                    $itemCount = array_sum(array_column($items, 'quantity'));
                ?>
                <tr>
                    <td class="ps-4 text-muted small">#QT-<?php echo str_pad($quote['id'], 5, '0', STR_PAD_LEFT); ?></td>
                    <td>
                        <div class="fw-bold text-light"><?php echo htmlspecialchars($quote['customer_name']); ?></div>
                        <?php if ($quote['customer_phone']): ?>
                        <small class="text-muted"><i class="fas fa-phone-alt me-1" style="font-size:0.6rem"></i><?php echo htmlspecialchars($quote['customer_phone']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?php echo date('M d, Y H:i', strtotime($quote['sale_date'])); ?></td>
                    <td>
                        <span class="badge bg-secondary bg-opacity-10 text-muted border border-secondary border-opacity-25"><?php echo $itemCount; ?> items</span>
                        <div class="small text-muted" style="font-size: 0.72rem; margin-top: 3px;">
                            <?php foreach (array_slice($items, 0, 2) as $item): ?>
                                <div><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['product_name']); ?></div>
                            <?php endforeach; ?>
                            <?php if (count($items) > 2): ?><div class="fst-italic">+<?php echo count($items)-2; ?> more...</div><?php endif; ?>
                        </div>
                    </td>
                    <td class="text-info fw-bold">₹<?php echo number_format($quote['total_amount'], 2); ?></td>
                    <td>
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">Quotation</span>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-flex justify-content-end gap-1 flex-wrap">
                            <a href="convert_quotation.php?id=<?php echo $quote['id']; ?>" 
                               class="btn btn-outline-success btn-sm rounded-pill px-3">
                                <i class="fas fa-check-circle me-1"></i> Convert to Sale
                            </a>
                            <a href="invoice.php?id=<?php echo $quote['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                <i class="fas fa-file-invoice me-1"></i> View
                            </a>
                            <?php if (hasPermission('can_delete_sales')): ?>
                            <a href="delete_sale.php?id=<?php echo $quote['id']; ?>&redirect=quotations.php" 
                               class="btn btn-outline-danger btn-sm rounded-pill px-2">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($quotations)): ?>
                <tr><td colspan="7" class="text-center py-5 text-muted">No quotations found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
