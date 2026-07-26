<?php
require_once 'db.php';
include 'includes/header.php';

// Permissions checks for dashboard queries
$totalSales = hasPermission('can_view_sales') ? ($pdo->query("SELECT SUM(total_amount) FROM sales WHERE type='Sale'")->fetchColumn() ?: 0) : 0;
$todaySales = hasPermission('can_view_sales') ? ($pdo->query("SELECT SUM(total_amount) FROM sales WHERE DATE(sale_date) = CURDATE() AND type='Sale'")->fetchColumn() ?: 0) : 0;
$productCount = hasPermission('can_manage_products') ? ($pdo->query("SELECT COUNT(*) FROM variations")->fetchColumn() ?: 0) : 0;
$totalProfit = (hasPermission('can_view_reports') || hasPermission('can_view_purchase_price')) ? ($pdo->query("SELECT SUM(total_earnings) FROM sales WHERE type='Sale'")->fetchColumn() ?: 0) : 0;
$inventoryValue = hasPermission('can_view_purchase_price') ? ($pdo->query("SELECT SUM(stock_quantity * cost_price) FROM variations")->fetchColumn() ?: 0) : 0;

$lowStockCount = 0;
$outOfStockCount = 0;
if (hasPermission('can_manage_stock')) {
    $lowStockCount = $pdo->query("SELECT COUNT(*) FROM variations WHERE stock_quantity <= stock_limit AND stock_quantity > 0")->fetchColumn() ?: 0;
    $outOfStockCount = $pdo->query("SELECT COUNT(*) FROM variations WHERE stock_quantity = 0")->fetchColumn() ?: 0;
}

$pendingCount = 0;
if (hasPermission('can_view_pending_dues')) {
    $pendingCount = $pdo->query("SELECT COUNT(*) FROM sales WHERE payment_status='Pending' AND type='Sale'")->fetchColumn() ?: 0;
}

// Low Stock Variations
$lowStockItems = [];
if (hasPermission('can_manage_stock')) {
    $lowStockItems = $pdo->query("SELECT p.name, v.variation_name, v.stock_quantity, v.stock_limit 
                                 FROM variations v 
                                 JOIN products p ON v.product_id = p.id 
                                 WHERE v.stock_quantity <= v.stock_limit 
                                 ORDER BY v.stock_quantity ASC LIMIT 5")->fetchAll();
}

// Recent Sales
$recentSales = [];
if (hasPermission('can_view_sales')) {
    $recentSales = $pdo->query("SELECT * FROM sales WHERE type='Sale' ORDER BY sale_date DESC LIMIT 5")->fetchAll();
}

// To-Dos
$todos = $pdo->query("SELECT * FROM todos WHERE status = 'Pending' ORDER BY sort_order ASC, due_date ASC LIMIT 4")->fetchAll();

// Chart Data: Last 7 Days Sales + Earnings
$chartLabels = [];
$chartData = [];
$chartEarnings = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime($date));
    $amount = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE DATE(sale_date) = '$date' AND type='Sale'")->fetchColumn() ?: 0;
    $earn   = $pdo->query("SELECT SUM(total_earnings) FROM sales WHERE DATE(sale_date) = '$date' AND type='Sale'")->fetchColumn() ?: 0;
    
    $chartLabels[] = $label;
    $chartData[] = (float)$amount;
    $chartEarnings[] = (float)$earn;
}
?>


