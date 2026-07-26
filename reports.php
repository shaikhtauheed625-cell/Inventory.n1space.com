<?php
require_once 'db.php';
session_start();

if (!hasPermission('can_view_reports')) {
    die("Access Denied: You do not have permission to view reports.");
}
include 'includes/header.php';

// Date range filter
$rangeType = $_GET['range'] ?? 'month';
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo   = $_GET['to']   ?? date('Y-m-d');

if ($rangeType === 'month') {
    $dateFrom = date('Y-m-01');
    $dateTo   = date('Y-m-d');
} elseif ($rangeType === 'last_month') {
    $dateFrom = date('Y-m-01', strtotime('-1 month'));
    $dateTo   = date('Y-m-t', strtotime('-1 month'));
} elseif ($rangeType === 'year') {
    $dateFrom = date('Y-01-01');
    $dateTo   = date('Y-m-d');
} elseif ($rangeType === 'all') {
    $dateFrom = '2000-01-01';
    $dateTo   = date('Y-m-d');
}

// Summary Stats for selected range
$rangeRevenue  = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE DATE(sale_date) BETWEEN '$dateFrom' AND '$dateTo' AND type='Sale'")->fetchColumn() ?: 0;
$rangeEarnings = $pdo->query("SELECT SUM(total_earnings) FROM sales WHERE DATE(sale_date) BETWEEN '$dateFrom' AND '$dateTo' AND type='Sale'")->fetchColumn() ?: 0;
$rangeSales    = $pdo->query("SELECT COUNT(*) FROM sales WHERE DATE(sale_date) BETWEEN '$dateFrom' AND '$dateTo' AND type='Sale'")->fetchColumn() ?: 0;
$rangeAvg      = $rangeSales > 0 ? $rangeRevenue / $rangeSales : 0;

// Monthly chart (last 12 months)
$monthlyLabels = [];
$monthlyRevenue = [];
$monthlyEarnings = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $rev  = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE DATE_FORMAT(sale_date,'%Y-%m')='$month' AND type='Sale'")->fetchColumn() ?: 0;
    $earn = $pdo->query("SELECT SUM(total_earnings) FROM sales WHERE DATE_FORMAT(sale_date,'%Y-%m')='$month' AND type='Sale'")->fetchColumn() ?: 0;
    $monthlyLabels[]  = $label;
    $monthlyRevenue[] = (float)$rev;
    $monthlyEarnings[]= (float)$earn;
}

