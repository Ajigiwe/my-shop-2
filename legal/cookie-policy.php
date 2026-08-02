<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$page_title = 'Cookie Policy | ASO Online Market';
include '../includes/header.php';
?>

<style>
    .policy-body { max-width: 800px; margin: 0 auto; color: var(--mid-gray); line-height: 1.8; font-size: 15px; }
    .policy-body h2 { color: var(--ink); font-family: var(--f-display); font-size: 24px; font-weight: 800; margin-bottom: 24px; }
    .policy-body p { margin-bottom: 24px; }
    .policy-body ul { margin-bottom: 24px; padding-left: 20px; }
    .policy-body li { margin-bottom: 12px; }
</style>

<section class="page-hero" style="background: var(--ink); padding: 100px 0 60px; text-align: center; color: #fff;">
    <div class="container">
        <h1 style="font-family: var(--f-display); font-size: 56px; font-weight: 900; margin-bottom: 16px;">COOKIE POLICY</h1>
        <p style="font-family: var(--f-mono); font-size: 13px; font-weight: 700; opacity: 0.6; letter-spacing: 0.1em; text-transform: uppercase;">Transparent About Your Data. Last Updated: <?php echo date('F d, Y'); ?></p>
    </div>
</section>

<section class="page-content" style="padding: 80px 0;">
    <div class="container">
        <div class="policy-body">
            <h2>1. What are Cookies?</h2>
            <p>Cookies are small text files that are placed on your device when you visit our website. They help us provide you with a better browsing experience by remembering your preferences and settings, keeping you logged in to your account, analyzing website traffic and usage patterns, and improving website functionality and performance.</p>

            <h2>2. Types of Cookies We Use</h2>
            <p><strong>Essential Cookies</strong> — required for basic website functionality: session management, shopping cart functionality, and security features.</p>
            <p><strong>Analytics Cookies</strong> — help us understand how visitors use our site: traffic analysis, user behavior tracking, and geographic data.</p>

            <h2>3. Managing Cookies</h2>
            <p>Most web browsers allow you to control cookies through their settings. You can typically view what cookies are stored, delete existing cookies, block future cookies, and receive notifications about cookies.</p>
            <p style="background: rgba(250,173,20,0.1); border-left: 4px solid #FAAD14; padding: 16px 20px; border-radius: 4px;"><strong>Note:</strong> Disabling certain cookies may affect website functionality.</p>

            <h2>4. Third-Party Cookies</h2>
            <p>We may use third-party services that place cookies on your device:</p>
            <ul>
                <li><strong>Google Analytics:</strong> for website traffic analysis</li>
                <li><strong>Payment Processors:</strong> for secure transaction handling (Paystack)</li>
                <li><strong>Shipping Providers:</strong> for delivery tracking</li>
            </ul>
            <p>These third parties have their own privacy policies and cookie practices.</p>

            <h2>5. Updates to This Policy</h2>
            <p>We may update this Cookie Policy from time to time to reflect changes in our practices or for legal reasons. We'll notify you of any significant changes by posting the updated policy on our website.</p>

            <h2>6. Questions About Cookies?</h2>
            <p>Contact us if you have any questions about our use of cookies or this policy — <a href="../contact.php" style="color: var(--red); font-weight: 700;">Contact Us</a> or email <a href="mailto:privacy@asoonlinemarket.com" style="color: var(--red); font-weight: 700;">privacy@asoonlinemarket.com</a>.</p>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
