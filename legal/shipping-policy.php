<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$page_title = 'Shipping Policy';
include '../includes/header.php';
?>

<main class="bg-[#F9F9F9] min-h-screen pt-6 pb-16">
    <div class="max-w-[1000px] mx-auto px-6">
        <div class="text-center mb-16">
            <span class="inline-block bg-white border border-[#EEEEEE] text-[#1A1A1A] text-[12px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest mb-6 shadow-sm">
                Logistics
            </span>
            <h1 class="text-[48px] font-black text-[#1A1A1A] tracking-tighter mb-4">Shipping <span class="text-[#888888]">Policy.</span></h1>
            <p class="text-[#888888] font-medium">Fast, reliable, and transparent delivery. Last Updated: <?php echo date('F d, Y'); ?></p>
        </div>

        <div class="bg-white rounded-[2rem] p-10 md:p-16 border border-[#EEEEEE] shadow-sm space-y-12">
            <section>
                <h2 class="text-[24px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-[#F9F9F9] flex items-center justify-center text-[14px]">01</span>
                    Processing Time
                </h2>
                <div class="text-[16px] text-[#666666] leading-relaxed space-y-4">
                    <p>All orders are processed within 1-2 business days. Orders are not shipped or delivered on weekends or holidays.</p>
                    <p>If we are experiencing a high volume of orders, shipments may be delayed by a few days. Please allow additional days in transit for delivery.</p>
                </div>
            </section>

            <section>
                <h2 class="text-[24px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-[#F9F9F9] flex items-center justify-center text-[14px]">02</span>
                    Shipping Rates & Estimates
                </h2>
                <div class="text-[16px] text-[#666666] leading-relaxed space-y-4">
                    <p>Shipping charges for your order will be calculated and displayed at checkout. We offer several delivery options depending on your location:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li><strong>Standard Delivery:</strong> 3-5 business days</li>
                        <li><strong>Express Delivery:</strong> 1-2 business days</li>
                        <li><strong>Store Pickup:</strong> Same day (where available)</li>
                    </ul>
                </div>
            </section>

            <section>
                <h2 class="text-[24px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-[#F9F9F9] flex items-center justify-center text-[14px]">03</span>
                    Shipment Confirmation & Tracking
                </h2>
                <div class="text-[16px] text-[#666666] leading-relaxed space-y-4">
                    <p>You will receive a Shipment Confirmation email once your order has shipped containing your tracking number(s). The tracking number will be active within 24 hours.</p>
                </div>
            </section>

            <section>
                <h2 class="text-[24px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-[#F9F9F9] flex items-center justify-center text-[14px]">04</span>
                    Damages & Returns
                </h2>
                <div class="text-[16px] text-[#666666] leading-relaxed space-y-4">
                    <p>ASO Online Market is not liable for any products damaged or lost during shipping. If you received your order damaged, please contact the shipment carrier to file a claim.</p>
                    <p>Please save all packaging materials and damaged goods before filing a claim.</p>
                </div>
            </section>
        </div>

        <div class="mt-12 text-center">
            <a href="../user/orders.php" class="inline-flex items-center gap-2 text-[14px] font-bold text-[#1A1A1A] hover:gap-4 transition-all">
                Track Your Order <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </a>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
