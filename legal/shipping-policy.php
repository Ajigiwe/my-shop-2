<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$page_title = 'Shipping Policy';
require_once '../includes/db.php';
$settings = loadSiteSettings($pdo);
$sh_zones = getShippingZones($pdo);
$domestic_zones = array_values(array_filter($sh_zones, function($z) { return ($z['zone_type'] ?? 'domestic') === 'domestic'; }));
$intl_zones = array_values(array_filter($sh_zones, function($z) { return ($z['zone_type'] ?? 'domestic') === 'international'; }));
$threshold = number_format((float)($settings['free_shipping_threshold'] ?? 500), 0);
include '../includes/header.php';
?>

<style>
    .policy-body { max-width: 800px; margin: 0 auto; color: var(--mid-gray); line-height: 1.8; font-size: 15px; }
    .policy-body h2 { color: var(--ink); font-family: var(--f-display); font-size: 24px; font-weight: 800; margin-bottom: 24px; }
    .policy-body p { margin-bottom: 32px; }
    .policy-body ul { margin-bottom: 32px; padding-left: 20px; }
    .policy-body li { margin-bottom: 12px; }
    .zone-row { display: flex; justify-content: space-between; border-bottom: 1px solid var(--light-gray); padding: 14px 0; font-size: 15px; }
    .zone-row span:last-child { font-weight: 800; color: var(--ink); }
    .free-banner { background: rgba(229,0,26,0.05); border: 1px solid var(--red); padding: 24px; border-radius: 4px; margin-bottom: 32px; }
</style>

<section class="page-hero" style="background: var(--ink); padding: 100px 0 60px; text-align: center; color: #fff;">
    <div class="container">
        <h1 style="font-family: var(--f-display); font-size: 56px; font-weight: 900; margin-bottom: 16px;">SHIPPING POLICY</h1>
        <p style="font-family: var(--f-mono); font-size: 13px; font-weight: 700; opacity: 0.6; letter-spacing: 0.1em; text-transform: uppercase;">Fast, Reliable & Transparent. Last Updated: <?php echo date('F d, Y'); ?></p>
    </div>
</section>

<section class="page-content" style="padding: 80px 0;">
    <div class="container">
        <div class="policy-body">
            <div class="free-banner">
                <p style="font-size: 16px; font-weight: 800; color: var(--red); margin-bottom: 4px;">FREE delivery on all orders above GH₵<?php echo $threshold; ?></p>
            </div>

            <h2>1. Processing Time</h2>
            <p>All orders are processed and fulfilled within 1-2 business days (Monday to Friday). Orders are not shipped or delivered on weekends or holidays. If we are experiencing a high volume of orders, shipments may be delayed by a few days.</p>

            <h2>2. Delivery Zones & Fees</h2>
            <p>For orders below GH₵<?php echo $threshold; ?>, delivery fees apply based on your region:</p>
            <div>
                <?php foreach ($domestic_zones as $z): ?>
                    <?php $flag = htmlspecialchars($z['flag_emoji'] ?? '📍');
                          $name = htmlspecialchars($z['zone_name']);
                          $days = htmlspecialchars($z['estimated_days'] ?? '');
                          $rate = (float)$z['flat_rate'];
                          $free = !empty($z['free_threshold']) ? (float)$z['free_threshold'] : null;
                          $eff = ($free !== null && 0 >= $free) ? 0 : $rate; // show standard rate
                    ?>
                    <div class="zone-row"><span><?php echo $flag; ?> <?php echo $name; ?> <?php echo $days ? '(' . $days . ')' : ''; ?></span><span>GH₵<?php echo number_format($rate, 0); ?></span></div>
                <?php endforeach; ?>
            </div>

            <h2>2b. International Delivery</h2>
            <p>We ship our <a href="../local.php" style="color: var(--red); font-weight: 700;">Made in Ghana</a> goods internationally. Flat-rate international delivery is charged in Ghanaian Cedis (GHS) and paid securely via <strong>Paystack</strong>. You'll choose your country at checkout:</p>
            <div>
                <?php foreach ($intl_zones as $z): ?>
                    <?php $flag = htmlspecialchars($z['flag_emoji'] ?? '🌍');
                          ?>
                    <div class="zone-row"><span><?php echo $flag; ?> <?php echo htmlspecialchars($z['zone_name']); ?> (<?php echo htmlspecialchars($z['estimated_days'] ?? 'Worldwide'); ?>)</span><span>GH₵<?php echo number_format((float)$z['flat_rate'], 0); ?></span></div>
                <?php endforeach; ?>
            </div>
            <p>Please note: customs duties, taxes or import fees at the destination country are the responsibility of the recipient.</p>

            <h2>3. Shipment Confirmation & Tracking</h2>
            <p>You will receive a Shipment Confirmation email once your order has shipped containing your tracking number(s). The tracking number will be active within 24 hours. You can also <a href="../track-order.php" style="color: var(--red); font-weight: 700;">track your order</a> any time using your Order ID and email or phone.</p>

            <h2>4. Important Information</h2>
            <ul>
                <li>All orders are delivered through trusted courier services.</li>
                <li>Delivery is strictly door-to-door (no P.O. Box addresses allowed).</li>
                <li>Please save all packaging materials and damaged goods before filing a claim.</li>
            </ul>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
