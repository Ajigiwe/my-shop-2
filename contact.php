<?php
$page_title = 'Contact Us';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - ASO Online Market</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Other CSS and JS includes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<!-- Contact Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h1 class="display-4 fw-bold mb-3">Contact Us</h1>
                <p class="lead text-muted">Get in touch with our customer service team</p>
            </div>
        </div>

        <div class="row">
            <!-- Contact Form -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Send us a Message</h5>
                    </div>
                    <div class="card-body">
                        <form>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="subject" class="form-label">Subject *</label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="">Select Subject</option>
                                        <option value="general">General Inquiry</option>
                                        <option value="support">Customer Support</option>
                                        <option value="orders">Orders & Delivery</option>
                                        <option value="returns">Returns & Refunds</option>
                                        <option value="complaints">Complaints</option>
                                        <option value="partnership">Partnership</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label">Message *</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter">
                                    <label class="form-check-label" for="newsletter">
                                        Subscribe to our newsletter for updates and promotions
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="col-lg-4">
                <!-- Contact Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-map-marker-alt text-primary me-3"></i>
                            <div>
                                <strong>Address:</strong><br>
                                123 Business District<br>
                                Accra, Ghana
                            </div>
                        </div>

                        <div class="mb-3">
                            <i class="fas fa-phone text-primary me-3"></i>
                            <div>
                                <strong>Phone:</strong><br>
                                +233 20 123 4567<br>
                                +233 20 765 4321
                            </div>
                        </div>

                        <div class="mb-3">
                            <i class="fas fa-envelope text-primary me-3"></i>
                            <div>
                                <strong>Email:</strong><br>
                                support@asoonlinemarket.com<br>
                                info@asoonlinemarket.com
                            </div>
                        </div>

                        <div class="mb-3">
                            <i class="fas fa-clock text-primary me-3"></i>
                            <div>
                                <strong>Business Hours:</strong><br>
                                Monday - Friday: 8:00 AM - 6:00 PM<br>
                                Saturday: 9:00 AM - 4:00 PM<br>
                                Sunday: Closed
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ Link -->
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-question-circle fa-2x text-primary mb-3"></i>
                        <h6>Need Quick Help?</h6>
                        <p class="text-muted mb-3">Check our FAQ section for common questions and answers.</p>
                        <a href="legal/faq.php" class="btn btn-outline-primary">
                            <i class="fas fa-book me-2"></i>View FAQ
                        </a>
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
});
</script>
</body>
</html>
