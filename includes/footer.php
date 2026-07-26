    </div> <!-- End container -->
    <footer class="mt-5 py-4 text-center text-muted border-top border-secondary border-opacity-10 d-print-none">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> N1 Solution. All rights reserved.</p>
        </div>
    </footer>
    <!-- Floating Quick Action Button -->
    <div class="position-fixed bottom-0 end-0 p-4 d-print-none" style="z-index: 1050;">
        <div class="dropdown dropup">
            <button class="btn btn-primary rounded-circle shadow-lg d-flex align-items-center justify-content-center" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 60px; height: 60px; font-size: 24px; transition: transform 0.2s;">
                <i class="fas fa-plus"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-dark shadow-lg border-secondary mb-2 rounded-3">
                <li><a class="dropdown-item py-2" href="add_sale.php"><i class="fas fa-shopping-cart me-2 text-info"></i> New Sale</a></li>
                <?php if (hasPermission('can_manage_products')): ?>
                <li><a class="dropdown-item py-2" href="add_product.php"><i class="fas fa-box me-2 text-success"></i> New Product</a></li>
                <?php endif; ?>
                <?php if (hasPermission('can_manage_stock')): ?>
                <li><a class="dropdown-item py-2" href="stock_in.php"><i class="fas fa-truck-loading me-2 text-warning"></i> Receive Stock</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add subtle rotation animation to the floating button when clicked
        const fab = document.querySelector('.dropup > .btn');
        if (fab) {
            fab.addEventListener('click', function() {
                this.classList.toggle('rotate-45');
            });
        }
    </script>
</body>
</html>
