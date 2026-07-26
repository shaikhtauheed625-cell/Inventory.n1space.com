<?php
require_once 'db.php';
header('Content-Type: text/html; charset=utf-8');

$vStmt = $pdo->query("SELECT v.id, p.name, v.variation_name, v.price, v.stock_quantity 
                      FROM variations v 
                      JOIN products p ON v.product_id = p.id 
                      ORDER BY p.name ASC");
$availableVariations = $vStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $totalAmount = 0;
        
        // Calculate Total
        foreach ($_POST['items'] as $item) {
            if ($item['qty'] > 0) {
                if ($item['id'] === 'manual') {
                    $totalAmount += (float)$item['price'] * (int)$item['qty'];
                } else {
                    $pStmt = $pdo->prepare("SELECT price FROM variations WHERE id = ?");
                    $pStmt->execute([$item['id']]);
                    $var = $pStmt->fetch();
                    $totalAmount += $var['price'] * $item['qty'];
                }
            }
        }

        // Create Quotation (type = 'Quotation')
        $sStmt = $pdo->prepare("INSERT INTO sales (customer_name, customer_phone, total_amount, payment_status, payment_method, due_date, type) VALUES (?, ?, ?, ?, ?, ?, 'Quotation')");
        $sStmt->execute([
            $_POST['customer_name'], 
            $_POST['customer_phone'] ?? '', 
            $totalAmount,
            'Pending', // Quotes are always pending payment technically
            $_POST['payment_method'] ?? 'Cash',
            $_POST['due_date'] ?: date('Y-m-d')
        ]);
        $quoteId = $pdo->lastInsertId();

        // Add Items
        $iStmt = $pdo->prepare("INSERT INTO sale_items (sale_id, variation_id, manual_product_name, manual_variation_name, quantity, unit_price) VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($_POST['items'] as $item) {
            if ($item['qty'] > 0) {
                if ($item['id'] === 'manual') {
                    $iStmt->execute([$quoteId, null, $item['name'], $item['variation'], $item['qty'], $item['price']]);
                } else {
                    $pStmt = $pdo->prepare("SELECT price FROM variations WHERE id = ?");
                    $pStmt->execute([$item['id']]);
                    $price = $pStmt->fetchColumn();
                    $iStmt->execute([$quoteId, $item['id'], null, null, $item['qty'], $price]);
                }
            }
        }
        
        $pdo->commit();

        if (isset($_POST['send_whatsapp']) && !empty($_POST['customer_phone'])) {
            $phone = preg_replace('/[^0-9]/', '', $_POST['customer_phone']);
            if (strlen($phone) < 10) $phone = '91' . $phone; 
            
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $invoiceUrl = "$protocol://$host" . dirname($_SERVER['PHP_SELF']) . "/invoice.php?id=$quoteId";

            $message = "Hello \u{1F60A}\n";
            $message .= "Please find your quotation details below:\n\n";
            $message .= "*Quotation:* #QT-" . str_pad($quoteId, 5, '0', STR_PAD_LEFT) . "\n";
            $message .= "*Amount:* \u{20B9}" . number_format($totalAmount, 2) . "\n";
            $message .= "*View Quotation:* " . $invoiceUrl . "\n\n";
            $message .= "Please let us know if you would like to proceed.\n";
            $message .= "Thank you! \u{1F49C}";
            
            $waUrl = "https://wa.me/" . $phone . "?text=" . urlencode($message);
            echo "<script>window.open('$waUrl', '_blank'); window.location.href='invoice.php?id=$quoteId';</script>";
            exit;
        }

        header("Location: invoice.php?id=" . $quoteId);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center animate-fade-in">
    <div class="col-md-10">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0"><i class="fas fa-file-invoice-dollar me-2 text-info"></i>New Quotation</h2>
                <a href="quotations.php" class="btn btn-outline-light btn-sm">Cancel</a>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted small uppercase fw-bold">Customer Name</label>
                        <input type="text" name="customer_name" class="form-control" required placeholder="Enter customer name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small uppercase fw-bold">Phone Number (WhatsApp)</label>
                        <input type="text" name="customer_phone" class="form-control" placeholder="e.g. 9876543210">
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted small uppercase fw-bold">Expected Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI / PhonePe / GPay</option>
                            <option value="Card">Credit/Debit Card</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small uppercase fw-bold">Valid Until</label>
                        <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                    </div>
                </div>

                <div class="form-check form-switch mb-5 p-3 rounded-4" style="background: linear-gradient(90deg, rgba(13, 202, 240, 0.1), rgba(0, 210, 255, 0.1)); border: 1px solid rgba(13, 202, 240, 0.2);">
                    <input class="form-check-input ms-0 me-3" type="checkbox" name="send_whatsapp" id="sendWhatsapp" checked style="width: 3rem; height: 1.5rem;">
                    <label class="form-check-label fw-bold text-light" for="sendWhatsapp" style="padding-top: 2px;">
                        <i class="fab fa-whatsapp me-1 text-success"></i> Send WhatsApp Quotation
                    </label>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Items to Quote</h5>
                    <button type="button" id="add-item" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-plus me-1"></i> Add Item
                    </button>
                </div>

                <div id="items-container">
                    <div class="item-row mb-3 p-3 border border-secondary border-opacity-10 rounded-3 bg-dark bg-opacity-25">
                        <div class="row g-3 align-items-start">
                            <div class="col-md-5">
                                <label class="form-label small text-muted">Product Variation</label>
                                <select name="items[0][id]" class="form-select item-select" required>
                                    <option value="">Choose product...</option>
                                    <option value="manual" class="text-info fw-bold">+ Add Manual Entry</option>
                                    <?php foreach ($availableVariations as $av): ?>
                                        <option value="<?php echo $av['id']; ?>" data-price="<?php echo $av['price']; ?>">
                                            <?php echo htmlspecialchars($av['name'] . ' - ' . $av['variation_name']); ?> 
                                            (₹<?php echo number_format($av['price'], 2); ?> | Stock: <?php echo $av['stock_quantity']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Quantity</label>
                                <input type="number" name="items[0][qty]" class="form-control" value="1" min="1" required>
                            </div>
                            <div class="col-md-4 manual-fields d-none">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small text-muted">Prod Name</label>
                                        <input type="text" name="items[0][name]" class="form-control form-control-sm" placeholder="Name">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small text-muted">Price (₹)</label>
                                        <input type="number" step="0.01" name="items[0][price]" class="form-control form-control-sm manual-price" placeholder="Price">
                                    </div>
                                    <div class="col-12 mt-2">
                                        <input type="text" name="items[0][variation]" class="form-control form-control-sm" placeholder="Variation (Optional)">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-1 text-end">
                                <button type="button" class="btn btn-outline-danger w-100 remove-item"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 p-4 mb-4 mt-4" style="background: linear-gradient(135deg, rgba(13, 202, 240, 0.05), rgba(58, 123, 213, 0.05)); border: 1px solid rgba(13, 202, 240, 0.1) !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-bold">Quote Total</div>
                            <h2 class="mb-0 text-info fw-bold" id="grand-total">₹0.00</h2>
                        </div>
                        <button type="submit" class="btn btn-info btn-lg text-dark fw-bold">
                            <i class="fas fa-file-invoice me-2"></i> Create Quotation
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let itemCount = 1;
const addBtn = document.getElementById('add-item');
const container = document.getElementById('items-container');

addBtn.addEventListener('click', function() {
    const newRow = document.createElement('div');
    newRow.className = 'item-row mb-3 p-3 border border-secondary border-opacity-10 rounded-3 bg-dark bg-opacity-25 animate-fade-in';
    newRow.innerHTML = `
        <div class="row g-3 align-items-start">
            <div class="col-md-5">
                <label class="form-label small text-muted">Product Variation</label>
                <select name="items[${itemCount}][id]" class="form-select item-select" required>
                    <option value="">Choose product...</option>
                    <option value="manual" class="text-info fw-bold">+ Add Manual Entry</option>
                    <?php foreach ($availableVariations as $av): ?>
                        <option value="<?php echo $av['id']; ?>" data-price="<?php echo $av['price']; ?>">
                            <?php echo htmlspecialchars($av['name'] . ' - ' . $av['variation_name']); ?> 
                            (₹<?php echo number_format($av['price'], 2); ?> | Stock: <?php echo $av['stock_quantity']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Quantity</label>
                <input type="number" name="items[${itemCount}][qty]" class="form-control" value="1" min="1" required>
            </div>
            <div class="col-md-4 manual-fields d-none">
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small text-muted">Prod Name</label>
                        <input type="text" name="items[${itemCount}][name]" class="form-control form-control-sm" placeholder="Name">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted">Price (₹)</label>
                        <input type="number" step="0.01" name="items[${itemCount}][price]" class="form-control form-control-sm manual-price" placeholder="Price">
                    </div>
                    <div class="col-12 mt-2">
                        <input type="text" name="items[${itemCount}][variation]" class="form-control form-control-sm" placeholder="Variation (Optional)">
                    </div>
                </div>
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-outline-danger w-100 remove-item"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
    itemCount++;
    updateTotal();
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('item-select')) {
        const row = e.target.closest('.item-row');
        const manualFields = row.querySelector('.manual-fields');
        const inputs = manualFields.querySelectorAll('input');
        
        if (e.target.value === 'manual') {
            manualFields.classList.remove('d-none');
            inputs.forEach(i => i.required = true);
            row.querySelector('[name*="[variation]"]').required = false; // Variation optional
        } else {
            manualFields.classList.add('d-none');
            inputs.forEach(i => i.required = false);
        }
        updateTotal();
    }
});

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.remove-item');
    if (btn) {
        const row = btn.closest('.item-row');
        if (document.querySelectorAll('.item-row').length > 1) {
            row.remove();
            updateTotal();
        } else {
            alert('You must have at least one item in the quotation.');
            row.querySelectorAll('input:not([type="checkbox"])').forEach(input => input.value = '');
            updateTotal();
        }
    }
});

document.addEventListener('input', updateTotal);

function updateTotal() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const select = row.querySelector('.item-select');
        const qtyInput = row.querySelector('input[name*="[qty]"]');
        const manualPrice = row.querySelector('.manual-price');
        const option = select.options[select.selectedIndex];
        
        const qty = parseInt(qtyInput.value || 0);
        
        if (select.value === 'manual') {
            total += (parseFloat(manualPrice.value) || 0) * qty;
        } else if (option && option.dataset.price) {
            total += parseFloat(option.dataset.price) * qty;
        }
    });
    document.getElementById('grand-total').textContent = '₹' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>

<?php include 'includes/footer.php'; ?>
