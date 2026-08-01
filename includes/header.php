<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: login.php");
    exit;
}

// Fallback: If role is not set in session, fetch it from DB and set it
if (isset($_SESSION['user_id']) && !isset($_SESSION['role'])) {
    require_once 'db.php';
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $_SESSION['role'] = $stmt->fetchColumn() ?: 'staff';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>N1 Solution - Premium Inventory</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery & Select2 -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Global JS -->
    <script src="assets/js/global.js?v=<?php echo time(); ?>"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top mb-4">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-2">
                    <i class="fas fa-layer-group text-primary"></i>
                </div>
                N1 SOLUTION
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-1">
                    <li class="nav-item">
                        <a class="nav-link px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-primary active' : 'text-muted'; ?>" href="index.php">Dashboard</a>
                    </li>
                    <?php if (hasPermission('can_manage_products')): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'text-primary active' : 'text-muted'; ?>" href="products.php">Products</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link px-3 dropdown-toggle text-muted" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-layer-group me-1"></i>Features
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark shadow border-secondary">
                            <?php if (hasPermission('can_view_sales')): ?>
                            <li><a class="dropdown-item py-2 <?php echo basename($_SERVER['PHP_SELF']) == 'view_sales.php' ? 'text-primary' : ''; ?>" href="view_sales.php"><i class="fas fa-shopping-cart me-2 text-primary"></i>Sales</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('can_view_quotations')): ?>
                            <li><a class="dropdown-item py-2 <?php echo basename($_SERVER['PHP_SELF']) == 'quotations.php' ? 'text-primary' : ''; ?>" href="quotations.php"><i class="fas fa-file-invoice-dollar me-2 text-info"></i>Quotations</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('can_view_reports')): ?>
                            <li><a class="dropdown-item py-2 <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'text-primary' : ''; ?>" href="reports.php"><i class="fas fa-chart-bar me-2 text-success"></i>Reports</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('can_view_pending_dues')): ?>
                            <li>
                                <?php
                                // Count pending payments for badge
                                try {
                                    $pendingCount = $pdo->query("SELECT COUNT(*) FROM sales WHERE payment_status='Pending' AND type='Sale'")->fetchColumn();
                                } catch(Exception $e) { $pendingCount = 0; }
                                ?>
                                <a class="dropdown-item py-2 d-flex justify-content-between align-items-center <?php echo basename($_SERVER['PHP_SELF']) == 'pending_payments.php' ? 'text-primary' : ''; ?>" href="pending_payments.php">
                                    <span><i class="fas fa-clock me-2 text-warning"></i>Pending Dues</span>
                                    <?php if ($pendingCount > 0): ?>
                                        <span class="badge rounded-pill bg-warning text-dark"><?php echo $pendingCount; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (hasPermission('can_manage_stock')): ?>
                            <li><a class="dropdown-item py-2 <?php echo basename($_SERVER['PHP_SELF']) == 'stock_in.php' ? 'text-primary' : ''; ?>" href="stock_in.php"><i class="fas fa-boxes me-2 text-primary"></i>Stock In</a></li>
                            <?php endif; ?>
                            <?php if (hasPermission('can_view_tasks')): ?>
                            <li><a class="dropdown-item py-2 <?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'text-primary' : ''; ?>" href="tasks.php"><i class="fas fa-tasks me-2 text-secondary"></i>Tasks</a></li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a class="dropdown-item py-2 <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'text-primary' : ''; ?>" href="services.php">
                                    <i class="fas fa-concierge-bell me-2 text-warning"></i>Our Services & Portals
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Global Search Bar -->
                    <li class="nav-item ms-lg-4 me-lg-3 d-none d-lg-block">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text bg-dark border-secondary text-muted"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control bg-dark border-secondary text-light" placeholder="Search anything...">
                        </div>
                    </li>
                    <li class="nav-item px-2">
                        <a href="tasks.php" class="text-muted text-decoration-none position-relative fs-5" title="Notifications">
                            <i class="far fa-bell"></i>
                            <span id="global-notification-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger d-none" style="padding: 4px; border: 2px solid var(--bg-dark);"> </span>
                        </a>
                    </li>
                    <?php if (hasPermission('can_view_sales')): ?>
                    <li class="nav-item ms-lg-2 d-flex align-items-center">
                        <a class="btn btn-primary btn-sm fw-bold px-3 shadow-sm rounded-pill d-flex align-items-center text-nowrap" href="add_sale.php">
                            <i class="fas fa-plus me-1"></i> Quick Add
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item ms-lg-3 border-start border-secondary ps-3">
                        <div class="dropdown">
                            <button class="btn btn-link nav-link dropdown-toggle d-flex align-items-center text-light text-decoration-none p-0" type="button" data-bs-toggle="dropdown" data-bs-display="static">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username'] ?? 'User'); ?>&background=0099FF&color=fff" class="rounded-circle me-2 border border-secondary" width="32" height="32">
                                <span class="d-none d-sm-inline fw-medium text-sm"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg border-secondary rounded-3 mt-2">
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <li><a class="dropdown-item py-2" href="settings.php"><i class="fas fa-cog me-2 text-muted"></i> Settings</a></li>
                                <li><hr class="dropdown-divider border-secondary opacity-50"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item py-2 text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Sign out</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container pb-5">
