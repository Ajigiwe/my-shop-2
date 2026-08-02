<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$page_title = 'Warranty Policy';
include 'includes/header.php';
?>

<style>
    .warranty-grid {
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 80px;
        margin-top: 80px;
    }
    .warranty-claim-item { padding: 40px; background: var(--ink); color: #fff; border-radius: 4px; box-shadow: 0 20px 60px rgba(0,0,0,0.1); }

    @media (max-width: 1024px) {
        .warranty-grid { grid-template-columns: 1fr; gap: 64px; margin-top: 60px; }
        .hero-heading { font-size: 48px !important; }
    }
</style>

<main style="padding-top: 120px; padding-bottom: 120px;">
    <div class="container">
        <div class="sec-head reveal">
            <div>
                <div class="sec-over" style="margin-bottom: 12px; font-family: var(--f-mono); font-size: 10px; letter-spacing: 0.2em; color: var(--red); text-transform: uppercase;">Protection</div>
                <h1 class="hero-heading" style="color: var(--ink); font-size: clamp(40px, 8vw, 72px); line-height: 0.85; margin: 0; font-family: var(--f-display); font-weight: 900; letter-spacing: -0.04em;">WARRANTY<br>POLICY.</h1>
            </div>
        </div>

        <div class="reveal rd1 warranty-grid">
            <div>
                <p style="font-size: 20px; line-height: 1.6; color: var(--ink); margin-bottom: 40px; font-weight: 400;">
                    At ASO Online Market, we stand behind the quality of every product we sell. Our warranty is designed to give you total peace of mind. Last Updated: <?php echo date('F d, Y'); ?>
                </p>

                <div style="display: flex; flex-direction: column; gap: 56px;">
                    <div>
                        <div style="font-family: var(--f-display); font-size: 14px; color: var(--red); margin-bottom: 8px;">12-MONTH STANDARD</div>
                        <h3 style="font-family: var(--f-display); font-size: 32px; font-weight: 700; margin-bottom: 12px; line-height: 1;">What Is Covered</h3>
                        <p style="color: var(--mid-gray); font-size: 15px; line-height: 1.7;">All electronics and hardware come with a 12-month manufacturer warranty from the date of delivery. Coverage includes manufacturing defects in materials and workmanship under normal use:</p>
                        <ul style="margin-top: 16px; padding-left: 20px; color: var(--mid-gray); font-size: 14px; line-height: 2;">
                            <li>Hardware failures (screen, battery, ports, chipsets) on electronics</li>
                            <li>Faulty components that fail without physical damage</li>
                            <li>Items that arrive dead on arrival (DOA) — 30-day swap, no questions asked</li>
                        </ul>
                    </div>

                    <div>
                        <div style="font-family: var(--f-display); font-size: 14px; color: var(--red); margin-bottom: 8px;">NOT COVERED</div>
                        <h3 style="font-family: var(--f-display); font-size: 32px; font-weight: 700; margin-bottom: 12px; line-height: 1;">What Is Not Covered</h3>
                        <ul style="padding-left: 20px; color: var(--mid-gray); font-size: 14px; line-height: 2;">
                            <li>Physical damage, accidental drops, water or liquid damage</li>
                            <li>Unauthorized repairs or modifications</li>
                            <li>Normal wear and tear, cosmetic scratches, or fading</li>
                            <li>Batteries and consumables (beyond 6 months from delivery)</li>
                            <li>Loss, theft, or damages caused by misuse</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 56px;">
                <div>
                    <div style="font-family: var(--f-display); font-size: 14px; color: var(--red); margin-bottom: 8px;">30-DAY SWAP</div>
                    <h3 style="font-family: var(--f-display); font-size: 32px; font-weight: 700; margin-bottom: 12px; line-height: 1;">Zero Debate</h3>
                    <p style="color: var(--mid-gray); font-size: 15px; line-height: 1.7;">Found a defect within 30 days? We swap your item for a new one — no stories, no fine print. We know tech fails, we fix it.</p>
                </div>

                <div>
                    <div style="font-family: var(--f-display); font-size: 14px; color: var(--red); margin-bottom: 8px;">LIFETIME HELP</div>
                    <h3 style="font-family: var(--f-display); font-size: 32px; font-weight: 700; margin-bottom: 12px; line-height: 1;">How To Claim</h3>
                    <p style="color: var(--mid-gray); font-size: 15px; line-height: 1.7;">To start a warranty claim, contact our support team with your order number and a description of the issue:</p>
                    <ul style="margin-top: 16px; padding-left: 20px; color: var(--mid-gray); font-size: 14px; line-height: 2;">
                        <li>Email <a href="mailto:info@asoonlinemarket.com" style="color: var(--red); font-weight: 700;">info@asoonlinemarket.com</a> with your order number</li>
                        <li>Include clear photos or a short video of the fault</li>
                        <li>We respond within 1-2 business days with next steps</li>
                        <li>Approved claims are repaired, replaced, or refunded</li>
                    </ul>
                </div>

                <div class="warranty-claim-item">
                    <p style="font-family: var(--f-semi); font-weight: 700; text-transform: uppercase; font-size: 11px; letter-spacing: .15em; color: var(--red); margin-bottom: 16px;">Fast Tracking:</p>
                    <p style="font-size: 16px; opacity: 0.9; line-height: 1.7; font-weight: 300;">WhatsApp us with your order number and a video of the issue. We aim to respond and provide a resolution path in under 2 hours.</p>
                    <a href="track-order.php" style="display: inline-block; margin-top: 24px; border-bottom: 2px solid var(--red); font-weight: 800; font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em; color: #fff;">Track Your Order</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
