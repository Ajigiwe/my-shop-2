<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$page_title = 'Terms & Conditions';
include '../includes/header.php';
?>

<style>
    .policy-body { max-width: 800px; margin: 0 auto; color: var(--mid-gray); line-height: 1.8; font-size: 15px; }
    .policy-body h2 { color: var(--ink); font-family: var(--f-display); font-size: 24px; font-weight: 800; margin-bottom: 24px; }
    .policy-body p { margin-bottom: 32px; }
</style>

<section class="page-hero" style="background: var(--ink); padding: 100px 0 60px; text-align: center; color: #fff;">
    <div class="container">
        <h1 style="font-family: var(--f-display); font-size: 56px; font-weight: 900; margin-bottom: 16px;">TERMS & CONDITIONS</h1>
        <p style="font-family: var(--f-mono); font-size: 13px; font-weight: 700; opacity: 0.6; letter-spacing: 0.1em; text-transform: uppercase;">Last Updated: <?php echo date('F d, Y'); ?></p>
    </div>
</section>

<section class="page-content" style="padding: 80px 0;">
    <div class="container">
        <div class="policy-body">
            <h2>1. Agreement to Terms</h2>
            <p>Welcome to ASO Online Market. By accessing or using our website, you agree to be bound by these Terms and Conditions and our Privacy Policy. If you do not agree to all of these terms, do not use this website.</p>
            <p>These Terms apply to all visitors, users, and others who access or use the Service.</p>

            <h2>2. Purchases & Payment</h2>
            <p>If you wish to purchase any product or service made available through the Service, you may be asked to supply certain information relevant to your Purchase including, without limitation, your credit card number, the expiration date of your credit card, your billing address, and your shipping information.</p>
            <p>You represent and warrant that: (i) you have the legal right to use any credit card(s) or other payment method(s) in connection with any Purchase; and that (ii) the information you supply to us is true, correct and complete.</p>

            <h2>3. Account Security</h2>
            <p>When you create an account with us, you must provide us information that is accurate, complete, and current at all times. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account on our Service.</p>
            <p>You are responsible for safeguarding the password that you use to access the Service and for any activities or actions under your password.</p>

            <h2>4. Intellectual Property</h2>
            <p>The Service and its original content, features and functionality are and will remain the exclusive property of ASO Online Market and its licensors. The Service is protected by copyright, trademark, and other laws.</p>

            <h2>5. Limitation of Liability</h2>
            <p>In no event shall ASO Online Market, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses.</p>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