<?php if ($lowStockCount > 0 || $outOfStockCount > 0 || $pendingCount > 0): ?>
<div class="row mb-4 animate-fade-in gap-3 px-3">
    <?php if ($lowStockCount > 0 && hasPermission('can_manage_stock')): ?>
    <div class="col-auto card bg-warning bg-opacity-10 border border-warning border-opacity-50 text-warning px-4 py-2 rounded-pill d-flex flex-row align-items-center shadow-sm">
        <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
        <span class="fw-semibold"><?php echo $lowStockCount; ?> Products Low Stock</span>
    </div>
    <?php endif; ?>

    <?php if ($outOfStockCount > 0 && hasPermission('can_manage_stock')): ?>
    <div class="col-auto card bg-danger bg-opacity-10 border border-danger border-opacity-50 text-danger px-4 py-2 rounded-pill d-flex flex-row align-items-center shadow-sm">
        <i class="fas fa-times-circle me-2 fs-5"></i>
        <span class="fw-semibold"><?php echo $outOfStockCount; ?> Products Out of Stock</span>
    </div>
    <?php endif; ?>

    <?php if ($pendingCount > 0 && hasPermission('can_view_pending_dues')): ?>
    <div class="col-auto card bg-info bg-opacity-10 border border-info border-opacity-50 text-info px-4 py-2 rounded-pill d-flex flex-row align-items-center shadow-sm">
        <i class="fas fa-clock me-2 fs-5"></i>
        <span class="fw-semibold"><?php echo $pendingCount; ?> Pending Payments</span>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="row mb-4 animate-fade-in">
    <div class="col-12">
        <h2 class="fw-bold mb-1">Inventory Overview</h2>
        <p class="text-muted small">Welcome back! Here's what's happening with your store today.</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-5 animate-fade-in" style="animation-delay: 0.1s">
    
    <!-- Card 1: Today's Sales -->
    <?php if (hasPermission('can_view_sales')): ?>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card p-3 h-100 border-0 shadow-sm" style="background: linear-gradient(145deg, #1E293B, #111827);">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="p-2 bg-primary bg-opacity-10 rounded-3">
                    <i class="fas fa-shopping-cart text-primary fs-5"></i>
                </div>
                <span class="badge bg-success bg-opacity-10 text-success rounded-pill fw-medium">+12% <i class="fas fa-arrow-up ms-1"></i></span>
            </div>
            <p class="text-muted small fw-medium mb-1">Today's Sales</p>
            <h4 class="fw-bold mb-0 text-white">₹<?php echo number_format($todaySales); ?></h4>
        </div>
    </div>
    <?php endif; ?>

    <!-- Card 2: Total Profit -->
    <?php if (hasPermission('can_view_reports') || hasPermission('can_view_purchase_price')): ?>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card p-3 h-100 border-0 shadow-sm" style="background: linear-gradient(145deg, #1E293B, #111827);">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="p-2 bg-success bg-opacity-10 rounded-3">
                    <i class="fas fa-chart-line text-success fs-5"></i>
                </div>
                <span class="badge bg-success bg-opacity-10 text-success rounded-pill fw-medium">+5% <i class="fas fa-arrow-up ms-1"></i></span>
            </div>
            <p class="text-muted small fw-medium mb-1">Total Profit</p>
            <h4 class="fw-bold mb-0 text-success">₹<?php echo number_format($totalProfit); ?></h4>
        </div>
    </div>
    <?php endif; ?>

    <!-- Card 3: Inventory Value -->
    <?php if (hasPermission('can_view_purchase_price')): ?>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card p-3 h-100 border-0 shadow-sm" style="background: linear-gradient(145deg, #1E293B, #111827);">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="p-2 bg-info bg-opacity-10 rounded-3">
                    <i class="fas fa-boxes text-info fs-5"></i>
                </div>
            </div>
            <p class="text-muted small fw-medium mb-1">Inventory Value</p>
            <h4 class="fw-bold mb-0 text-white">₹<?php echo number_format($inventoryValue); ?></h4>
        </div>
    </div>
    <?php endif; ?>

    <!-- Card 4: Total Products -->
    <?php if (hasPermission('can_manage_products')): ?>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card p-3 h-100 border-0 shadow-sm" style="background: linear-gradient(145deg, #1E293B, #111827);">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="p-2 bg-secondary bg-opacity-10 rounded-3">
                    <i class="fas fa-tags text-secondary fs-5"></i>
                </div>
            </div>
            <p class="text-muted small fw-medium mb-1">Total Products</p>
            <h4 class="fw-bold mb-0 text-white"><?php echo $productCount; ?></h4>
        </div>
    </div>
    <?php endif; ?>

    <!-- Card 5: Low Stock -->
    <?php if (hasPermission('can_manage_stock')): ?>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card p-3 h-100 border-0 shadow-sm" style="background: linear-gradient(145deg, rgba(245, 158, 11, 0.1), #111827); border-left: 3px solid var(--warning) !important;">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="p-2 bg-warning bg-opacity-10 rounded-3">
                    <i class="fas fa-exclamation-triangle text-warning fs-5"></i>
                </div>
            </div>
            <p class="text-warning small fw-medium mb-1 opacity-75">Low Stock</p>
            <h4 class="fw-bold mb-0 text-warning"><?php echo $lowStockCount; ?> Items</h4>
        </div>
    </div>
    <?php endif; ?>

    <!-- Card 6: Out of Stock -->
    <?php if (hasPermission('can_manage_stock')): ?>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card p-3 h-100 border-0 shadow-sm" style="background: linear-gradient(145deg, rgba(239, 68, 68, 0.1), #111827); border-left: 3px solid var(--danger) !important;">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="p-2 bg-danger bg-opacity-10 rounded-3">
                    <i class="fas fa-times-circle text-danger fs-5"></i>
                </div>
            </div>
            <p class="text-danger small fw-medium mb-1 opacity-75">Out of Stock</p>
            <h4 class="fw-bold mb-0 text-danger"><?php echo $outOfStockCount; ?> Items</h4>
        </div>
    </div>
    <?php endif; ?>

</div>

<div class="row g-4 mb-5">
    <!-- Main Chart Section -->
    <?php if (hasPermission('can_view_reports') || hasPermission('can_view_sales')): ?>
    <div class="col-lg-8 animate-fade-in" style="animation-delay: 0.2s">
        <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 fw-bold">Sales Analytics</h4>
                <select class="form-select form-select-sm w-auto bg-dark border-secondary">
                    <option>Last 7 Days</option>
                    <option>Last 30 Days</option>
                </select>
            </div>
            <div style="height: 300px; position: relative;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions & Tasks -->
    <div class="col-lg-4 animate-fade-in" style="animation-delay: 0.3s">
        <?php if (hasPermission('can_view_tasks')): ?>
        <div class="card p-4 mb-4" style="background: linear-gradient(145deg, rgba(139, 92, 246, 0.1), rgba(0, 210, 255, 0.1)); border: 1px solid rgba(255, 255, 255, 0.1);">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Task Overview</h5>
                <a href="tasks.php" class="text-muted small text-decoration-none">View All <i class="fas fa-chevron-right ms-1"></i></a>
            </div>
            <div class="todo-list">
                <?php foreach ($todos as $todo): ?>
                <div class="todo-item d-flex align-items-center p-2 mb-2 rounded-3" style="background: rgba(255,255,255,0.03); border-left: 3px solid <?php 
                    echo $todo['priority'] == 'High' ? '#ef4444' : ($todo['priority'] == 'Medium' ? '#f59e0b' : '#10b981'); 
                ?>;">
                    <div class="flex-grow-1">
                        <div class="small fw-semibold"><?php echo htmlspecialchars($todo['title']); ?></div>
                        <div class="text-muted d-flex align-items-center" style="font-size: 0.65rem;">
                            <i class="far fa-calendar-alt me-1"></i> 
                            <?php echo $todo['due_date'] ? date('M d, H:i', strtotime($todo['due_date'])) : 'No date'; ?>
                            <span class="ms-2 badge bg-dark text-muted p-1" style="font-size: 0.6rem;"><?php echo $todo['category']; ?></span>
                        </div>
                    </div>
                    <a href="tasks.php" class="btn btn-sm btn-link text-primary p-0"><i class="fas fa-arrow-right"></i></a>
                </div>
                <?php endforeach; ?>
                <?php if (empty($todos)): ?>
                    <div class="text-center py-3">
                        <i class="fas fa-check-double text-success mb-2"></i>
                        <p class="text-muted small mb-0">All caught up!</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="d-grid mt-3">
                <a href="tasks.php" class="btn btn-outline-primary btn-sm rounded-3"><i class="fas fa-plus me-1"></i> Quick Add</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="card p-4">
            <h5 class="fw-bold mb-3">Quick Actions</h5>
            <div class="d-grid gap-2">
                <?php if (hasPermission('can_view_sales')): ?>
                <a href="add_sale.php" class="btn btn-primary text-start"><i class="fas fa-plus-circle me-2"></i> Register New Sale</a>
                <?php endif; ?>
                <?php if (hasPermission('can_view_quotations')): ?>
                <a href="add_quotation.php" class="btn btn-outline-info text-start"><i class="fas fa-file-invoice-dollar me-2"></i> Create Quotation</a>
                <?php endif; ?>
                <?php if (hasPermission('can_manage_products')): ?>
                <a href="add_product.php" class="btn btn-outline-light text-start"><i class="fas fa-box-open me-2"></i> Add Product</a>
                <?php endif; ?>
                <?php if (hasPermission('can_manage_stock')): ?>
                <a href="stock_in.php" class="btn btn-outline-light text-start"><i class="fas fa-truck me-2"></i> Restock Items</a>
                <?php endif; ?>
                <?php if (hasPermission('can_view_sales')): ?>
                <a href="view_sales.php" class="btn btn-outline-light text-start"><i class="fas fa-file-invoice-dollar me-2"></i> View Sales</a>
                <?php endif; ?>
                <?php if (hasPermission('can_view_reports')): ?>
                <a href="reports.php" class="btn btn-outline-info text-start"><i class="fas fa-chart-bar me-2"></i> View Reports</a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<div class="row g-4">
    <!-- Critical Stock Alerts -->
    <?php if (hasPermission('can_manage_stock')): ?>
    <div class="col-md-6 animate-fade-in" style="animation-delay: 0.4s">
        <div class="card overflow-hidden">
            <div class="p-4 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Critical Stock</h5>
                <a href="products.php" class="btn btn-outline-danger btn-sm px-3">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Stock</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStockItems as $item): ?>
                        <tr>
                            <td class="small fw-semibold"><?php echo htmlspecialchars($item['name'] . ' - ' . $item['variation_name']); ?></td>
                            <td class="text-danger fw-bold"><?php echo $item['stock_quantity']; ?></td>
                            <td><span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Critical</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Sales -->
    <?php if (hasPermission('can_view_sales')): ?>
    <div class="col-md-6 animate-fade-in" style="animation-delay: 0.5s">
        <div class="card overflow-hidden">
            <div class="p-4 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-info"><i class="fas fa-history me-2"></i>Recent Sales</h5>
                <a href="view_sales.php" class="btn btn-outline-info btn-sm px-3">Full Report</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSales as $sale): ?>
                        <tr>
                            <td class="small fw-semibold"><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                            <td class="text-primary fw-bold">₹<?php echo number_format($sale['total_amount'], 2); ?></td>
                            <td class="small text-muted"><?php echo date('M d, H:i', strtotime($sale['sale_date'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chartLabels); ?>,
        datasets: [
        {
            label: 'Revenue',
            data: <?php echo json_encode($chartData); ?>,
            borderColor: '#00d2ff',
            backgroundColor: 'rgba(0, 210, 255, 0.08)',
            fill: true,
            tension: 0.4,
            borderWidth: 3,
            pointBackgroundColor: '#00d2ff',
            pointRadius: 4
        },
        {
            label: 'Earnings',
            data: <?php echo json_encode($chartEarnings); ?>,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.06)',
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointBackgroundColor: '#10b981',
            pointRadius: 4,
            borderDash: [5,3]
        }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { 
                display: true,
                labels: { color: '#94a3b8', usePointStyle: true, pointStyleWidth: 10 }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ₹' + context.parsed.y.toLocaleString();
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255, 255, 255, 0.05)' },
                ticks: { 
                    color: '#94a3b8',
                    callback: function(value) {
                        return '₹' + value.toLocaleString();
                    }
                }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#94a3b8' }
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
