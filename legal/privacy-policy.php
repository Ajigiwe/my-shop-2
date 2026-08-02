<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$page_title = 'Privacy Policy';
include '../includes/header.php';
?>

<style>
    .policy-body { max-width: 800px; margin: 0 auto; color: var(--mid-gray); line-height: 1.8; font-size: 15px; }
    .policy-body h2 { color: var(--ink); font-family: var(--f-display); font-size: 24px; font-weight: 800; margin-bottom: 24px; }
    .policy-body p { margin-bottom: 32px; }
    .policy-body ul { margin-bottom: 32px; padding-left: 20px; }
    .policy-body li { margin-bottom: 12px; }
</style>

<section class="page-hero" style="background: var(--ink); padding: 100px 0 60px; text-align: center; color: #fff;">
    <div class="container">
        <h1 style="font-family: var(--f-display); font-size: 56px; font-weight: 900; margin-bottom: 16px;">PRIVACY POLICY</h1>
        <p style="font-family: var(--f-mono); font-size: 13px; font-weight: 700; opacity: 0.6; letter-spacing: 0.1em; text-transform: uppercase;">Your Privacy Is Our Priority. Last Updated: <?php echo date('F d, Y'); ?></p>
    </div>
</section>

<section class="page-content" style="padding: 80px 0;">
    <div class="container">
        <div class="policy-body">
            <h2>1. Information We Collect</h2>
            <p>We collect information you provide directly to us when you create an account, place an order, or contact us for support. This may include your name, email address, phone number, shipping address, and payment information.</p>
            <p>We also automatically collect certain information when you visit our site, such as your IP address, browser type, and how you interact with our pages.</p>

            <h2>2. How We Use Information</h2>
            <p>We use the information we collect to:</p>
            <ul>
                <li>Process and fulfill your orders</li>
                <li>Communicate with you about your account or transactions</li>
                <li>Send you marketing communications (if you've opted in)</li>
                <li>Improve our website and customer service</li>
                <li>Protect against fraudulent or illegal activity</li>
            </ul>

            <h2>3. Data Sharing & Security</h2>
            <p>We do not sell your personal information to third parties. We only share information with service providers who help us operate our business (e.g., payment processors and shipping carriers).</p>
            <p>We implement industry-standard security measures to protect your data, but no method of transmission over the internet is 100% secure.</p>

            <h2>4. Your Rights</h2>
            <p>You have the right to access, update, or delete your personal information at any time through your account settings. You can also opt-out of marketing emails by following the unsubscribe link in any message.</p>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
