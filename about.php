<?php
// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$page_title = 'About Us';

// Check if user is logged in (for navbar display)
$user_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
$user_role = $_SESSION['user_role'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - ASO Online Market</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Other CSS and JS includes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<!-- About Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h1 class="display-4 fw-bold mb-3">About ASO Online Market</h1>
                <p class="lead text-muted">Your trusted partner in quality online shopping</p>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <h3>Our Story</h3>
                <p class="mb-4">
                    ASO Online Market was founded with a simple mission: to provide customers with high-quality products
                    at competitive prices while delivering exceptional customer service. Since our inception, we've grown
                    from a small startup to become one of the leading e-commerce platforms in the region.
                </p>
                <p class="mb-4">
                    We believe that online shopping should be convenient, reliable, and enjoyable. That's why we've built
                    our platform with cutting-edge technology and a customer-first approach that puts your needs at the center
                    of everything we do.
                </p>
            </div>

            <div class="col-lg-6">
                <h3>Our Mission</h3>
                <p class="mb-4">
                    To democratize access to quality products by providing a seamless online shopping experience that
                    combines convenience, reliability, and value. We're committed to:
                </p>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Offering high-quality products at fair prices</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Providing exceptional customer service</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Maintaining secure and reliable transactions</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Supporting local businesses and communities</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Continuously improving our platform and services</li>
                </ul>
            </div>
        </div>

        <!-- Values Section -->
        <div class="row mt-5">
            <div class="col-12 text-center mb-4">
                <h2>Our Values</h2>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 text-center mb-4">
                <div class="mb-3">
                    <i class="fas fa-heart fa-3x text-primary"></i>
                </div>
                <h4>Customer First</h4>
                <p>We prioritize our customers' needs and satisfaction above all else. Every decision we make is guided by what's best for our customers.</p>
            </div>

            <div class="col-md-4 text-center mb-4">
                <div class="mb-3">
                    <i class="fas fa-shield-alt fa-3x text-primary"></i>
                </div>
                <h4>Trust & Security</h4>
                <p>We maintain the highest standards of security and privacy protection. Your trust is our most valuable asset.</p>
            </div>

            <div class="col-md-4 text-center mb-4">
                <div class="mb-3">
                    <i class="fas fa-star fa-3x text-primary"></i>
                </div>
                <h4>Quality Excellence</h4>
                <p>We partner with trusted suppliers and manufacturers to ensure that every product meets our strict quality standards.</p>
            </div>
        </div>

        <!-- Welcome Message for Logged-in Users -->
        <?php if ($user_logged_in): ?>
        <div class="row mt-5">
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-user-check me-2"></i>
                    Welcome back, <?php echo htmlspecialchars($user_name); ?>!
                    Thank you for choosing ASO Online Market for your shopping needs.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contact CTA -->
        <div class="row mt-5">
            <div class="col-12 text-center">
                <div class="card bg-light">
                    <div class="card-body py-4">
                        <h3>Get in Touch</h3>
                        <p class="mb-4">Have questions? We're here to help!</p>
                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <a href="contact.php" class="btn btn-primary">
                                <i class="fas fa-envelope me-2"></i>Contact Us
                            </a>
                            <a href="shop.php" class="btn btn-outline-primary">
                                <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<!-- Bootstrap JS (loaded at end for better performance) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>

<script>
// Initialize Bootstrap dropdowns explicitly
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });

    // Also ensure navbar collapse works
    var navbarCollapse = document.getElementById('navbarNav');
    if (navbarCollapse) {
        var bsCollapse = new bootstrap.Collapse(navbarCollapse, {
            toggle: false
        });
    }

    // Animate value icons on scroll
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Animate value cards
    document.querySelectorAll('.col-md-4.text-center').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
});
</script>
</body>
</html>
