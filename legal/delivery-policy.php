<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$page_title = 'Delivery Policy | ASO Online Market';
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
        <h1 style="font-family: var(--f-display); font-size: 56px; font-weight: 900; margin-bottom: 16px;">DELIVERY POLICY</h1>
        <p style="font-family: var(--f-mono); font-size: 13px; font-weight: 700; opacity: 0.6; letter-spacing: 0.1em; text-transform: uppercase;">Door-To-Door, Everywhere In Ghana. Last Updated: <?php echo date('F d, Y'); ?></p>
    </div>
</section>

<section class="page-content" style="padding: 80px 0;">
    <div class="container">
        <div class="policy-body">
            <h2>1. Service Areas</h2>
            <p style="background: rgba(82,196,26,0.08); border-left: 4px solid #52C41A; padding: 16px 20px; border-radius: 4px;"><strong>Pan-Ghana Delivery:</strong> We deliver to all regions and districts across Ghana. We also ship <a href="../local.php" style="color: var(--red); font-weight: 700;">Made in Ghana</a> goods internationally — choose your country at checkout and pay in GHS via Paystack.</p>
            <ul>
                <li><strong>Greater Accra Region</strong> — Accra Metropolitan, Tema Municipality, Ga East/West Districts. Fastest delivery area.</li>
                <li><strong>Other Regions</strong> — Ashanti Region, Western Region, Central Region, and all remaining regions (standard delivery timeframes).</li>
            </ul>

            <h2>2. Delivery Requirements</h2>
            <p><strong>Address:</strong> Provide a complete street address with house/flat number, city/town and district, a valid postal code if applicable, and clear landmarks for hard-to-find locations.</p>
            <p><strong>Contact:</strong> A valid phone number for delivery coordination, an email for delivery notifications, and an alternative contact person if needed.</p>

            <h2>3. Delivery Procedures</h2>
            <div class="step-row">
                <div class="step-box">
                    <div class="step-num">STEP 01</div>
                    <h6>Order Processing</h6>
                    <p>1-2 business days.</p>
                </div>
                <div class="step-box">
                    <div class="step-num">STEP 02</div>
                    <h6>Order Shipment</h6>
                    <p>Tracking number provided.</p>
                </div>
                <div class="step-box">
                    <div class="step-num">STEP 03</div>
                    <h6>Delivery Confirmation</h6>
                    <p>Signature may be required.</p>
                </div>
            </div>

            <h2>4. Failed Deliveries</h2>
            <p>If we're unable to deliver your order, we'll contact you to reschedule delivery, leave a notice with collection instructions, and hold your package for 7 days.</p>
            <p><strong>Re-delivery options:</strong> free re-delivery within the service area, self-collection at our pickup points, or return to sender (additional charges may apply).</p>

            <h2>5. Special Deliveries</h2>
            <p><strong>Same-Day Delivery:</strong> Available in Accra for orders placed before 12 PM — delivery by 6 PM.</p>
            <p><strong>Weekend Delivery:</strong> Saturday delivery available in Accra & Tema only.</p>

            <h2>6. Delivery Questions?</h2>
            <p>Contact our delivery team for questions about delivery schedules, special arrangements, or delivery issues — <a href="../contact.php" style="color: var(--red); font-weight: 700;">Contact Us</a> or email <a href="mailto:delivery@asoonlinemarket.com" style="color: var(--red); font-weight: 700;">delivery@asoonlinemarket.com</a>.</p>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
