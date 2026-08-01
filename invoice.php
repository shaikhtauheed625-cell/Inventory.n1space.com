<?php
require_once 'db.php';
header('Content-Type: text/html; charset=utf-8');

if (!isset($_GET['id'])) {
    die("Invoice ID not specified.");
}

$saleId = $_GET['id'];

// Fetch Sale Info
$sStmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
$sStmt->execute([$saleId]);
$sale = $sStmt->fetch();

if (!$sale) {
    die("Sale not found.");
}

// Fetch Items
$iStmt = $pdo->prepare("SELECT si.*, 
                               COALESCE(p.name, si.manual_product_name) as product_name, 
                               COALESCE(v.variation_name, si.manual_variation_name) as variation_name 
                        FROM sale_items si 
                        LEFT JOIN variations v ON si.variation_id = v.id 
                        LEFT JOIN products p ON v.product_id = p.id 
                        WHERE si.sale_id = ?");
$iStmt->execute([$saleId]);
$items = $iStmt->fetchAll();

// Fetch Settings
$settings = [];
try {
    $setStmt = $pdo->query("SELECT * FROM settings");
    while ($row = $setStmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {}
include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/invoice.css?v=<?php echo time(); ?>">

<div class="invoice-page-body">
    <!-- Header Actions -->
    <div class="invoice-actions d-print-none">
        <a href="<?php echo $sale['type'] === 'Quotation' ? 'quotations.php' : 'view_sales.php'; ?>" class="btn btn-dark px-4 shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> <?php echo $sale['type'] === 'Quotation' ? 'Back to Quotations' : 'Back to Sales'; ?>
        </a>
        <div class="d-flex gap-3">
            <?php if (!empty($sale['customer_phone'])): 
                $phone = preg_replace('/[^0-9]/', '', $sale['customer_phone']);
                if (strlen($phone) < 10) $phone = '91' . $phone;
                
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                $invoiceUrl = "$protocol://$host" . dirname($_SERVER['PHP_SELF']) . "/invoice.php?id=" . $sale['id'];

                if ($sale['type'] === 'Quotation') {
                    $message = "Hello \u{1F60A}\n";
                    $message .= "Please find your quotation details below:\n\n";
                    $message .= "*Quotation:* #QT-" . str_pad($sale['id'], 5, '0', STR_PAD_LEFT) . "\n";
                    $message .= "*Amount:* \u{20B9}" . number_format($sale['total_amount'], 2) . "\n";
                    $message .= "*View Quotation:* " . $invoiceUrl . "\n\n";
                    $message .= "Please let us know if you would like to proceed.\n";
                    $message .= "Thank you! \u{1F49C}";
                } else {
                    $message = "Thank you for shopping with us \u{1F60A}\n";
                    $message .= "Please find your invoice details below:\n\n";
                    $message .= "*Order:* #INV-" . str_pad($sale['id'], 5, '0', STR_PAD_LEFT) . "\n";
                    $message .= "*Amount:* \u{20B9}" . number_format($sale['total_amount'], 2) . "\n";
                    $message .= "*View Invoice:* " . $invoiceUrl . "\n\n";
                    $message .= "If you are happy with our service, we would truly appreciate a \u{2B50}\u{2B50}\u{2B50}\u{2B50}\u{2B50} 5-star rating and your valuable feedback. Your support helps us grow and serve you even better.\n\n";
                    $message .= "Thank you for choosing us \u{1F49C}";
                }
                
                $waUrl = "https://wa.me/" . $phone . "?text=" . urlencode($message);
            ?>
            <a href="<?php echo $waUrl; ?>" target="_blank" class="btn btn-success px-4 shadow-sm">
                <i class="fab fa-whatsapp me-2"></i> Send via WhatsApp
            </a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-primary px-4 shadow-sm">
                <i class="fas fa-print me-2"></i> Print / PDF
            </button>
        </div>
    </div>

    <!-- A4 Paper Container -->
    <div class="invoice-a4-container">
        
        <div class="tagline-top">
            Delivering Quality, Building Trust.
        </div>

        <div class="invoice-header-container">
            <div class="invoice-header-bg-accent"></div>
            <div class="invoice-header-shape">
                <div class="invoice-header-content">
                    <div class="header-left">
                        <i class="fas fa-cube company-logo"></i>
                        <div>
                            <h1 class="company-name">N1 SOLUTION</h1>
                            <p class="company-tagline">Premium Inventory Solutions</p>
                        </div>
                    </div>
                    <div class="header-right">
                        <h2 class="doc-title"><?php echo $sale['type'] === 'Quotation' ? 'QUOTATION' : 'INVOICE'; ?></h2>
                        <p class="doc-meta">
                            <strong><?php echo $sale['type'] === 'Quotation' ? 'Quotation' : 'Invoice'; ?> #:</strong> 
                            <?php echo $sale['type'] === 'Quotation' ? 'QT' : 'INV'; ?>-<?php echo date('Y', strtotime($sale['sale_date'])); ?>-<?php echo str_pad($sale['id'], 3, '0', STR_PAD_LEFT); ?>
                        </p>
                        <p class="doc-meta">
                            <strong>Date:</strong> <?php echo date('d M Y', strtotime($sale['sale_date'])); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="address-section">
            <div class="address-block">
                <div class="address-title"><?php echo $sale['type'] === 'Quotation' ? 'FROM' : 'BILL FROM'; ?></div>
                <h4>N1 Solution</h4>
                <p>Sandhurst Road, In front J.J. Hospital Gate No. 9<br>
                Mumbai, Maharashtra, Pincode 400003</p>
                <p><i class="fas fa-phone-alt"></i> +91 96162 73393</p>
                <p><i class="fas fa-envelope"></i> support@n1shopping.com</p>
                <p><i class="fas fa-globe"></i> www.n1shopping.com</p>
            </div>
            
            <div class="address-block">
                <div class="address-title"><?php echo $sale['type'] === 'Quotation' ? 'TO' : 'BILL TO'; ?></div>
                <h4><?php echo htmlspecialchars($sale['customer_name']); ?></h4>
                <p>Retail Customer</p>
                <?php if (!empty($sale['customer_phone'])): ?>
                <p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($sale['customer_phone']); ?></p>
                <?php endif; ?>
                <p><i class="fas fa-envelope"></i> N/A</p>
            </div>
        </div>

        <div class="table-section">
            <table class="invoice-items-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">ITEM DESCRIPTION</th>
                        <th style="width: 15%;">QTY</th>
                        <th style="width: 20%;">UNIT PRICE</th>
                        <th style="width: 15%;"><?php echo $sale['type'] === 'Quotation' ? 'TOTAL' : 'AMOUNT'; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($item['product_name']); ?>
                            <?php if (!empty($item['variation_name'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($item['variation_name']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>₹ <?php echo number_format($item['unit_price'], 2); ?></td>
                        <td>₹ <?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="totals-section" style="position: relative;">
            <?php if ($sale['type'] === 'Sale' && $sale['payment_status'] === 'Paid'): ?>
            <div style="position: absolute; right: 320px; top: -10px; border: 5px solid #198754; color: #198754; font-size: 3rem; font-weight: 900; padding: 10px 40px; transform: rotate(-15deg); opacity: 0.7; letter-spacing: 5px; border-radius: 10px; text-transform: uppercase; pointer-events: none; z-index: 10;">
                PAID
            </div>
            <?php endif; ?>
            <?php
            $gstRate = isset($sale['gst_rate']) ? (float)$sale['gst_rate'] : 0;
            // Calculate subtotal and GST amount backward from the grand total
            $subtotal = $sale['total_amount'] / (1 + ($gstRate / 100));
            $gstAmount = $sale['total_amount'] - $subtotal;
            ?>
            <table class="totals-table">
                <tr>
                    <td>SUBTOTAL</td>
                    <td>₹ <?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <tr>
                    <td>GST (<?php echo rtrim(rtrim(number_format($gstRate, 2), '0'), '.'); ?>%)</td>
                    <td>₹ <?php echo number_format($gstAmount, 2); ?></td>
                </tr>
                <tr class="total-due">
                    <td><?php echo $sale['type'] === 'Quotation' ? 'TOTAL' : ($sale['payment_status'] === 'Paid' ? 'PAID TOTAL' : 'TOTAL DUE'); ?></td>
                    <td>₹ <?php echo number_format($sale['total_amount'], 2); ?></td>
                </tr>
            </table>
        </div>

        <div class="footer-sections">
            <?php 
            $terms_key = $sale['type'] === 'Quotation' ? 'quotation_terms' : 'invoice_terms';
            $terms = isset($settings[$terms_key]) && trim($settings[$terms_key]) !== '' ? explode("\n", trim($settings[$terms_key])) : [];
            if (!empty($terms)):
            ?>
            <div class="footer-block">
                <div class="footer-title">TERMS & CONDITIONS</div>
                <ul>
                    <?php 
                    foreach ($terms as $term) {
                        if (trim($term)) echo "<li>" . htmlspecialchars(trim($term)) . "</li>";
                    }
                    ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php 
            try {
                $dbServices = $pdo->query("SELECT title, url FROM services ORDER BY id DESC LIMIT 5")->fetchAll();
            } catch(Exception $e) { $dbServices = []; }
            
            if (!empty($dbServices)):
            ?>
            <div class="footer-block">
                <div class="footer-title">OUR WEBSITES & SERVICES</div>
                <ul>
                    <?php 
                    foreach ($dbServices as $s) {
                        echo "<li><strong>" . htmlspecialchars($s['title']) . "</strong> - <a href='" . htmlspecialchars($s['url']) . "' target='_blank' style='color:#00d4ff; text-decoration:none;'>" . htmlspecialchars(parse_url($s['url'], PHP_URL_HOST) ?: $s['url']) . "</a></li>";
                    }
                    ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="footer-block">
                <?php if ($sale['type'] === 'Quotation'): ?>
                <?php 
                $notes = isset($settings['quotation_notes']) ? explode("\n", trim($settings['quotation_notes'])) : [];
                if (!empty($notes) && trim(implode("", $notes)) !== ''):
                ?>
                <div class="footer-title">NOTES</div>
                <?php 
                foreach ($notes as $note) {
                    if (trim($note)) echo "<p>" . htmlspecialchars(trim($note)) . "</p>";
                }
                endif;
                ?>
                <?php else: ?>
                <div class="footer-title">PAYMENT DETAILS</div>
                <table style="width: 100%; border:none; margin-bottom: 15px; background: transparent;">
                    <tr><td style="width: 35%; padding-bottom: 4px; border:none; font-size: 9pt; font-weight: 600; text-align: left;">Bank Name</td><td style="padding-bottom: 4px; border:none; font-size: 9pt; text-align: left;">: HDFC Bank</td></tr>
                    <tr><td style="padding-bottom: 4px; border:none; font-size: 9pt; font-weight: 600; text-align: left;">A/c Name</td><td style="padding-bottom: 4px; border:none; font-size: 9pt; text-align: left;">: N1 Solution</td></tr>
                    <tr><td style="padding-bottom: 4px; border:none; font-size: 9pt; font-weight: 600; text-align: left;">A/c Number</td><td style="padding-bottom: 4px; border:none; font-size: 9pt; text-align: left;">: 1234 5678 9012 34</td></tr>
                    <tr><td style="padding-bottom: 4px; border:none; font-size: 9pt; font-weight: 600; text-align: left;">IFSC Code</td><td style="padding-bottom: 4px; border:none; font-size: 9pt; text-align: left;">: HDFC0001234</td></tr>
                </table>
                <div class="thank-you-title">THANK YOU!</div>
                <?php 
                $default_notes = ["We appreciate your business."];
                if ($sale['payment_status'] !== 'Paid') {
                    $default_notes[] = "Payment is due within 7 days from the invoice date.";
                }
                $inv_notes = isset($settings['invoice_notes']) && trim($settings['invoice_notes']) !== '' ? explode("\n", trim($settings['invoice_notes'])) : $default_notes;
                foreach ($inv_notes as $note) {
                    if (trim($note)) echo "<p style=\"margin-bottom: 2px;\">" . htmlspecialchars(trim($note)) . "</p>";
                }
                ?>
                <?php endif; ?>
            </div>
        </div>

        <div style="position: absolute; bottom: 50px; left: 0; width: 100%; text-align: center;">
            <p class="text-muted small mb-0" style="font-size: 8.5pt; font-style: italic;">This is a computer generated document. No signature is required.</p>
        </div>

        <div class="invoice-bottom-bar"></div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
