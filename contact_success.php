<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$page_title = 'Message Sent Successfully';
include 'includes/header.php';
?>

<!-- Success Message Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center p-5">
                        <!-- Success Icon -->
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 rounded-circle" style="width: 100px; height: 100px;">
                                <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                            </div>
                        </div>
                        
                        <!-- Success Message -->
                        <h2 class="mb-3">Message Sent Successfully!</h2>
                        <p class="text-muted mb-4">Thank you for contacting us. We've received your message and will get back to you as soon as possible.</p>
                        
                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <a href="index.php" class="btn btn-primary px-4">
                                <i class="fas fa-home me-2"></i> Back to Home
                            </a>
                            <a href="contact.php" class="btn btn-outline-secondary px-4">
                                <i class="fas fa-envelope me-2"></i> Send Another Message
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<?php include 'includes/footer.php'; ?>
