<?php
$page_title = 'Payment Methods | ASO Online Market';
?>

<?php include '../includes/header.php'; ?>



<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Page Header -->
            <div class="text-center mb-5">
                <h1 class="display-5 fw-bold text-[#1A1A1A] mb-3">Payment Methods</h1>
                <p class="lead text-muted">Learn about our secure payment options and how to complete your purchase.</p>
                <div class="alert alert-info border-0">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <strong>Last Updated:</strong> January 2024
                </div>
            </div>

            <!-- Content Sections -->
            <div class="legal-content">
                <!-- Accepted Payment Methods -->
                <section class="mb-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h2 class="h4 mb-0"><i class="fas fa-credit-card me-2"></i>Accepted Payment Methods</h2>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success border-0">
                                <i class="fas fa-shield-alt me-2"></i>
                                <strong>Secure Payments:</strong> All transactions are encrypted and processed through secure payment gateways.
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Credit & Debit Cards</h6>
                                    <div class="payment-methods">
                                        <div class="payment-item d-flex align-items-center mb-3">
                                            <i class="fab fa-cc-visa fa-2x text-[#1A1A1A] me-3"></i>
                                            <div>
                                                <strong>Visa</strong>
                                                <br><small class="text-muted">All Visa cards accepted</small>
                                            </div>
                                        </div>
                                        <div class="payment-item d-flex align-items-center mb-3">
                                            <i class="fab fa-cc-mastercard fa-2x text-danger me-3"></i>
                                            <div>
                                                <strong>Mastercard</strong>
                                                <br><small class="text-muted">All Mastercard cards accepted</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Mobile Money</h6>
                                    <div class="payment-methods">
                                        <div class="payment-item d-flex align-items-center mb-3">
                                            <i class="fas fa-mobile-alt fa-2x text-success me-3"></i>
                                            <div>
                                                <strong>MTN Mobile Money</strong>
                                                <br><small class="text-muted">Pay with MTN MoMo</small>
                                            </div>
                                        </div>
                                        <div class="payment-item d-flex align-items-center mb-3">
                                            <i class="fas fa-mobile-alt fa-2x text-warning me-3"></i>
                                            <div>
                                                <strong>Vodafone Cash</strong>
                                                <br><small class="text-muted">Pay with Vodafone Cash</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Payment Security -->
                <section class="mb-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h2 class="h4 mb-0"><i class="fas fa-lock me-2"></i>Payment Security</h2>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">Your payment information is protected by industry-leading security measures:</p>

                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Encryption</h6>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><i class="fas fa-shield-alt text-success me-2"></i>SSL/TLS encryption</li>
                                        <li class="mb-2"><i class="fas fa-key text-success me-2"></i>End-to-end encryption</li>
                                        <li class="mb-2"><i class="fas fa-server text-success me-2"></i>Secure servers</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Compliance</h6>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><i class="fas fa-certificate text-success me-2"></i>PCI DSS compliant</li>
                                        <li class="mb-2"><i class="fas fa-user-shield text-success me-2"></i>GDPR compliant</li>
                                        <li class="mb-2"><i class="fas fa-balance-scale text-success me-2"></i>Local regulations</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Payment Process -->
                <section class="mb-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h2 class="h4 mb-0"><i class="fas fa-credit-card me-2"></i>Payment Process</h2>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center mb-3">
                                    <div class="bg-light p-3 rounded h-100">
                                        <div class="text-[#1A1A1A] mb-2">
                                            <i class="fas fa-shopping-cart fa-2x"></i>
                                        </div>
                                        <h6 class="fw-bold">1. Add to Cart</h6>
                                        <p class="small text-muted mb-0">Select items and proceed to checkout</p>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center mb-3">
                                    <div class="bg-light p-3 rounded h-100">
                                        <div class="text-[#1A1A1A] mb-2">
                                            <i class="fas fa-address-card fa-2x"></i>
                                        </div>
                                        <h6 class="fw-bold">2. Enter Details</h6>
                                        <p class="small text-muted mb-0">Provide shipping and payment info</p>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center mb-3">
                                    <div class="bg-light p-3 rounded h-100">
                                        <div class="text-[#1A1A1A] mb-2">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                        <h6 class="fw-bold">3. Complete Payment</h6>
                                        <p class="small text-muted mb-0">Secure payment processing</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Payment Issues -->
                <section class="mb-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-warning text-dark">
                            <h2 class="h4 mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Payment Issues</h2>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">If you encounter payment issues:</p>

                            <h6 class="fw-bold mb-2">Common Solutions:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-credit-card text-warning me-2"></i><strong>Card Declined:</strong> Check card details and limits</li>
                                <li class="mb-2"><i class="fas fa-mobile-alt text-warning me-2"></i><strong>Mobile Money:</strong> Ensure sufficient balance</li>
                                <li class="mb-2"><i class="fas fa-bank text-warning me-2"></i><strong>Bank Transfer:</strong> Verify account details</li>
                                <li class="mb-2"><i class="fas fa-globe text-warning me-2"></i><strong>International Cards:</strong> May require 3D Secure</li>
                            </ul>

                            <div class="alert alert-info border-0 mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Need Help?</strong> Contact our payment support team for assistance with payment issues.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Billing & Receipts -->
                <section class="mb-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h2 class="h4 mb-0"><i class="fas fa-receipt me-2"></i>Billing & Receipts</h2>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">After successful payment, you'll receive:</p>

                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Immediate Confirmation</h6>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><i class="fas fa-envelope text-[#1A1A1A] me-2"></i>Email confirmation</li>
                                        <li class="mb-2"><i class="fas fa-file-invoice text-[#1A1A1A] me-2"></i>Order invoice</li>
                                        <li class="mb-2"><i class="fas fa-list text-[#1A1A1A] me-2"></i>Itemized receipt</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Payment Details</h6>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><i class="fas fa-credit-card text-[#1A1A1A] me-2"></i>Payment method used</li>
                                        <li class="mb-2"><i class="fas fa-calendar text-[#1A1A1A] me-2"></i>Transaction date</li>
                                        <li class="mb-2"><i class="fas fa-hashtag text-[#1A1A1A] me-2"></i>Transaction ID</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Contact Section -->
                <section class="mb-5">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h3 class="h5 mb-0"><i class="fas fa-question-circle me-2"></i>Payment Questions?</h3>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">Contact our payment support team for questions about payment methods, transactions, or billing issues.</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <a href="mailto:payments@asomarket.com" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-envelope me-2"></i>Email: payments@asomarket.com
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="tel:+233548624107" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-phone me-2"></i>Phone: (+233) 548 624 107
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
}

.legal-content .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.legal-content .card-header {
    border-radius: 0.75rem 0.75rem 0 0 !important;
    border-bottom: none;
    padding: 1rem 1.5rem;
}

.legal-content .card-body {
    padding: 1.5rem;
}

.legal-content ul li {
    padding: 0.25rem 0;
}

.legal-content .alert {
    border-radius: 0.5rem;
}

.payment-item {
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.payment-item:hover {
    background-color: #f8f9fa;
    border-color: var(--primary-color);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .legal-content .card-body {
        padding: 1rem;
    }

    .legal-content .card-header {
        padding: 0.75rem 1rem;
    }

    .payment-item {
        margin-bottom: 1rem;
    }
}
</style>

<?php include '../includes/footer.php'; ?>

