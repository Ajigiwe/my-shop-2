<?php
/**
 * Footer
 * - Standard footer for all pages
 */
$base = '';
$current_path = $_SERVER['PHP_SELF'] ?? '';
if (preg_match('/\/(admin|user|legal)\//', $current_path)) {
    $base = '../';
}
?>

<footer class="w-full mt-auto bg-[#F9F9F9] border-t border-[#EEEEEE] pt-8 pb-4 text-[#666666] font-sans">
    <div class="max-w-[1200px] mx-auto px-6">
        
        <!-- Top Footer Column Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-4">
            
            <!-- Left Info Column -->
            <div class="space-y-2">
                <div class="mb-2">
                    <img src="<?php echo $base; ?>assets/images/logo-v3.png" alt="<?php echo htmlspecialchars($site_name); ?>" class="h-16 w-auto object-contain" />
                </div>
                <p class="text-[12px] text-[#666666] leading-relaxed max-w-[240px]">
                    123 Business District,<br>Accra, Ghana
                </p>
                <div class="space-y-0.5 text-[12px] text-[#666666]">
                    <p>Email: <a href="mailto:info@asoonlinemarket.com" class="hover:text-[#1A1A1A] transition-colors">info@asoonlinemarket.com</a></p>
                    <p>Phone: <a href="tel:+233201234567" class="hover:text-[#1A1A1A] transition-colors">+233 20 123 4567</a></p>
                </div>
            </div>

            <!-- Company Column -->
            <div>
                <h4 class="text-[11px] font-black uppercase tracking-widest text-[#1A1A1A] mb-3 border-b border-[#EEEEEE] pb-1 max-w-[80px]">Company</h4>
                <ul class="space-y-1 text-[12px]">
                    <li><a href="<?php echo $base; ?>about.php" class="hover:text-[#1A1A1A] transition-colors py-0.5 block">About Us</a></li>
                    <li><a href="<?php echo $base; ?>shop.php" class="hover:text-[#1A1A1A] transition-colors py-0.5 block">Shop</a></li>
                    <li><a href="<?php echo $base; ?>contact.php" class="hover:text-[#1A1A1A] transition-colors py-0.5 block">Contact Us</a></li>
                    <li><a href="<?php echo $base; ?>user/orders.php" class="hover:text-[#1A1A1A] transition-colors py-0.5 block">Track Your Order</a></li>
                    <li><a href="<?php echo $base; ?>login.php" class="hover:text-[#1A1A1A] transition-colors py-0.5 block">Login / Register</a></li>
                </ul>
            </div>

            <!-- Support Column -->
            <div>
                <h4 class="text-[11px] font-black uppercase tracking-widest text-[#1A1A1A] mb-3 border-b border-[#EEEEEE] pb-1 max-w-[80px]">Support</h4>
                <ul class="space-y-1 text-[12px]">
                    <li><a href="<?php echo $base; ?>legal/terms-conditions.php" class="hover:text-[#1A1A1A] transition-colors py-0.5 block">Terms & Conditions</a></li>
                    <li><a href="<?php echo $base; ?>legal/privacy-policy.php" class="hover:text-[#1A1A1A] transition-colors py-0.5 block">Privacy Policy</a></li>
                    <li><a href="<?php echo $base; ?>legal/shipping-policy.php" class="hover:text-[#1A1A1A] transition-colors py-0.5 block">Shipping Policy</a></li>
                </ul>
            </div>

            <!-- Direct Support Column -->
            <div class="space-y-2">
                <h4 class="text-[11px] font-black uppercase tracking-widest text-[#1A1A1A] mb-3 border-b border-[#EEEEEE] pb-1 max-w-[120px]">Direct Support</h4>
                <p class="text-[12px] leading-relaxed max-w-[240px]">
                    Leading the way in fresh groceries and cutting-edge electronics delivered to your door.
                </p>
                <a href="mailto:info@asoonlinemarket.com" class="flex items-center justify-center gap-2 px-3.5 py-2 bg-transparent border border-[#DDDDDD] hover:border-[#1A1A1A] rounded-full text-[#1A1A1A] text-[11px] font-black tracking-widest uppercase transition-all duration-300 group max-w-[185px]">
                    <span class="material-symbols-outlined text-[15px] text-gray-500 group-hover:text-[#1A1A1A] transition-colors">mail</span>
                    EMAIL OUR TEAM
                </a>
                <p class="text-[10px] text-[#888888]">
                    We typically respond within 24 hours.
                </p>
            </div>

        </div>

        <!-- Middle Row: Socials -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-3 border-t border-[#EEEEEE] pt-4 mb-4">
            
            <!-- Social Icons (Align left) -->
            <div class="flex items-center gap-2.5">
                <?php if (!empty($settings['social_facebook'])): ?>
                    <a href="<?php echo htmlspecialchars($settings['social_facebook']); ?>" target="_blank" class="w-8 h-8 rounded-full bg-[#EEEEEE] hover:bg-[#1A1A1A] flex items-center justify-center text-[#1A1A1A] hover:text-white transition-colors duration-300 group" title="Facebook">
                        <svg class="w-4 h-4 fill-current text-[#1A1A1A] group-hover:text-white transition-colors duration-300" viewBox="0 0 24 24">
                            <path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.8c4.56-.93 8-4.96 8-9.8z"/>
                        </svg>
                    </a>
                <?php endif; ?>
                <?php if (!empty($settings['social_whatsapp'])): ?>
                    <?php 
                        $wa_val = trim($settings['social_whatsapp']);
                        $wa_link = htmlspecialchars($wa_val);
                        // If it is a phone number, automatically format it as a clean wa.me link
                        if (preg_match('/^[0-9+ ]+$/', $wa_val)) {
                            $clean_num = preg_replace('/[^0-9]/', '', $wa_val);
                            $wa_link = "https://wa.me/" . $clean_num;
                        }
                    ?>
                    <a href="<?php echo $wa_link; ?>" target="_blank" class="w-8 h-8 rounded-full bg-[#EEEEEE] hover:bg-[#1A1A1A] flex items-center justify-center text-[#1A1A1A] hover:text-white transition-colors duration-300 group" title="WhatsApp">
                        <svg class="w-4 h-4 fill-current text-[#1A1A1A] group-hover:text-white transition-colors duration-300" viewBox="0 0 24 24">
                            <path d="M12.012 2c-5.506 0-9.989 4.478-9.99 9.984a9.96 9.96 0 001.333 4.982L2 22l5.233-1.371a9.936 9.936 0 004.779 1.218h.004c5.505 0 9.988-4.478 9.989-9.984a9.975 9.975 0 00-2.925-7.064A9.932 9.932 0 0012.012 2zm5.727 14.072c-.244.688-1.201 1.249-1.657 1.297-.431.045-.994.074-1.61-.122a7.195 7.195 0 01-3.136-1.913 7.804 7.804 0 01-2.153-3.11c-.422-.724-.689-1.562-.689-2.353 0-1.659.866-2.485 1.192-2.813.244-.246.61-.31.874-.31h.244c.264 0 .528.016.752.544l.793 1.936c.081.2.146.416.033.624-.097.176-.227.352-.39.512l-.504.496c-.163.16-.341.336-.146.672a5.056 5.056 0 001.9 2.08 4.093 4.093 0 002.374.88c.325.016.512-.144.691-.336.179-.192.748-.864.96-1.168.195-.272.455-.24.715-.144l2.228 1.04c.26.128.52.256.585.368.065.112.065.656-.179 1.344z"/>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Empty right side since logos are removed -->
            <div></div>

        </div>

        <!-- Bottom Copyright Row -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 border-t border-[#EEEEEE] pt-4 text-[11px] text-[#888888]">
            <p>
                <?php echo !empty($settings['footer_notice']) ? htmlspecialchars($settings['footer_notice']) : '© ' . date('Y') . ' ' . htmlspecialchars($site_name) . '. All rights reserved.'; ?>
            </p>
            <div class="flex items-center gap-6">
                <a href="<?php echo $base; ?>legal/privacy-policy.php" class="hover:text-[#1A1A1A] transition-colors">Privacy</a>
                <a href="<?php echo $base; ?>legal/terms-conditions.php" class="hover:text-[#1A1A1A] transition-colors">Terms</a>
                <a href="<?php echo $base; ?>legal/cookie-policy.php" class="hover:text-[#1A1A1A] transition-colors">Cookies</a>
            </div>
        </div>

    </div>
</footer>

<?php if (isset($settings['promo_popup_enabled']) && $settings['promo_popup_enabled'] == '1'): ?>
    <!-- Promo Popup Modal -->
    <div id="promoPopupOverlay" class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4 opacity-0 transition-opacity duration-300">
        <div id="promoPopupModal" class="bg-white rounded-2xl shadow-2xl overflow-hidden max-w-[800px] w-full flex flex-col md:flex-row transform scale-95 transition-transform duration-300 relative">
            
            <!-- Close 'X' Button -->
            <button onclick="closePromoPopup()" class="absolute top-4 right-4 z-10 w-8 h-8 flex items-center justify-center bg-white/50 hover:bg-white rounded-full text-black hover:text-red-500 transition-colors shadow-sm">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>

            <?php if (!empty($settings['promo_popup_image'])): ?>
            <!-- Image Section -->
            <div class="md:w-1/2 bg-[#F9F9F9] relative flex items-center justify-center min-h-[200px] md:min-h-[400px]">
                <img src="<?php echo $base; ?>assets/images/<?php echo htmlspecialchars($settings['promo_popup_image']); ?>" alt="Promo" class="absolute inset-0 w-full h-full object-cover">
            </div>
            <?php endif; ?>

            <!-- Content Section -->
            <div class="<?php echo !empty($settings['promo_popup_image']) ? 'md:w-1/2' : 'w-full'; ?> p-8 md:p-12 flex flex-col justify-center text-center md:text-left">
                <div class="text-[10px] font-black text-[#888888] uppercase tracking-widest mb-2">Special Offer</div>
                <h2 class="text-[28px] font-black text-[#1A1A1A] tracking-tighter leading-tight mb-4">
                    <?php echo htmlspecialchars($settings['promo_popup_title'] ?? 'Special Promotion'); ?>
                </h2>
                <p class="text-[14px] text-[#666666] mb-8 leading-relaxed">
                    <?php echo nl2br(htmlspecialchars($settings['promo_popup_content'] ?? 'Don\'t miss out on our latest deals and offers.')); ?>
                </p>
                
                <div class="flex flex-col gap-3">
                    <a href="<?php echo $base . ltrim(htmlspecialchars($settings['promo_popup_btn_link'] ?? 'shop.php'), '/'); ?>" class="bg-primary text-white text-center py-3.5 rounded-lg font-black text-[12px] uppercase tracking-widest hover:scale-105 transition-transform">
                        <?php echo htmlspecialchars($settings['promo_popup_btn_text'] ?? 'Shop Now'); ?>
                    </a>
                    <button onclick="dismissPromoPopup()" class="text-[11px] font-black text-[#888888] uppercase tracking-widest hover:text-[#1A1A1A] py-2 transition-colors">
                        Don't Show Again
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const frequency = '<?php echo htmlspecialchars($settings['promo_popup_frequency'] ?? 'session'); ?>';
        
        // 1. Check if permanently dismissed
        if (localStorage.getItem('promo_dismissed') === 'true') {
            return; // Never show
        }

        // 2. Check frequency
        let shouldShow = false;
        
        if (frequency === 'always') {
            shouldShow = true;
        } else if (frequency === 'session') {
            if (!sessionStorage.getItem('promo_shown_this_session')) {
                shouldShow = true;
            }
        } else if (frequency === 'daily') {
            const lastShown = localStorage.getItem('promo_last_shown_time');
            const now = new Date().getTime();
            // 24 hours = 86400000 ms
            if (!lastShown || (now - parseInt(lastShown)) > 86400000) {
                shouldShow = true;
            }
        }

        if (shouldShow) {
            // Slight delay so it doesn't jarringly appear instantly
            setTimeout(() => {
                const overlay = document.getElementById('promoPopupOverlay');
                const modal = document.getElementById('promoPopupModal');
                
                if(overlay && modal) {
                    overlay.classList.remove('hidden');
                    // Trigger reflow
                    void overlay.offsetWidth;
                    
                    overlay.classList.remove('opacity-0');
                    modal.classList.remove('scale-95');
                    modal.classList.add('scale-100');

                    // Record that we showed it
                    if (frequency === 'session') {
                        sessionStorage.setItem('promo_shown_this_session', 'true');
                    } else if (frequency === 'daily') {
                        localStorage.setItem('promo_last_shown_time', new Date().getTime().toString());
                    }
                }
            }, 1000);
        }
    });

    function closePromoPopup() {
        const overlay = document.getElementById('promoPopupOverlay');
        const modal = document.getElementById('promoPopupModal');
        
        if(overlay && modal) {
            overlay.classList.add('opacity-0');
            modal.classList.remove('scale-100');
            modal.classList.add('scale-95');
            
            setTimeout(() => {
                overlay.classList.add('hidden');
            }, 300);
        }
    }

    function dismissPromoPopup() {
        localStorage.setItem('promo_dismissed', 'true');
        closePromoPopup();
    }
    </script>
<?php endif; ?>

<!-- No more Bootstrap JS needed for the new UI, using Alpine or Vanilla JS for interactivity if needed -->
<!-- But we keep the base script.js if it has critical logic like cart handling -->
<script src="<?php echo $base; ?>assets/js/script.js"></script>

</body>
</html>
