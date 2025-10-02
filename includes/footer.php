<!-- Footer -->
<footer id="main-footer">
    <?php
    // Compute base path for assets (works from root, /admin, /user, /legal, etc.)
    $base = '';
    $current_path = $_SERVER['PHP_SELF'] ?? '';
    if (preg_match('/\/(admin|user|legal)\//', $current_path)) {
        $base = '../';
    }
    ?>
    <div class="container py-1">
        <div class="row g-2">
            <!-- Company info -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center mb-0">
                    <i class="fas fa-store me-1" style="font-size: 0.9rem;"></i>
                    <h5 class="mb-0" style="font-size: 0.9rem;">ASO Online Market</h5>
                </div>
                <p class="mb-1" style="font-size: 0.75rem; line-height: 1.2; opacity: 0.8;">Your trusted shopping destination</p>
                <div class="social-links d-flex gap-1">
                    <a href="#" title="Facebook" class="d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; border-radius: 50%; background: rgba(255,255,255,0.1); font-size: 0.8rem;">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" title="Twitter" class="d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; border-radius: 50%; background: rgba(255,255,255,0.1); font-size: 0.8rem;">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" title="Instagram" class="d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; border-radius: 50%; background: rgba(255,255,255,0.1); font-size: 0.8rem;">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>

            <!-- Main Navigation -->
            <div class="col-lg-4 col-md-6">
                <h6 class="mb-1" style="font-size: 0.85rem;">Navigation</h6>
                <ul class="list-unstyled">
                    <li class="mb-1">
                        <a href="<?php echo $base; ?>index.php" class="text-decoration-none d-flex align-items-center py-1 px-2 rounded" style="font-size: 0.85rem;">
                            <i class="fas fa-home me-1" style="width: 14px; text-align: center; font-size: 0.8rem;"></i>Home
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="<?php echo $base; ?>shop.php" class="text-decoration-none d-flex align-items-center py-1 px-2 rounded" style="font-size: 0.85rem;">
                            <i class="fas fa-shopping-bag me-1" style="width: 14px; text-align: center; font-size: 0.8rem;"></i>Shop
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="<?php echo $base; ?>about.php" class="text-decoration-none d-flex align-items-center py-1 px-2 rounded" style="font-size: 0.85rem;">
                            <i class="fas fa-info-circle me-1" style="width: 14px; text-align: center; font-size: 0.8rem;"></i>About Us
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="<?php echo $base; ?>contact.php" class="text-decoration-none d-flex align-items-center py-1 px-2 rounded" style="font-size: 0.85rem;">
                            <i class="fas fa-phone me-1" style="width: 14px; text-align: center; font-size: 0.8rem;"></i>Contact
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Legal & Support -->
            <div class="col-lg-4 col-md-6">
                <h6 class="mb-1" style="font-size: 0.85rem;">Legal & Support</h6>
                <ul class="list-unstyled">
                    <li class="mb-1">
                        <a href="<?php echo $base; ?>legal/terms-conditions.php" class="text-decoration-none d-flex align-items-center py-1 px-2 rounded" style="font-size: 0.8rem;">
                            <i class="fas fa-file-contract me-1" style="width: 14px; text-align: center; font-size: 0.75rem;"></i>Terms & Conditions
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="<?php echo $base; ?>legal/privacy-policy.php" class="text-decoration-none d-flex align-items-center py-1 px-2 rounded" style="font-size: 0.8rem;">
                            <i class="fas fa-shield-alt me-1" style="width: 14px; text-align: center; font-size: 0.75rem;"></i>Privacy Policy
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="<?php echo $base; ?>legal/refund-returns.php" class="text-decoration-none d-flex align-items-center py-1 px-2 rounded" style="font-size: 0.8rem;">
                            <i class="fas fa-undo me-1" style="width: 14px; text-align: center; font-size: 0.75rem;"></i>Refund & Returns
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="<?php echo $base; ?>legal/shipping-policy.php" class="text-decoration-none d-flex align-items-center py-1 px-2 rounded" style="font-size: 0.8rem;">
                            <i class="fas fa-truck me-1" style="width: 14px; text-align: center; font-size: 0.75rem;"></i>Shipping Policy
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="<?php echo $base; ?>contact.php" class="text-decoration-none d-flex align-items-center py-1 px-2 rounded" style="font-size: 0.8rem;">
                            <i class="fas fa-headset me-1" style="width: 14px; text-align: center; font-size: 0.75rem;"></i>Contact Support
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Copyright -->
    <div class="footer-bottom py-1" style="font-size: 0.7rem; border-top: 1px solid rgba(255,255,255,0.1);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <p class="mb-0" style="opacity: 0.7;">&copy; <?php echo date('Y'); ?> ASO Online Market. All rights reserved.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="<?php echo $base; ?>legal/privacy-policy.php" class="text-decoration-none me-2" style="font-size: 0.7rem; opacity: 0.7;">Privacy</a>
                    <a href="<?php echo $base; ?>legal/terms-conditions.php" class="text-decoration-none" style="font-size: 0.7rem; opacity: 0.7;">Terms</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Floating WhatsApp Button -->
<a href="https://wa.me/233123456789?text=Hello! I need help with my order on ASO Online Market."
   target="_blank"
   class="whatsapp-float whatsapp-pulse"
   title="Chat with us on WhatsApp"
   aria-label="Contact us on WhatsApp">
</a>

<style>
/* Footer specific styles - ensure single instance */
footer:not(#main-footer) {
    display: none !important;
}

footer#main-footer {
    display: block !important;
}

/* Additional safety measures */
body > footer:not(:last-of-type) {
    display: none !important;
}

footer {
    background: inherit; /* Inherit the gradient from CSS */
    color: inherit; /* Inherit white text color from CSS */
}

footer h5 {
    color: inherit; /* Inherit white color from CSS */
}

footer .social-links a:hover {
    background-color: rgba(255, 255, 255, 0.1);
    transition: background-color 0.2s ease;
}

footer .container {
    max-width: 100%;
    width: 100%;
}

footer .row {
    margin: 0;
}

footer [class*="col-"] {
    padding-left: 15px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    footer .container {
        padding: 0.75rem 0 0.25rem;
        padding-right: 1rem;
    }

    footer [class*="col-"] {
        margin-top: 2rem;
    }

    footer h5 {
        font-size: 0.8rem;
    }

    footer p {
        font-size: 0.7rem;
    }
}
</style>

<!-- Custom JS - Only load on pages that need it -->
<?php
// Don't load script.js on certain pages to avoid conflicts
$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$pages_without_script_js = ['form_test.php'];

if (!in_array($current_page, $pages_without_script_js)):
?>
<script src="<?php echo $base; ?>assets/js/script.js"></script>
<?php endif; ?>