// Top 8 selling products by quantity
$topProducts = $pdo->query("
    SELECT COALESCE(p.name, si.manual_product_name) as name,
           SUM(si.quantity) as total_qty,
           SUM(si.quantity * si.unit_price) as total_revenue
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    LEFT JOIN variations v ON si.variation_id = v.id
    LEFT JOIN products p ON v.product_id = p.id
    WHERE s.type = 'Sale'
    GROUP BY name
    ORDER BY total_qty DESC
    LIMIT 8
")->fetchAll();

// Payment method breakdown
$paymentMethods = $pdo->query("
    SELECT COALESCE(payment_method,'Cash') as method, COUNT(*) as count, SUM(total_amount) as total
    FROM sales
    WHERE type='Sale'
    GROUP BY payment_method
")->fetchAll();

// Payment status split
$paidTotal    = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE payment_status='Paid' AND type='Sale'")->fetchColumn() ?: 0;
$pendingTotal = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE payment_status='Pending' AND type='Sale'")->fetchColumn() ?: 0;

// CSV Export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Sale ID','Customer','Phone','Date','Amount','Earnings','Status','Method']);
    $allSales = $pdo->query("SELECT * FROM sales WHERE DATE(sale_date) BETWEEN '$dateFrom' AND '$dateTo' AND type='Sale' ORDER BY sale_date DESC")->fetchAll();
    foreach ($allSales as $s) {
        fputcsv($out, ['#INV-'.str_pad($s['id'],5,'0',STR_PAD_LEFT), $s['customer_name'], $s['customer_phone'], $s['sale_date'], $s['total_amount'], $s['total_earnings'], $s['payment_status'], $s['payment_method']]);
    }
    fclose($out);
    exit;
}
?>

<div class="d-flex justify-content-between align-items-start mb-4 animate-fade-in flex-wrap gap-3">
    <div>
        <h2 class="fw-bold mb-1"><i class="fas fa-chart-bar me-2 text-primary"></i>Reports & Analytics</h2>
        <p class="text-muted small">Deep insights into your business performance.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="?range=all&export=1" class="btn btn-outline-success btn-sm rounded-pill px-4">
            <i class="fas fa-download me-1"></i> Export CSV
        </a>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card p-3 mb-5 animate-fade-in" style="animation-delay:0.05s">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-auto">
            <label class="form-label text-muted small fw-bold mb-1">QUICK RANGE</label>
            <div class="btn-group">
                <a href="?range=month" class="btn btn-sm <?php echo $rangeType=='month'?'btn-primary':'btn-outline-secondary'; ?>">This Month</a>
                <a href="?range=last_month" class="btn btn-sm <?php echo $rangeType=='last_month'?'btn-primary':'btn-outline-secondary'; ?>">Last Month</a>
                <a href="?range=year" class="btn btn-sm <?php echo $rangeType=='year'?'btn-primary':'btn-outline-secondary'; ?>">This Year</a>
                <a href="?range=all" class="btn btn-sm <?php echo $rangeType=='all'?'btn-primary':'btn-outline-secondary'; ?>">All Time</a>
            </div>
        </div>
        <div class="col-auto">
            <label class="form-label text-muted small fw-bold mb-1">FROM</label>
            <input type="date" name="from" class="form-control form-control-sm" value="<?php echo $dateFrom; ?>">
        </div>
        <div class="col-auto">
            <label class="form-label text-muted small fw-bold mb-1">TO</label>
            <input type="date" name="to" class="form-control form-control-sm" value="<?php echo $dateTo; ?>">
        </div>
        <div class="col-auto">
            <button type="submit" name="range" value="custom" class="btn btn-primary btn-sm px-4">Apply</button>
        </div>
        <div class="col-auto ms-auto text-muted small">
            Showing: <strong><?php echo date('M d, Y', strtotime($dateFrom)); ?></strong> — <strong><?php echo date('M d, Y', strtotime($dateTo)); ?></strong>
        </div>
    </form>
</div>

<!-- Summary Stats -->
<div class="row g-4 mb-5 animate-fade-in" style="animation-delay:0.1s">
    <div class="col-md-3 col-6">
        <div class="card p-4 h-100">
            <div class="stat-card-title">Period Revenue</div>
            <div class="stat-card-value">₹<?php echo number_format($rangeRevenue, 2); ?></div>
            <div class="small text-muted mt-2"><i class="fas fa-rupee-sign me-1"></i> Total sales amount</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-4 h-100">
            <div class="stat-card-title">Period Earnings</div>
            <div class="stat-card-value text-success">₹<?php echo number_format($rangeEarnings, 2); ?></div>
            <div class="small text-muted mt-2">
                <i class="fas fa-percentage me-1"></i>
                <?php echo $rangeRevenue > 0 ? number_format(($rangeEarnings/$rangeRevenue)*100, 1) : 0; ?>% margin
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-4 h-100">
            <div class="stat-card-title">Total Orders</div>
            <div class="stat-card-value"><?php echo $rangeSales; ?></div>
            <div class="small text-muted mt-2"><i class="fas fa-shopping-bag me-1"></i> Sales transactions</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card p-4 h-100">
            <div class="stat-card-title">Avg Order Value</div>
            <div class="stat-card-value">₹<?php echo number_format($rangeAvg, 2); ?></div>
            <div class="small text-muted mt-2"><i class="fas fa-receipt me-1"></i> Per transaction</div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-5">
    <!-- Monthly Revenue Chart -->
    <div class="col-lg-8 animate-fade-in" style="animation-delay:0.2s">
        <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Monthly Revenue & Earnings</h5>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Last 12 Months</span>
            </div>
            <div style="height:300px; position:relative">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Payment Status Doughnut -->
    <div class="col-lg-4 animate-fade-in" style="animation-delay:0.25s">
        <div class="card p-4 h-100">
            <h5 class="fw-bold mb-4">Payment Status</h5>
            <div style="height:220px; position:relative; margin: auto; max-width:220px">
                <canvas id="statusChart"></canvas>
            </div>
            <div class="mt-3 d-flex justify-content-around">
                <div class="text-center">
                    <div class="small text-muted">Paid</div>
                    <div class="fw-bold text-success">₹<?php echo number_format($paidTotal, 2); ?></div>
                </div>
                <div class="text-center">
                    <div class="small text-muted">Pending</div>
                    <div class="fw-bold text-warning">₹<?php echo number_format($pendingTotal, 2); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- Top Products Chart -->
    <div class="col-lg-7 animate-fade-in" style="animation-delay:0.3s">
        <div class="card p-4 h-100">
            <h5 class="fw-bold mb-4">Top Selling Products</h5>
            <div style="height:300px; position:relative">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Payment Methods -->
    <div class="col-lg-5 animate-fade-in" style="animation-delay:0.35s">
        <div class="card p-4 h-100">
            <h5 class="fw-bold mb-4">Payment Methods</h5>
            <div style="height:200px; position:relative; margin:auto; max-width:200px">
                <canvas id="methodChart"></canvas>
            </div>
            <div class="mt-4">
                <?php foreach ($paymentMethods as $m): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small text-muted"><i class="fas fa-circle me-2" style="font-size:0.5rem"></i><?php echo htmlspecialchars($m['method']); ?></span>
                    <div class="text-end">
                        <span class="fw-bold text-light small">₹<?php echo number_format($m['total'], 2); ?></span>
                        <span class="text-muted small ms-2">(<?php echo $m['count']; ?> orders)</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Revenue & Earnings Chart
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($monthlyLabels); ?>,
        datasets: [
        {
            label: 'Revenue',
            data: <?php echo json_encode($monthlyRevenue); ?>,
            backgroundColor: 'rgba(0, 210, 255, 0.6)',
            borderColor: '#00d2ff',
            borderWidth: 1,
            borderRadius: 6
        },
        {
            label: 'Earnings',
            data: <?php echo json_encode($monthlyEarnings); ?>,
            backgroundColor: 'rgba(16, 185, 129, 0.6)',
            borderColor: '#10b981',
            borderWidth: 1,
            borderRadius: 6
        }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { labels: { color: '#94a3b8', usePointStyle: true } },
            tooltip: { callbacks: { label: c => c.dataset.label + ': ₹' + c.parsed.y.toLocaleString() } }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8', callback: v => '₹' + v.toLocaleString() } },
            x: { grid: { display: false }, ticks: { color: '#94a3b8', maxRotation: 45 } }
        }
    }
});

