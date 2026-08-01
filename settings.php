<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied. Only administrators can access this page.");
}

$success = '';
$error = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    try {
        $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute(['invoice_terms', $_POST['invoice_terms'] ?? '']);
        $stmt->execute(['quotation_terms', $_POST['quotation_terms'] ?? '']);
        $stmt->execute(['invoice_notes', $_POST['invoice_notes'] ?? '']);
        $stmt->execute(['quotation_notes', $_POST['quotation_notes'] ?? '']);
        $stmt->execute(['our_services', $_POST['our_services'] ?? '']);
        $success = "Settings updated successfully.";
    } catch (Exception $e) {
        $error = "Failed to update settings: " . $e->getMessage();
    }
}

// Handle User Permissions Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user_permissions') {
    try {
        $userId = $_POST['user_id'] ?? 0;
        $perms = [
            'can_view_sales' => isset($_POST['can_view_sales']),
            'can_view_quotations' => isset($_POST['can_view_quotations']),
            'can_view_reports' => isset($_POST['can_view_reports']),
            'can_view_pending_dues' => isset($_POST['can_view_pending_dues']),
            'can_view_tasks' => isset($_POST['can_view_tasks']),
            'can_view_purchase_price' => isset($_POST['can_view_purchase_price']),
            'can_delete_sales' => isset($_POST['can_delete_sales']),
            'can_manage_products' => isset($_POST['can_manage_products']),
            'can_manage_stock' => isset($_POST['can_manage_stock']),
        ];
        $stmt = $pdo->prepare("UPDATE users SET permissions = ? WHERE id = ?");
        $stmt->execute([json_encode($perms), $userId]);
        $success = "User permissions updated successfully.";
    } catch (Exception $e) {
        $error = "Failed to update permissions: " . $e->getMessage();
    }
}

// Handle User Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'staff';

    if ($username && $password) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hash, $role]);
            $success = "User added successfully.";
        } catch (Exception $e) {
            $error = "Failed to add user (Username might already exist).";
        }
    }
}

// Handle User Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $userId = $_POST['user_id'];
    if ($userId != $_SESSION['user_id']) { // Prevent self-delete
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $success = "User deleted successfully.";
    } else {
        $error = "You cannot delete your own account.";
    }
}

// Handle Change Role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_role') {
    $userId = $_POST['user_id'];
    $newRole = $_POST['new_role'];
    if ($userId != $_SESSION['user_id']) { // Prevent self-change
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$newRole, $userId]);
        $success = "User role updated successfully.";
    } else {
        $error = "You cannot change your own role.";
    }
}

