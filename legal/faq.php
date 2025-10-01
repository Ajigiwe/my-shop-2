<?php
$page_title = 'FAQ | ASO Online Market';
?>

<?php include '../includes/header.php'; ?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Page Header -->
            <div class="text-center mb-5">
                <h1 class="display-5 fw-bold text-primary mb-3">Frequently Asked Questions</h1>
                <p class="lead text-muted">Find answers to common questions about our products and services.</p>
            </div>

            <!-- FAQ Content -->
            <div class="legal-content">
                <!-- Account & Registration -->
                <section class="mb-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h2 class="h4 mb-0"><i class="fas fa-user-circle me-2"></i>Account & Registration</h2>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="accountAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="accountHeading1">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#accountCollapse1">
                                            How do I create an account?
                                        </button>
                                    </h2>
                                    <div id="accountCollapse1" class="accordion-collapse collapse show" data-bs-parent="#accountAccordion">
                                        <div class="accordion-body">
                                            Click on the "Register" link in the top navigation, fill in your details including name, email, and password, then click "Create Account". You'll receive a confirmation email to verify your account.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="accountHeading2">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accountCollapse2">
                                            I forgot my password. How can I reset it?
                                        </button>
                                    </h2>
                                    <div id="accountCollapse2" class="accordion-collapse collapse" data-bs-parent="#accountAccordion">
                                        <div class="accordion-body">
                                            Click on "Forgot Password" on the login page, enter your email address, and we'll send you a password reset link. Follow the instructions in the email to create a new password.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Shopping & Orders -->
                <section class="mb-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h2 class="h4 mb-0"><i class="fas fa-shopping-cart me-2"></i>Shopping & Orders</h2>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="shoppingAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="shoppingHeading1">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#shoppingCollapse1">
                                            How do I place an order?
                                        </button>
                                    </h2>
                                    <div id="shoppingCollapse1" class="accordion-collapse collapse show" data-bs-parent="#shoppingAccordion">
                                        <div class="accordion-body">
                                            Browse our products, add items to your cart, proceed to checkout, enter your shipping and payment information, and complete your order. You'll receive an order confirmation email.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="shoppingHeading2">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#shoppingCollapse2">
                                            Can I modify or cancel my order?
                                        </button>
                                    </h2>
                                    <div id="shoppingCollapse2" class="accordion-collapse collapse" data-bs-parent="#shoppingAccordion">
                                        <div class="accordion-body">
                                            Orders can be modified or cancelled within 1 hour of placement if they haven't been processed yet. Contact our customer service team immediately for assistance.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="shoppingHeading3">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#shoppingCollapse3">
                                            How can I track my order?
                                        </button>
                                    </h2>
                                    <div id="shoppingCollapse3" class="accordion-collapse collapse" data-bs-parent="#shoppingAccordion">
                                        <div class="accordion-body">
                                            Once your order ships, you'll receive a tracking number via email. You can also check your order status in your account dashboard or contact customer service.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Payment & Pricing -->
                <section class="mb-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h2 class="h4 mb-0"><i class="fas fa-credit-card me-2"></i>Payment & Pricing</h2>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="paymentAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="paymentHeading1">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#paymentCollapse1">
                                            What payment methods do you accept?
                                        </button>
                                    </h2>
                                    <div id="paymentCollapse1" class="accordion-collapse collapse show" data-bs-parent="#paymentAccordion">
                                        <div class="accordion-body">
                                            We accept various payment methods including credit/debit cards, mobile money, and bank transfers. All payments are processed securely through our payment partners.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="paymentHeading2">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paymentCollapse2">
                                            Are prices inclusive of taxes?
                                        </button>
                                    </h2>
                                    <div id="paymentCollapse2" class="accordion-collapse collapse" data-bs-parent="#paymentAccordion">
                                        <div class="accordion-body">
                                            All prices displayed on our website are inclusive of applicable taxes. The final amount you'll pay is exactly what's shown during checkout.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Shipping & Delivery -->
                <section class="mb-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-warning text-dark">
                            <h2 class="h4 mb-0"><i class="fas fa-truck me-2"></i>Shipping & Delivery</h2>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="shippingAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="shippingHeading1">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#shippingCollapse1">
                                            How much does shipping cost?
                                        </button>
                                    </h2>
                                    <div id="shippingCollapse1" class="accordion-collapse collapse show" data-bs-parent="#shippingAccordion">
                                        <div class="accordion-body">
                                            Shipping is free for orders over GH₵50. For orders below this amount, a flat shipping fee of GH₵10 applies. Express delivery options are available for an additional fee.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="shippingHeading2">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#shippingCollapse2">
                                            How long does delivery take?
                                        </button>
                                    </h2>
                                    <div id="shippingCollapse2" class="accordion-collapse collapse" data-bs-parent="#shippingAccordion">
                                        <div class="accordion-body">
                                            Standard delivery takes 2-5 business days within Accra and 3-7 business days for other regions. Express delivery (1-2 business days) is available for an additional fee.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Returns & Refunds -->
                <section class="mb-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-danger text-white">
                            <h2 class="h4 mb-0"><i class="fas fa-undo me-2"></i>Returns & Refunds</h2>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="returnsAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="returnsHeading1">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#returnsCollapse1">
                                            What is your return policy?
                                        </button>
                                    </h2>
                                    <div id="returnsCollapse1" class="accordion-collapse collapse show" data-bs-parent="#returnsAccordion">
                                        <div class="accordion-body">
                                            We offer a 30-day return policy for most items. Items must be in original condition with tags attached. Some items like perishable goods may not be returnable.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="returnsHeading2">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#returnsCollapse2">
                                            How do I initiate a return?
                                        </button>
                                    </h2>
                                    <div id="returnsCollapse2" class="accordion-collapse collapse" data-bs-parent="#returnsAccordion">
                                        <div class="accordion-body">
                                            Contact our customer service team within 30 days of delivery with your order number. We'll provide a return shipping label and instructions for sending back the item.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Contact Support -->
                <section class="mb-5">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h3 class="h5 mb-0"><i class="fas fa-headset me-2"></i>Still Need Help?</h3>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">Can't find the answer you're looking for? Our customer support team is here to help.</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <a href="mailto:support@asomarket.com" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-envelope me-2"></i>Email Support
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="tel:+233548624107" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-phone me-2"></i>Call Us
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="../contact.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-comments me-2"></i>Contact Form
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
/* Legal page specific styles */
.legal-content .card {
    margin-bottom: 1.5rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
}

