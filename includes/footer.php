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
    <div class="container py-4">
        <div class="row">
            <!-- Company info -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-store fa-2x me-3"></i>
                    <h5 class="mb-0">ASO Online Market</h5>
                </div>
                <p class="mb-4">Your trusted online shopping destination offering quality products at competitive prices with excellent customer service.</p>
                <div class="social-links d-flex gap-3">
                    <a href="#" title="Facebook">
                        <i class="fab fa-facebook-f fa-lg"></i>
                    </a>
                    <a href="#" title="Twitter">
                        <i class="fab fa-twitter fa-lg"></i>
                    </a>
                    <a href="#" title="Instagram">
                        <i class="fab fa-instagram fa-lg"></i>
                    </a>
                    <a href="#" title="LinkedIn">
                        <i class="fab fa-linkedin-in fa-lg"></i>
                    </a>
                </div>
            </div>

            <!-- Main Navigation -->
            <div class="col-lg-4 col-md-6 mb-4">
                <h6 class="mb-4">Navigation</h6>
                <ul class="list-unstyled">
                    <li class="mb-3">
                        <a href="<?php echo $base; ?>index.php" class="text-decoration-none d-flex align-items-center py-2 px-3 rounded">
                            <i class="fas fa-home me-3"></i>Home
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo $base; ?>shop.php" class="text-decoration-none d-flex align-items-center py-2 px-3 rounded">
                            <i class="fas fa-shopping-bag me-3"></i>Shop
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo $base; ?>about.php" class="text-decoration-none d-flex align-items-center py-2 px-3 rounded">
                            <i class="fas fa-info-circle me-3"></i>About Us
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo $base; ?>contact.php" class="text-decoration-none d-flex align-items-center py-2 px-3 rounded">
                            <i class="fas fa-phone me-3"></i>Contact
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Legal & Support -->
            <div class="col-lg-4 col-md-6 mb-4">
                <h6 class="mb-4">Legal & Support</h6>
                <ul class="list-unstyled">
                    <li class="mb-3">
                        <a href="<?php echo $base; ?>legal/terms-conditions.php" class="text-decoration-none d-flex align-items-center py-2 px-3 rounded">
                            <i class="fas fa-file-contract me-3"></i>Terms & Conditions
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo $base; ?>legal/privacy-policy.php" class="text-decoration-none d-flex align-items-center py-2 px-3 rounded">
                            <i class="fas fa-shield-alt me-3"></i>Privacy Policy
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo $base; ?>legal/faq.php" class="text-decoration-none d-flex align-items-center py-2 px-3 rounded">
                            <i class="fas fa-question-circle me-3"></i>FAQ
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo $base; ?>legal/shipping-policy.php" class="text-decoration-none d-flex align-items-center py-2 px-3 rounded">
                            <i class="fas fa-truck me-3"></i>Shipping Info
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Copyright -->
    <div class="footer-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> ASO Online Market. All rights reserved.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="<?php echo $base; ?>legal/privacy-policy.php" class="text-decoration-none me-3">Privacy</a>
                    <a href="<?php echo $base; ?>legal/terms-conditions.php" class="text-decoration-none">Terms</a>
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
    padding-right: 15px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    footer .container {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    footer [class*="col-"] {
        margin-bottom: 1.5rem;
    }
}
</style>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?php echo $base; ?>assets/js/script.js"></script>
