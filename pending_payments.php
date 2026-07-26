<?php
require_once 'db.php';
include 'includes/header.php';

// Handle mark as paid via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $saleId = (int)$_POST['sale_id'];
    $stmt = $pdo->prepare("UPDATE sales SET payment_status = 'Paid' WHERE id = ?");
    $stmt->execute([$saleId]);
    header("Location: pending_payments.php?success=1");
    exit;
}

// Fetch all pending sales
$pendingSales = $pdo->query("
    SELECT s.*, 
           (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) as item_count,
           DATEDIFF(CURDATE(), DATE(s.due_date)) as days_overdue
    FROM sales s 
    WHERE s.payment_status = 'Pending' AND s.type = 'Sale'
    ORDER BY s.due_date ASC, s.sale_date DESC
")->fetchAll();

$totalPending = array_sum(array_column($pendingSales, 'total_amount'));
$overdueCount = count(array_filter($pendingSales, fn($s) => $s['days_overdue'] > 0));
?>

<div class="d-flex justify-content-between align-items-start mb-4 animate-fade-in flex-wrap gap-3">
    <div>
        <h2 class="fw-bold mb-1"><i class="fas fa-clock me-2 text-warning"></i>Pending Payments</h2>
        <p class="text-muted small">Track and collect outstanding dues from customers.</p>
    </div>
    <a href="add_sale.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Sale</a>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success bg-success bg-opacity-10 text-success border-success border-opacity-25 animate-fade-in">
    <i class="fas fa-check-circle me-2"></i> Payment marked as paid successfully!
</div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row g-4 mb-5 animate-fade-in" style="animation-delay:0.1s">
    <div class="col-md-4">
        <div class="card p-4" style="border-color:rgba(245,158,11,0.3)!important;background:linear-gradient(135deg,rgba(245,158,11,0.07),rgba(239,68,68,0.05))">
            <div class="stat-card-title text-warning">Total Pending Amount</div>
            <div class="stat-card-value text-warning">₹<?php echo number_format($totalPending, 2); ?></div>
            <div class="small text-muted mt-2"><i class="fas fa-file-invoice me-1"></i> <?php echo count($pendingSales); ?> unpaid invoices</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4" style="border-color:rgba(239,68,68,0.3)!important;background:linear-gradient(135deg,rgba(239,68,68,0.07),rgba(220,38,38,0.03))">
            <div class="stat-card-title text-danger">Overdue</div>
            <div class="stat-card-value text-danger"><?php echo $overdueCount; ?></div>
            <div class="small text-muted mt-2"><i class="fas fa-exclamation-triangle me-1"></i> Past due date</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4">
            <div class="stat-card-title">Average Due Amount</div>
            <div class="stat-card-value">₹<?php echo count($pendingSales) ? number_format($totalPending / count($pendingSales), 2) : '0.00'; ?></div>
            <div class="small text-muted mt-2"><i class="fas fa-calculator me-1"></i> Per pending sale</div>
        </div>
    </div>
</div>

<!-- Pending Sales Table -->
<div class="card overflow-hidden animate-fade-in" style="animation-delay:0.2s">
    <div class="p-4 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="fas fa-list me-2 text-warning"></i>Outstanding Invoices</h5>
        <div class="input-group" style="width:280px">
            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted"><i class="fas fa-search"></i></span>
            <input type="text" id="pendingSearch" class="form-control" placeholder="Search customer...">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="pendingTable">
            <thead>
                <tr>
                    <th class="ps-4">Invoice</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Sale Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Method</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingSales as $sale): 
                    $isOverdue = $sale['days_overdue'] > 0;
                    $phone = preg_replace('/[^0-9]/', '', $sale['customer_phone'] ?? '');
                    if ($phone && strlen($phone) < 10) $phone = '91' . $phone;
                    $waMsg = urlencode("Dear " . $sale['customer_name'] . ",\n\nThis is a gentle reminder that your payment of ₹" . number_format($sale['total_amount'], 2) . " for Invoice #INV-" . str_pad($sale['id'],5,'0',STR_PAD_LEFT) . " is pending.\n\nPlease make the payment at your earliest convenience.\n\nThank you!");
                ?>
                <tr>
                    <td class="ps-4">
                        <a href="invoice.php?id=<?php echo $sale['id']; ?>" class="text-info text-decoration-none fw-bold small">
                            #INV-<?php echo str_pad($sale['id'], 5, '0', STR_PAD_LEFT); ?>
                        </a>
                    </td>
                    <td>
                        <div class="fw-bold text-light"><?php echo htmlspecialchars($sale['customer_name']); ?></div>
                        <?php if ($sale['customer_phone']): ?>
                        <small class="text-muted"><i class="fas fa-phone-alt me-1"></i><?php echo htmlspecialchars($sale['customer_phone']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="fw-bold text-warning">₹<?php echo number_format($sale['total_amount'], 2); ?></td>
                    <td class="small text-muted"><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                    <td class="small <?php echo $isOverdue ? 'text-danger fw-bold' : 'text-muted'; ?>">
                        <?php echo $sale['due_date'] ? date('M d, Y', strtotime($sale['due_date'])) : '—'; ?>
                        <?php if ($isOverdue): ?>
                        <span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25 d-block mt-1" style="font-size:0.6rem"><?php echo $sale['days_overdue']; ?>d overdue</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-warning bg-opacity-15 text-warning border border-warning border-opacity-25">Pending</span></td>
                    <td class="small text-muted"><?php echo htmlspecialchars($sale['payment_method'] ?: 'Cash'); ?></td>
                    <td class="text-end pe-4">
                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                            <?php if ($phone): ?>
                            <a href="https://wa.me/<?php echo $phone; ?>?text=<?php echo $waMsg; ?>" target="_blank"
                               class="btn btn-success btn-sm rounded-pill px-3" title="WhatsApp Reminder">
                                <i class="fab fa-whatsapp me-1"></i> Remind
                            </a>
                            <?php endif; ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="sale_id" value="<?php echo $sale['id']; ?>">
                                <button type="submit" name="mark_paid" class="btn btn-primary btn-sm rounded-pill px-3"
                                    onclick="return confirm('Mark this sale as paid?')">
                                    <i class="fas fa-check me-1"></i> Mark Paid
                                </button>
                            </form>
                            <a href="invoice.php?id=<?php echo $sale['id']; ?>" class="btn btn-outline-info btn-sm rounded-pill px-2" title="View Invoice">
                                <i class="fas fa-file-invoice"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pendingSales)): ?>
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <i class="fas fa-check-double text-success fa-3x mb-3 d-block"></i>
                        <p class="text-muted mb-0">All payments are settled! No pending dues.</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('pendingSearch').addEventListener('keyup', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#pendingTable tbody tr').forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
