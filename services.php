<?php
require_once 'db.php';
session_start();

// Handle Service Add/Edit/Delete for Admins
$success = '';
$error = '';

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_service') {
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fas fa-globe');
        
        if ($title && $url) {
            try {
                $stmt = $pdo->prepare("INSERT INTO services (title, url, description, icon) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $url, $description, $icon]);
                $success = "Service added successfully.";
            } catch (Exception $e) {
                // Table might not exist, auto-create it
                $pdo->exec("CREATE TABLE IF NOT EXISTS services (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    url VARCHAR(255) NOT NULL,
                    description TEXT,
                    icon VARCHAR(100) DEFAULT 'fas fa-globe',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $stmt = $pdo->prepare("INSERT INTO services (title, url, description, icon) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $url, $description, $icon]);
                $success = "Service added successfully.";
            }
        } else {
            $error = "Please fill in all required fields.";
        }
    } elseif ($action === 'delete_service') {
        $id = $_POST['id'] ?? 0;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Service removed successfully.";
        }
    }
}

// Fetch Services
$servicesList = [];
try {
    $servicesList = $pdo->query("SELECT * FROM services ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {
    // If table doesn't exist yet, create default entry
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        url VARCHAR(255) NOT NULL,
        description TEXT,
        icon VARCHAR(100) DEFAULT 'fas fa-globe',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Seed default services
    $pdo->exec("INSERT INTO services (title, url, description, icon) VALUES 
        ('N1 Shopping Store', 'https://www.n1shopping.com', 'Explore our full e-commerce store for computers, accessories, and gadget deals.', 'fas fa-shopping-bag'),
        ('ClubHosty Cloud Hosting', 'https://my.clubhosty.com', 'High speed SSD web hosting, domain registration, and cloud server solutions.', 'fas fa-server'),
        ('IT Asset & Inventory', 'http://localhost/inventry-app', 'Centralized device tracking, stock management, and invoice billing platform.', 'fas fa-boxes')
    ");
    $servicesList = $pdo->query("SELECT * FROM services ORDER BY id DESC")->fetchAll();
}

include 'includes/header.php';
?>

<div class="row animate-fade-in mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1"><i class="fas fa-concierge-bell me-2 text-warning"></i>Our Services & Platforms</h2>
        <p class="text-muted">Explore our full ecosystem of web applications, cloud hosting, e-commerce stores, and digital solutions.</p>
    </div>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="col-md-4 text-md-end d-flex align-items-center justify-content-md-end">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
            <i class="fas fa-plus me-1"></i> Add New Service Website
        </button>
    </div>
    <?php endif; ?>
</div>

<?php if ($success): ?>
    <div class="alert alert-success bg-success bg-opacity-10 border-success border-opacity-25 text-success mb-4"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger mb-4"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Services Grid -->
<div class="row g-4">
    <?php foreach ($servicesList as $svc): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 p-4 border-secondary border-opacity-25 shadow-sm position-relative hover-lift" style="background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(10px); border-radius: 16px;">
            <div class="d-flex align-items-center mb-3">
                <div class="p-3 rounded-3 me-3" style="background: rgba(0, 212, 255, 0.1); border: 1px solid rgba(0, 212, 255, 0.2);">
                    <i class="<?php echo htmlspecialchars($svc['icon']); ?> fs-3 text-cyan" style="color: #00d4ff;"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1 text-light"><?php echo htmlspecialchars($svc['title']); ?></h5>
                    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25" style="font-size: 10px;">VERIFIED SERVICE</span>
                </div>
            </div>
            
            <p class="text-muted small mb-4 flex-grow-1" style="line-height: 1.6;">
                <?php echo htmlspecialchars($svc['description']); ?>
            </p>

            <div class="d-flex align-items-center justify-content-between pt-3 border-top border-secondary border-opacity-25">
                <a href="<?php echo htmlspecialchars($svc['url']); ?>" target="_blank" class="btn btn-outline-info btn-sm rounded-pill px-3 fw-bold">
                    Visit Website <i class="fas fa-external-link-alt ms-1 fs-6"></i>
                </a>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <form method="POST" onsubmit="return confirm('Are you sure you want to remove this service?');" class="d-inline">
                    <input type="hidden" name="action" value="delete_service">
                    <input type="hidden" name="id" value="<?php echo $svc['id']; ?>">
                    <button type="submit" class="btn btn-link text-danger p-0" title="Delete Service">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal to Add New Service Website (Admin Only) -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-light"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Service Website</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_service">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Service Title / Name</label>
                        <input type="text" name="title" class="form-control bg-dark text-light border-secondary" required placeholder="e.g. ClubHosty Cloud Hosting">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Website URL</label>
                        <input type="url" name="url" class="form-control bg-dark text-light border-secondary" required placeholder="https://example.com">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Icon (FontAwesome Class)</label>
                        <select name="icon" class="form-select bg-dark text-light border-secondary">
                            <option value="fas fa-shopping-bag">Shopping / E-commerce (fa-shopping-bag)</option>
                            <option value="fas fa-server">Cloud / Server Hosting (fa-server)</option>
                            <option value="fas fa-globe">Website / Portal (fa-globe)</option>
                            <option value="fas fa-boxes">Inventory / Warehouse (fa-boxes)</option>
                            <option value="fas fa-tools">Maintenance / Support (fa-tools)</option>
                            <option value="fas fa-laptop-code">Software / App (fa-laptop-code)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Description</label>
                        <textarea name="description" class="form-control bg-dark text-light border-secondary" rows="3" placeholder="Brief summary of what this website/service offers to customers..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Service</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
