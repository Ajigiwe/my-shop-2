<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$page_title = 'Refund & Returns Policy | ASO Online Market';
include '../includes/header.php';
?>

<style>
    .policy-body { max-width: 800px; margin: 0 auto; color: var(--mid-gray); line-height: 1.8; font-size: 15px; }
    .policy-body h2 { color: var(--ink); font-family: var(--f-display); font-size: 24px; font-weight: 800; margin-bottom: 24px; }
    .policy-body p { margin-bottom: 24px; }
    .policy-body ul { margin-bottom: 24px; padding-left: 20px; }
    .policy-body li { margin-bottom: 12px; }
    .step-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 40px; }
    .step-box { background: var(--off); border-radius: 12px; padding: 24px; text-align: center; }
    .step-num { font-family: var(--f-mono); font-size: 11px; font-weight: 800; color: var(--red); letter-spacing: 0.1em; margin-bottom: 8px; }
    .step-box h6 { font-family: var(--f-display); font-weight: 800; color: var(--ink); margin-bottom: 8px; }
    .step-box p { font-size: 13px; margin: 0; }

    @media (max-width: 640px) {
        .step-row { grid-template-columns: 1fr; }
    }
</style>

<section class="page-hero" style="background: var(--ink); padding: 100px 0 60px; text-align: center; color: #fff;">
    <div class="container">
        <h1 style="font-family: var(--f-display); font-size: 56px; font-weight: 900; margin-bottom: 16px;">REFUND & RETURNS</h1>
        <p style="font-family: var(--f-mono); font-size: 13px; font-weight: 700; opacity: 0.6; letter-spacing: 0.1em; text-transform: uppercase;">Simple, Fair, Transparent. Last Updated: <?php echo date('F d, Y'); ?></p>
    </div>
</section>

<section class="page-content" style="padding: 80px 0;">
    <div class="container">
        <div class="policy-body">
            <h2>1. Return Window</h2>
            <p style="background: rgba(82,196,26,0.08); border-left: 4px solid #52C41A; padding: 16px 20px; border-radius: 4px;"><strong>30-Day Return Policy:</strong> You have 30 days from the date of delivery to return eligible items. The period begins on the day you receive your order.</p>
            <p><strong>Exceptions:</strong></p>
            <ul>
                <li>Perishable items (food, flowers) — 7 days</li>
                <li>Personalized or customized items — No returns</li>
                <li>Items damaged due to misuse — No returns</li>
            </ul>

            <h2>2. Eligible Items</h2>
            <p>Most items purchased from ASO Online Market are eligible for return, provided they meet these conditions:</p>
            <ul>
                <li>Item must be in original packaging</li>
                <li>All tags and labels must be attached</li>
                <li>Item must not be used or damaged</li>
                <li>Original receipt or order confirmation required</li>
                <li>Within the 30-day return window</li>
            </ul>

            <h2>3. Non-Returnable Items</h2>
            <ul>
                <li>Perishable food items, fresh flowers and plants</li>
                <li>Personalized products and digital downloads</li>
                <li>Undergarments, swimwear, jewelry and watches</li>
                <li>Items damaged by misuse, opened software or media</li>
            </ul>

            <h2>4. Return Process</h2>
            <div class="step-row">
                <div class="step-box">
                    <div class="step-num">STEP 01</div>
                    <h6>Contact Us</h6>
                    <p>Call or email within 30 days.</p>
                </div>
                <div class="step-box">
                    <div class="step-num">STEP 02</div>
                    <h6>Get Instructions</h6>
                    <p>We'll provide return instructions and label.</p>
                </div>
                <div class="step-box">
                    <div class="step-num">STEP 03</div>
                    <h6>Receive Refund</h6>
                    <p>7-10 business days processing.</p>
                </div>
            </div>

            <h2>5. Refund Information</h2>
            <p><strong>Processing Time:</strong> Refunds are processed within 7-10 business days after we receive your return.</p>
            <p><strong>Refund Methods:</strong> Original payment method, store credit (faster processing), or exchange for a different item.</p>
            <p><strong>Shipping Fees:</strong> Original shipping charges are non-refundable unless the return is due to our error. You are responsible for return shipping costs unless we provide a prepaid label.</p>

            <h2>6. Need to Make a Return?</h2>
            <p>Contact our customer service team to initiate a return or for any questions — <a href="../contact.php" style="color: var(--red); font-weight: 700;">Contact Us</a>, email <a href="mailto:returns@asoonlinemarket.com" style="color: var(--red); font-weight: 700;">returns@asoonlinemarket.com</a>, or see our <a href="faq.php" style="color: var(--red); font-weight: 700;">Return FAQ</a>.</p>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