.legal-content .card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.legal-content .card-header {
    border-radius: var(--radius) var(--radius) 0 0 !important;
    border-bottom: none;
    padding: 1rem 1.5rem;
    font-family: var(--font-serif);
    font-weight: 600;
}

.legal-content .card-header.bg-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)) !important;
    color: var(--white) !important;
}

.legal-content .card-header.bg-success {
    background: linear-gradient(135deg, var(--success-color), #059669) !important;
    color: var(--white) !important;
}

.legal-content .card-header.bg-info {
    background: linear-gradient(135deg, var(--info-color), #0891b2) !important;
    color: var(--white) !important;
}

.legal-content .card-header.bg-warning {
    background: linear-gradient(135deg, var(--warning-color), #d97706) !important;
    color: var(--gray-900) !important;
}

.legal-content .card-body {
    padding: 1.5rem;
    color: var(--gray-800);
    font-family: var(--font-sans);
    line-height: 1.6;
}

.legal-content ul li {
    padding: 0.25rem 0;
    color: var(--gray-700);
}

.legal-content .alert {
    border-radius: var(--radius-sm);
    border: none;
    padding: 1rem;
}

.legal-content .alert-info {
    background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(6, 182, 212, 0.05));
    border-left: 4px solid var(--info-color);
    color: var(--info-color);
}

.legal-content .alert-warning {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));
    border-left: 4px solid var(--warning-color);
    color: var(--warning-color);
}

.legal-content h1, .legal-content h2, .legal-content h3, .legal-content h4, .legal-content h5, .legal-content h6 {
    font-family: var(--font-serif);
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: 1rem;
}

.legal-content p {
    color: var(--gray-700);
    margin-bottom: 1rem;
}

.legal-content .btn {
    border-radius: var(--radius-lg);
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    font-size: 0.9rem;
    transition: var(--transition);
    font-family: var(--font-sans);
    min-height: 44px;
}

.legal-content .btn-outline-primary {
    border-color: var(--primary-color);
    color: var(--primary-color);
    background: transparent;
    border-width: 2px;
}

.legal-content .btn-outline-primary:hover {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: var(--white);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .legal-content .card-body {
        padding: 1rem;
    }

    .legal-content .card-header {
        padding: 0.75rem 1rem;
    }

    .legal-content h2 {
        font-size: 1.25rem;
    }
}
</style>

<?php include '../includes/footer.php'; ?>