// Payment Status Doughnut
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Paid', 'Pending'],
        datasets: [{ data: [<?php echo $paidTotal; ?>, <?php echo $pendingTotal; ?>], backgroundColor: ['rgba(16,185,129,0.8)', 'rgba(245,158,11,0.8)'], borderColor: ['#10b981','#f59e0b'], borderWidth: 2 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '70%' }
});

// Top Products Horizontal Bar
new Chart(document.getElementById('topProductsChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($topProducts, 'name')); ?>,
        datasets: [{
            label: 'Units Sold',
            data: <?php echo json_encode(array_column($topProducts, 'total_qty')); ?>,
            backgroundColor: ['rgba(139,92,246,0.7)','rgba(0,210,255,0.7)','rgba(16,185,129,0.7)','rgba(245,158,11,0.7)','rgba(239,68,68,0.7)','rgba(236,72,153,0.7)','rgba(99,102,241,0.7)','rgba(20,184,166,0.7)'],
            borderRadius: 6, borderWidth: 0
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => c.parsed.x + ' units' } } },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
            y: { grid: { display: false }, ticks: { color: '#94a3b8' } }
        }
    }
});

// Payment Methods Doughnut
const methodColors = ['rgba(0,210,255,0.8)','rgba(139,92,246,0.8)','rgba(16,185,129,0.8)','rgba(245,158,11,0.8)','rgba(239,68,68,0.8)'];
new Chart(document.getElementById('methodChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($paymentMethods, 'method')); ?>,
        datasets: [{ data: <?php echo json_encode(array_column($paymentMethods, 'total')); ?>, backgroundColor: methodColors, borderWidth: 2 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '60%' }
});
</script>

<?php include 'includes/footer.php'; ?>
