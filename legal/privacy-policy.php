<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$page_title = 'Privacy Policy';
include '../includes/header.php';
?>

<main class="bg-[#F9F9F9] min-h-screen py-24">
    <div class="max-w-[1000px] mx-auto px-6">
        <div class="text-center mb-16">
            <span class="inline-block bg-white border border-[#EEEEEE] text-[#1A1A1A] text-[12px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest mb-6 shadow-sm">
                Legal
            </span>
            <h1 class="text-[48px] font-black text-[#1A1A1A] tracking-tighter mb-4">Privacy <span class="text-[#888888]">Policy.</span></h1>
            <p class="text-[#888888] font-medium">Your privacy is our priority. Last Updated: <?php echo date('F d, Y'); ?></p>
        </div>

        <div class="bg-white rounded-[2rem] p-10 md:p-16 border border-[#EEEEEE] shadow-sm space-y-12">
            <section>
                <h2 class="text-[24px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-[#F9F9F9] flex items-center justify-center text-[14px]">01</span>
                    Information We Collect
                </h2>
                <div class="text-[16px] text-[#666666] leading-relaxed space-y-4">
                    <p>We collect information you provide directly to us when you create an account, place an order, or contact us for support. This may include your name, email address, phone number, shipping address, and payment information.</p>
                    <p>We also automatically collect certain information when you visit our site, such as your IP address, browser type, and how you interact with our pages.</p>
                </div>
            </section>

            <section>
                <h2 class="text-[24px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-[#F9F9F9] flex items-center justify-center text-[14px]">02</span>
                    How We Use Information
                </h2>
                <div class="text-[16px] text-[#666666] leading-relaxed space-y-4">
                    <p>We use the information we collect to:</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Process and fulfill your orders</li>
                        <li>Communicate with you about your account or transactions</li>
                        <li>Send you marketing communications (if you've opted in)</li>
                        <li>Improve our website and customer service</li>
                        <li>Protect against fraudulent or illegal activity</li>
                    </ul>
                </div>
            </section>

            <section>
                <h2 class="text-[24px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-[#F9F9F9] flex items-center justify-center text-[14px]">03</span>
                    Data Sharing & Security
                </h2>
                <div class="text-[16px] text-[#666666] leading-relaxed space-y-4">
                    <p>We do not sell your personal information to third parties. We only share information with service providers who help us operate our business (e.g., payment processors and shipping carriers).</p>
                    <p>We implement industry-standard security measures to protect your data, but no method of transmission over the internet is 100% secure.</p>
                </div>
            </section>

            <section>
                <h2 class="text-[24px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-[#F9F9F9] flex items-center justify-center text-[14px]">04</span>
                    Your Rights
                </h2>
                <div class="text-[16px] text-[#666666] leading-relaxed space-y-4">
                    <p>You have the right to access, update, or delete your personal information at any time through your account settings. You can also opt-out of marketing emails by following the unsubscribe link in any message.</p>
                </div>
            </section>
        </div>

        <div class="mt-12 text-center">
            <a href="../contact.php" class="inline-flex items-center gap-2 text-[14px] font-bold text-[#1A1A1A] hover:gap-4 transition-all">
                Have Questions? Contact Us <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </a>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