// Fetch Settings
$settings = [];
$sStmt = $pdo->query("SELECT * FROM settings");
while ($row = $sStmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Fetch Users
$users = $pdo->query("SELECT id, username, role, permissions, created_at FROM users ORDER BY username ASC")->fetchAll();

include 'includes/header.php';
?>

<div class="row animate-fade-in">
    <div class="col-12 mb-4">
        <h2 class="fw-bold mb-0"><i class="fas fa-cogs me-2 text-primary"></i>Admin Settings</h2>
        <p class="text-muted">Manage application settings and user permissions.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success bg-success bg-opacity-10 border-success border-opacity-25 text-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card p-4">
            <h5 class="fw-bold mb-4 border-bottom pb-2 border-secondary border-opacity-25"><i class="fas fa-file-invoice me-2 text-info"></i>Invoice & Quotation Text</h5>
            <form method="POST">
                <input type="hidden" name="action" value="update_settings">
                
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold uppercase">Quotation Terms & Conditions</label>
                    <textarea name="quotation_terms" class="form-control" rows="4"><?php echo htmlspecialchars($settings['quotation_terms'] ?? ''); ?></textarea>
                    <div class="form-text small">Enter each term on a new line.</div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold uppercase">Quotation Notes</label>
                    <textarea name="quotation_notes" class="form-control" rows="2"><?php echo htmlspecialchars($settings['quotation_notes'] ?? ''); ?></textarea>
                </div>

                <div class="mb-3 mt-4">
                    <label class="form-label text-muted small fw-bold uppercase">Invoice Terms & Conditions</label>
                    <textarea name="invoice_terms" class="form-control" rows="4"><?php echo htmlspecialchars($settings['invoice_terms'] ?? ''); ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold uppercase">Invoice Notes</label>
                    <textarea name="invoice_notes" class="form-control" rows="2"><?php echo htmlspecialchars($settings['invoice_notes'] ?? ''); ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold uppercase"><i class="fas fa-concierge-bell me-1 text-warning"></i> Our Services (Printed on Invoice/Quotation)</label>
                    <textarea name="our_services" class="form-control" rows="4" placeholder="e.g. IT Consulting&#10;Software Development&#10;Hardware Maintenance&#10;Cloud Solutions"><?php echo htmlspecialchars($settings['our_services'] ?? "IT Asset Management\nDevice Procurement & Maintenance\nSoftware Licensing & Compliance\nCloud & Network Infrastructure"); ?></textarea>
                    <div class="form-text small">Enter each service on a new line.</div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Settings</button>
            </form>
        </div>
    </div>

    <!-- User Management Section -->
    <div class="col-md-6 mb-4">
        <!-- Add User -->
        <div class="card p-4 mb-4">
            <h5 class="fw-bold mb-4 border-bottom pb-2 border-secondary border-opacity-25"><i class="fas fa-user-plus me-2 text-success"></i>Add New User</h5>
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="add_user">
                <div class="col-md-6">
                    <label class="form-label text-muted small">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label text-muted small">Role</label>
                    <select name="role" class="form-select">
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">Add User</button>
                </div>
            </form>
        </div>

        <!-- Manage Users Table -->
        <div class="card p-4">
            <h5 class="fw-bold mb-4 border-bottom pb-2 border-secondary border-opacity-25"><i class="fas fa-users-cog me-2 text-warning"></i>Manage Users</h5>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <?php $uPerms = !empty($u['permissions']) ? json_decode($u['permissions'], true) : []; ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($u['username']); ?></div>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-25 text-light border border-secondary border-opacity-50">Staff</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <div class="d-flex justify-content-end gap-2">
                                    <?php if ($u['role'] === 'staff'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#permissionsModal<?php echo $u['id']; ?>" title="Edit Permissions">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <?php endif; ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="change_role">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="new_role" value="<?php echo $u['role'] === 'admin' ? 'staff' : 'admin'; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-info" title="Change Role"><i class="fas fa-exchange-alt"></i></button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete User"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                                
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<?php foreach ($users as $u): ?>
    <?php if ($u['role'] === 'staff'): ?>
        <?php $uPerms = !empty($u['permissions']) ? json_decode($u['permissions'], true) : []; ?>
        <!-- Permissions Modal for <?php echo htmlspecialchars($u['username']); ?> -->
        <div class="modal fade" id="permissionsModal<?php echo $u['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark border-secondary text-start">
                    <div class="modal-header border-secondary border-opacity-25">
                        <h5 class="modal-title">Permissions: <?php echo htmlspecialchars($u['username']); ?></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="update_user_permissions">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" name="can_view_sales" id="permSales<?php echo $u['id']; ?>" <?php echo !empty($uPerms['can_view_sales']) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-light" for="permSales<?php echo $u['id']; ?>">View Sales</label>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" name="can_view_quotations" id="permQuotations<?php echo $u['id']; ?>" <?php echo !empty($uPerms['can_view_quotations']) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-light" for="permQuotations<?php echo $u['id']; ?>">View Quotations</label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" name="can_view_reports" id="permReports<?php echo $u['id']; ?>" <?php echo !empty($uPerms['can_view_reports']) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-light" for="permReports<?php echo $u['id']; ?>">View Reports</label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" name="can_view_pending_dues" id="permPendingDues<?php echo $u['id']; ?>" <?php echo !empty($uPerms['can_view_pending_dues']) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-light" for="permPendingDues<?php echo $u['id']; ?>">View Pending Dues</label>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" name="can_view_tasks" id="permTasks<?php echo $u['id']; ?>" <?php echo !empty($uPerms['can_view_tasks']) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-light" for="permTasks<?php echo $u['id']; ?>">View Tasks</label>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" name="can_view_purchase_price" id="permPurchasePrice<?php echo $u['id']; ?>" <?php echo !empty($uPerms['can_view_purchase_price']) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-light" for="permPurchasePrice<?php echo $u['id']; ?>">View Purchase Price</label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" name="can_delete_sales" id="permDeleteSales<?php echo $u['id']; ?>" <?php echo !empty($uPerms['can_delete_sales']) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-light" for="permDeleteSales<?php echo $u['id']; ?>">Delete Sales & Quotations</label>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" name="can_manage_products" id="permManageProducts<?php echo $u['id']; ?>" <?php echo !empty($uPerms['can_manage_products']) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-light" for="permManageProducts<?php echo $u['id']; ?>">Manage Products</label>
                            </div>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" name="can_manage_stock" id="permManageStock<?php echo $u['id']; ?>" <?php echo !empty($uPerms['can_manage_stock']) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-light" for="permManageStock<?php echo $u['id']; ?>">Manage Stock</label>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary border-opacity-25">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php include 'includes/footer.php'; ?>
