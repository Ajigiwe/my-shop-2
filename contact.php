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
$user_email = $_SESSION['user_email'] ?? '';
$user_phone = $_SESSION['user_phone'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - ASO Online Market</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .map-container {
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        .map-container:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .map-container iframe {
            display: block;
            width: 100% !important;
            height: 300px !important;
            border: none !important;
        }
        .contact-info-card {
            border-left: 4px solid #0d6efd;
        }
        .form-control:focus, .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h1 class="display-4 fw-bold mb-3">Contact Us</h1>
                    <p class="lead text-muted">Get in touch with our customer service team</p>
                </div>
            </div>
            
            <div class="row">
                <!-- Contact Information -->
                <div class="col-lg-4 order-lg-1">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Our Location</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="map-container">
                                <iframe 
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3970.3589870469786!2d-0.1868594852308864!3d5.611744995937145!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNcKwMzYnNDIuMyJOIDDCsDExJzEyLjciVw!5e0!3m2!1sen!2sgh!4v1640995200000" 
                                    allowfullscreen="" 
                                    loading="lazy">
                                </iframe>
                            </div>
                            <div class="p-3">
                                <p class="mb-2"><i class="fas fa-map-marker-alt me-2 text-primary"></i> 123 Business District, Accra, Ghana</p>
                                <p class="mb-2"><i class="fas fa-phone me-2 text-primary"></i> +233 20 123 4567</p>
                                <p class="mb-3"><i class="fas fa-envelope me-2 text-primary"></i> info@asoonlinemarket.com</p>
                                
                                <div class="d-grid gap-2">
                                    <button onclick="window.open('https://www.google.com/maps/dir//5.61175,-0.18686', '_blank')" 
                                            class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-directions me-1"></i> Get Directions
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Business Hours</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Monday - Friday</span>
                                    <span class="text-muted">8:00 AM - 6:00 PM</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Saturday</span>
                                    <span class="text-muted">9:00 AM - 4:00 PM</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Sunday</span>
                                    <span class="text-muted">Closed</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="col-lg-8 order-lg-2">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Send us a Message</h5>
                        </div>
                        <div class="card-body">
                            <div id="formMessages" class="mb-3"></div>
                            
                            <form id="contactForm" method="post" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($user_name); ?>" required>
                                        <div class="invalid-feedback">Please enter your name</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user_email); ?>" required>
                                        <div class="invalid-feedback">Please enter a valid email address</div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($user_phone); ?>">
                                    <div class="form-text">Optional, but recommended for faster response</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="" selected disabled>Select a subject</option>
                                        <option value="General Inquiry">General Inquiry</option>
                                        <option value="Product Question">Product Question</option>
                                        <option value="Order Status">Order Status</option>
                                        <option value="Returns & Exchanges">Returns & Exchanges</option>
                                        <option value="Wholesale Inquiries">Wholesale Inquiries</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a subject</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="message" class="form-label">Your Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="message" name="message" rows="5" minlength="10" required></textarea>
                                    <div class="form-text">Please provide detailed information about your inquiry (at least 10 characters)</div>
                                    <div class="invalid-feedback">Please enter a message (at least 10 characters)</div>
                                </div>
                                
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter" checked>
                                    <label class="form-check-label" for="newsletter">
                                        Subscribe to our newsletter for updates and promotions
                                    </label>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="reset" class="btn btn-outline-secondary me-md-2">
                                        <i class="fas fa-undo me-2"></i> Reset
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="fas fa-paper-plane me-2"></i> Send Message
                                        <span class="spinner-border spinner-border-sm d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Required Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="assets/js/script.js"></script>

    <script>
    $(document).ready(function() {
        // Initialize Bootstrap components
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize dropdowns
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });

        // Initialize navbar collapse
        var navbarCollapse = document.getElementById('navbarNav');
        if (navbarCollapse) {
            var bsCollapse = new bootstrap.Collapse(navbarCollapse, {
                toggle: false
            });
        }

        // Contact form handling
        const $form = $('#contactForm');
        if ($form.length) {
            $form.on('submit', function(e) {
                e.preventDefault();
                
                // Reset messages and form state
                const $formMessages = $('#formMessages');
                $formMessages.html('').addClass('d-none');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
                
                // Show loading state
                const $submitBtn = $('#submitBtn');
                const $submitSpinner = $('#submitSpinner');
                $submitBtn.prop('disabled', true);
                $submitSpinner.removeClass('d-none');

                // Submit form via AJAX
                $.ajax({
                    type: 'POST',
                    url: 'process_contact.php',
                    data: $form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        // Re-enable button
                        $submitBtn.prop('disabled', false);
                        $submitSpinner.addClass('d-none');
                        
                        if (response.success) {
                            // Redirect to success page
                            window.location.href = 'contact_success.php';
                            return;
                        } else {
                            // Show validation errors
                            let errorHtml = '<div class="alert alert-danger">' +
                                          '<i class="fas fa-exclamation-circle me-2"></i> ' +
                                          (response.message || 'Please correct the following errors:');
                            
                            if (response.errors) {
                                errorHtml += '<ul class="mt-2 mb-0">';
                                for (let field in response.errors) {
                                    let errorMsg = response.errors[field];
                                    errorHtml += '<li>' + errorMsg + '</li>';
                                    $('#' + field).addClass('is-invalid');
                                    $('#' + field).after('<div class="invalid-feedback">' + errorMsg + '</div>');
                                }
                                errorHtml += '</ul>';
                            }
                            
                            errorHtml += '</div>';
                            $formMessages.html(errorHtml).removeClass('d-none');
                            
                            // Scroll to messages
                            $('html, body').animate({
                                scrollTop: $formMessages.offset().top - 100
                            }, 500);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Re-enable button
                        $submitBtn.prop('disabled', false);
                        $submitSpinner.addClass('d-none');
                        
                        // Parse the error response if available
                        let errorMessage = 'An error occurred while sending your message. Please try again later.';
                        
                        try {
                            const response = xhr.responseText ? JSON.parse(xhr.responseText) : null;
                            if (response && response.message) {
                                errorMessage = response.message;
                            }
                        } catch (e) {
                            console.error('Error parsing error response:', e);
                        }
                        
                        // Show error message
                        $formMessages.html(
                            '<div class="alert alert-danger">' +
                            '<i class="fas fa-exclamation-circle me-2"></i> ' + errorMessage +
                            '</div>'
                        ).removeClass('d-none');
                        
                        // Scroll to messages
                        $('html, body').animate({
                            scrollTop: $formMessages.offset().top - 100
                        }, 500);
                        
                        console.error('AJAX Error:', status, error);
                    }
                });
            });
        }

        // Map functionality
        window.openFullscreenMap = function() {
            const mapUrl = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3970.3589870469786!2d-0.1868594852308864!3d5.611744995937145!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNcKwMzYnNDIuMyJOIDDCsDExJzEyLjciVw!5e0!3m2!1sen!2sgh!4v1640995200000';
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