<?php
// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$page_title = 'Contact Us';

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

    <!-- Contact Page Styles -->
    <style>
        /* Map Styling */
        .map-container {
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            margin-bottom: 1rem;
        }

        .map-container:hover {
            box-shadow: var(--shadow-md);
        }

        .map-container iframe {
            display: block;
            width: 100% !important;
            height: 300px !important;
            border: none !important;
        }

        /* Contact page specific styles */
        .contact-info-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .contact-info-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Map buttons styling */
        .map-buttons .btn {
            border-radius: var(--radius);
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }

        /* Mobile responsive adjustments */
        @media (max-width: 768px) {
            .map-container iframe {
                height: 250px !important;
            }

            .map-buttons {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .map-buttons .btn {
                width: 100%;
                margin: 0;
            }
        }
    </style>
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
                        <?php if ($user_logged_in): ?>
                            <!-- Pre-fill form for logged-in users -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Welcome back, <?php echo htmlspecialchars($user_name); ?>! We've pre-filled some of your information.
                            </div>
                        <?php endif; ?>

                        <form id="contactForm" method="POST" action="process_contact.php">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="name" name="name"
                                           value="<?php echo $user_logged_in ? htmlspecialchars($user_name) : ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo $user_logged_in ? htmlspecialchars($_SESSION['user_email'] ?? '') : ''; ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                           value="<?php echo $user_logged_in ? htmlspecialchars($_SESSION['user_phone'] ?? '') : ''; ?>">
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
                                <textarea class="form-control" id="message" name="message" rows="5"
                                          placeholder="Please describe your inquiry in detail..." required></textarea>
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
                <div class="card mb-4 contact-info-card">
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

                <!-- Interactive Map -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i>Find Us</h5>
                    </div>
                    <div class="card-body">
                        <!-- Google Maps Embed -->
                        <div class="map-container" style="position: relative; overflow: hidden; width: 100%; height: 300px; border-radius: var(--radius);">
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3970.974!2d-0.186957!3d5.603738!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNcKwMzYnMTMuNCJOIDDCsDExJzEyLjciVw!5e0!3m2!1sen!2sgh!4v1640995200000!5m2!1sen!2sgh"
                                width="100%"
                                height="300"
                                style="border: 0; border-radius: var(--radius);"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                        <div class="mt-3 map-buttons">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="openFullscreenMap()">
                                <i class="fas fa-expand me-1"></i>View Larger Map
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm ms-2" onclick="getDirections()">
                                <i class="fas fa-directions me-1"></i>Get Directions
                            </button>
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

    // Contact form handling
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            submitBtn.disabled = true;

            // Collect form data
            const formData = new FormData(this);

            // Send AJAX request
            fetch('ajax/contact.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Message sent successfully! We\'ll get back to you soon.', 'success');
                    contactForm.reset();
                } else {
                    showToast(data.message || 'Error sending message. Please try again.', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error sending message. Please try again.', 'danger');
            })
            .finally(() => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }

    // Map functionality
    window.openFullscreenMap = function() {
        const mapUrl = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3970.974!2d-0.186957!3d5.603738!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNcKwMzYnMTMuNCJOIDDCsDExJzEyLjciVw!5e0!3m2!1sen!2sgh!4v1640995200000!5m2!1sen!2sgh';
        window.open(mapUrl, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
    };

    window.getDirections = function() {
        const destination = encodeURIComponent('123 Business District, Accra, Ghana');
        const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${destination}`;
        window.open(googleMapsUrl, '_blank');
    };
});
</script>
</body>
</html>
