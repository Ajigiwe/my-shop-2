<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$page_title = 'Terms & Conditions';
include '../includes/header.php';
?>

<main class="bg-[#F9F9F9] min-h-screen py-24">
    <div class="max-w-[1000px] mx-auto px-6">
        <div class="text-center mb-16">
            <span class="inline-block bg-white border border-[#EEEEEE] text-[#1A1A1A] text-[12px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest mb-6 shadow-sm">
                Legal
            </span>
            <h1 class="text-[48px] font-black text-[#1A1A1A] tracking-tighter mb-4">Terms & <span class="text-[#888888]">Conditions.</span></h1>
            <p class="text-[#888888] font-medium">Last Updated: <?php echo date('F d, Y'); ?></p>
        </div>

        <div class="bg-white rounded-[2rem] p-10 md:p-16 border border-[#EEEEEE] shadow-sm space-y-12">
            <section>
                <h2 class="text-[24px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-[#F9F9F9] flex items-center justify-center text-[14px]">01</span>
                    Agreement to Terms
                </h2>
                <div class="text-[16px] text-[#666666] leading-relaxed space-y-4">
                    <p>Welcome to ASO Online Market. By accessing or using our website, you agree to be bound by these Terms and Conditions and our Privacy Policy. If you do not agree to all of these terms, do not use this website.</p>
                    <p>These Terms apply to all visitors, users, and others who access or use the Service.</p>
                </div>
            </section>

            <section>
                <h2 class="text-[24px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-[#F9F9F9] flex items-center justify-center text-[14px]">02</span>
                    Purchases & Payment
                </h2>
                <div class="text-[16px] text-[#666666] leading-relaxed space-y-4">
                    <p>If you wish to purchase any product or service made available through the Service, you may be asked to supply certain information relevant to your Purchase including, without limitation, your credit card number, the expiration date of your credit card, your billing address, and your shipping information.</p>
                    <p>You represent and warrant that: (i) you have the legal right to use any credit card(s) or other payment method(s) in connection with any Purchase; and that (ii) the information you supply to us is true, correct and complete.</p>
                </div>
            </section>

            <section>
                <h2 class="text-[24px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-[#F9F9F9] flex items-center justify-center text-[14px]">03</span>
                    Account Security
                </h2>
                <div class="text-[16px] text-[#666666] leading-relaxed space-y-4">
                    <p>When you create an account with us, you must provide us information that is accurate, complete, and current at all times. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account on our Service.</p>
                    <p>You are responsible for safeguarding the password that you use to access the Service and for any activities or actions under your password.</p>
                </div>
            </section>

            <section>
                <h2 class="text-[24px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-[#F9F9F9] flex items-center justify-center text-[14px]">04</span>
                    Intellectual Property
                </h2>
                <div class="text-[16px] text-[#666666] leading-relaxed space-y-4">
                    <p>The Service and its original content, features and functionality are and will remain the exclusive property of ASO Online Market and its licensors. The Service is protected by copyright, trademark, and other laws.</p>
                </div>
            </section>

            <section>
                <h2 class="text-[24px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-[#F9F9F9] flex items-center justify-center text-[14px]">05</span>
                    Limitation of Liability
                </h2>
                <div class="text-[16px] text-[#666666] leading-relaxed space-y-4">
                    <p>In no event shall ASO Online Market, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses.</p>
                </div>
            </section>
        </div>

        <div class="mt-12 text-center">
            <a href="../shop.php" class="inline-flex items-center gap-2 text-[14px] font-bold text-[#1A1A1A] hover:gap-4 transition-all">
                Return to Shopping <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </a>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
