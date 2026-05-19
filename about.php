<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$page_title = 'About Us';

include 'includes/header.php';
?>

<main class="bg-[#F9F9F9] min-h-screen">
    <!-- Hero Section -->
    <section class="relative py-24 overflow-hidden">
        <div class="absolute inset-0 z-0">
            <div class="absolute inset-0 bg-[#F9F9F9]"></div>
            <div class="absolute top-[-10%] right-[-5%] w-[400px] h-[400px] bg-[#EEEEEE] rounded-full blur-[100px] opacity-50"></div>
            <div class="absolute bottom-[-10%] left-[-5%] w-[400px] h-[400px] bg-[#EEEEEE] rounded-full blur-[100px] opacity-50"></div>
        </div>
        
        <div class="relative z-10 max-w-[1200px] mx-auto px-6 text-center">
            <span class="inline-block bg-white border border-[#EEEEEE] text-[#1A1A1A] text-[12px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest mb-6 shadow-sm">
                Our Story
            </span>
            <h1 class="text-[48px] md:text-[64px] font-black text-[#1A1A1A] leading-tight mb-6 tracking-tighter">
                ASO Online <span class="text-[#888888]">Market.</span>
            </h1>
            <p class="max-w-2xl mx-auto text-[18px] md:text-[20px] text-[#666666] font-medium leading-relaxed">
                Your trusted partner in quality online shopping. We're on a mission to democratize access to premium products with speed and reliability.
            </p>
        </div>
    </section>

    <!-- Content Sections -->
    <section class="max-w-[1200px] mx-auto px-6 pb-24 space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Our Story -->
            <div class="bg-white rounded-[2rem] p-10 md:p-12 border border-[#EEEEEE] shadow-sm">
                <h2 class="text-[28px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                    <span class="material-symbols-outlined text-[32px]">history_edu</span> Our Story
                </h2>
                <div class="space-y-6 text-[16px] text-[#666666] leading-relaxed">
                    <p>
                        ASO Online Market was founded with a simple mission: to provide customers with high-quality products
                        at competitive prices while delivering exceptional customer service. Since our inception, we've grown
                        from a small startup to become one of the leading e-commerce platforms in the region.
                    </p>
                    <p>
                        We believe that online shopping should be convenient, reliable, and enjoyable. That's why we've built
                        our platform with cutting-edge technology and a customer-first approach that puts your needs at the center
                        of everything we do.
                    </p>
                </div>
            </div>

            <!-- Our Mission -->
            <div class="bg-white rounded-[2rem] p-10 md:p-12 border border-[#EEEEEE] shadow-sm">
                <h2 class="text-[28px] font-black text-[#1A1A1A] mb-6 flex items-center gap-3">
                    <span class="material-symbols-outlined text-[32px]">rocket_launch</span> Our Mission
                </h2>
                <p class="text-[16px] text-[#666666] leading-relaxed mb-8">
                    To democratize access to quality products by providing a seamless online shopping experience that
                    combines convenience, reliability, and value.
                </p>
                <div class="grid grid-cols-1 gap-4">
                    <?php 
                    $commitments = [
                        ['check_circle', 'Fair pricing for high-quality goods'],
                        ['check_circle', 'Exceptional customer support'],
                        ['check_circle', 'Secure and reliable transactions'],
                        ['check_circle', 'Supporting local communities'],
                    ];
                    foreach ($commitments as $item): ?>
                        <div class="flex items-center gap-3 p-4 bg-[#F9F9F9] rounded-2xl border border-[#EEEEEE]">
                            <span class="material-symbols-outlined text-[#1A1A1A]"><?php echo $item[0]; ?></span>
                            <span class="text-[14px] font-bold text-[#1A1A1A]"><?php echo $item[1]; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Values Section -->
        <div class="bg-white rounded-[2rem] p-10 md:p-16 border border-[#EEEEEE] shadow-sm">
            <div class="text-center mb-16">
                <h2 class="text-[32px] font-black text-[#1A1A1A] mb-4">Our Core Values</h2>
                <p class="text-[#888888] font-medium">The principles that guide every decision we make.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <div class="text-center group">
                    <div class="w-20 h-20 mx-auto bg-[#F9F9F9] rounded-3xl flex items-center justify-center text-[#1A1A1A] mb-6 group-hover:bg-primary group-hover:text-white transition-all duration-500">
                        <span class="material-symbols-outlined text-[40px]">favorite</span>
                    </div>
                    <h4 class="text-[20px] font-black text-[#1A1A1A] mb-4">Customer First</h4>
                    <p class="text-[14px] text-[#666666] leading-relaxed">We prioritize our customers' needs and satisfaction above all else. Every decision is guided by what's best for you.</p>
                </div>

                <div class="text-center group">
                    <div class="w-20 h-20 mx-auto bg-[#F9F9F9] rounded-3xl flex items-center justify-center text-[#1A1A1A] mb-6 group-hover:bg-primary group-hover:text-white transition-all duration-500">
                        <span class="material-symbols-outlined text-[40px]">shield</span>
                    </div>
                    <h4 class="text-[20px] font-black text-[#1A1A1A] mb-4">Trust & Security</h4>
                    <p class="text-[14px] text-[#666666] leading-relaxed">We maintain the highest standards of security and privacy protection. Your trust is our most valuable asset.</p>
                </div>

                <div class="text-center group">
                    <div class="w-20 h-20 mx-auto bg-[#F9F9F9] rounded-3xl flex items-center justify-center text-[#1A1A1A] mb-6 group-hover:bg-primary group-hover:text-white transition-all duration-500">
                        <span class="material-symbols-outlined text-[40px]">award_star</span>
                    </div>
                    <h4 class="text-[20px] font-black text-[#1A1A1A] mb-4">Quality Excellence</h4>
                    <p class="text-[14px] text-[#666666] leading-relaxed">We partner with trusted suppliers and manufacturers to ensure every product meets our strict standards.</p>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="bg-primary rounded-[2rem] p-12 md:p-16 text-center relative overflow-hidden">
            <div class="absolute top-[-50%] left-[-10%] w-[300px] h-[300px] bg-white/5 rounded-full blur-[80px]"></div>
            <div class="relative z-10">
                <h2 class="text-[32px] md:text-[48px] font-black text-white mb-6 tracking-tighter">Ready to experience the future?</h2>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="shop.php" class="bg-white text-[#1A1A1A] font-bold px-10 py-4 rounded-full hover:scale-105 transition-transform">Start Shopping</a>
                    <a href="contact.php" class="bg-transparent border-2 border-white/20 text-white font-bold px-10 py-4 rounded-full hover:bg-white/10 transition-colors">Contact Us</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
