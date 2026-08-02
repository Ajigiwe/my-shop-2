<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$page_title = 'Payment Methods | ASO Online Market';
include '../includes/header.php';
?>

<style>
    .policy-body { max-width: 800px; margin: 0 auto; color: var(--mid-gray); line-height: 1.8; font-size: 15px; }
    .policy-body h2 { color: var(--ink); font-family: var(--f-display); font-size: 24px; font-weight: 800; margin-bottom: 24px; }
    .policy-body p { margin-bottom: 24px; }
    .policy-body ul { margin-bottom: 24px; padding-left: 20px; }
    .policy-body li { margin-bottom: 12px; }
    .pay-method-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 40px; }
    .pay-card { border: 1px solid var(--light-gray); border-radius: 12px; padding: 20px 24px; }
    .pay-card strong { color: var(--ink); font-family: var(--f-display); }
    .pay-card small { color: var(--mid-gray); }
    .step-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 40px; }
    .step-box { background: var(--off); border-radius: 12px; padding: 24px; text-align: center; }
    .step-num { font-family: var(--f-mono); font-size: 11px; font-weight: 800; color: var(--red); letter-spacing: 0.1em; margin-bottom: 8px; }
    .step-box h6 { font-family: var(--f-display); font-weight: 800; color: var(--ink); margin-bottom: 8px; }
    .step-box p { font-size: 13px; margin: 0; }

    @media (max-width: 640px) {
        .pay-method-grid, .step-row { grid-template-columns: 1fr; }
    }
</style>

<section class="page-hero" style="background: var(--ink); padding: 100px 0 60px; text-align: center; color: #fff;">
    <div class="container">
        <h1 style="font-family: var(--f-display); font-size: 56px; font-weight: 900; margin-bottom: 16px;">PAYMENT METHODS</h1>
        <p style="font-family: var(--f-mono); font-size: 13px; font-weight: 700; opacity: 0.6; letter-spacing: 0.1em; text-transform: uppercase;">Secure Payment Options. Last Updated: <?php echo date('F d, Y'); ?></p>
    </div>
</section>

<section class="page-content" style="padding: 80px 0;">
    <div class="container">
        <div class="policy-body">
            <h2>1. Accepted Payment Methods</h2>
            <p style="background: rgba(82,196,26,0.08); border-left: 4px solid #52C41A; padding: 16px 20px; border-radius: 4px;"><strong>Secure Payments:</strong> All transactions are encrypted and processed through secure payment gateways (Paystack, cards & mobile money).</p>

            <div class="pay-method-grid">
                <div class="pay-card">
                    <strong>Credit & Debit Cards</strong><br>
                    <small>Visa and Mastercard accepted — all cards supported.</small>
                </div>
                <div class="pay-card">
                    <strong>Mobile Money</strong><br>
                    <small>Pay with MTN Mobile Money or Vodafone Cash via Paystack.</small>
                </div>
            </div>

            <h2>2. Payment Security</h2>
            <p>Your payment information is protected by industry-leading security measures:</p>
            <ul>
                <li>SSL/TLS encryption on all connections</li>
                <li>End-to-end encryption of payment data</li>
                <li>PCI DSS compliant payment processing</li>
                <li>Secure servers and fraud protection</li>
            </ul>

            <h2>3. Payment Process</h2>
            <div class="step-row">
                <div class="step-box">
                    <div class="step-num">STEP 01</div>
                    <h6>Add to Cart</h6>
                    <p>Select items and proceed to checkout.</p>
                </div>
                <div class="step-box">
                    <div class="step-num">STEP 02</div>
                    <h6>Enter Details</h6>
                    <p>Provide shipping and payment info.</p>
                </div>
                <div class="step-box">
                    <div class="step-num">STEP 03</div>
                    <h6>Complete Payment</h6>
                    <p>Secure payment processing.</p>
                </div>
            </div>

            <h2>4. Payment Issues</h2>
            <ul>
                <li><strong>Card Declined:</strong> Check card details, limits, and that 3D Secure is available for international cards.</li>
                <li><strong>Mobile Money:</strong> Ensure sufficient balance in your wallet.</li>
                <li><strong>Bank Transfer:</strong> Verify the account details before sending.</li>
            </ul>

            <h2>5. Billing & Receipts</h2>
            <p>After successful payment, you'll receive an email confirmation with an order invoice and itemized receipt, including the payment method used, transaction date, and transaction ID.</p>

            <h2>6. Payment Questions?</h2>
            <p>Contact our support team for questions about payment methods, transactions, or billing issues — <a href="../contact.php" style="color: var(--red); font-weight: 700;">Contact Us</a> or email <a href="mailto:payments@asoonlinemarket.com" style="color: var(--red); font-weight: 700;">payments@asoonlinemarket.com</a>.</p>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
