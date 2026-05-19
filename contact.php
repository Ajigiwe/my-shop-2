<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$page_title = 'Contact Us';

// Check if user is logged in
$user_name = $_SESSION['user_name'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';
$user_phone = $_SESSION['user_phone'] ?? '';

include 'includes/header.php';
?>

<main class="bg-[#F9F9F9] min-h-screen">
    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden">
        <div class="relative z-10 max-w-[1200px] mx-auto px-6 text-center">
            <span class="inline-block bg-white border border-[#EEEEEE] text-[#1A1A1A] text-[12px] font-black px-4 py-1.5 rounded-full uppercase tracking-widest mb-6 shadow-sm">
                Get In Touch
            </span>
            <h1 class="text-[48px] md:text-[64px] font-black text-[#1A1A1A] leading-tight mb-4 tracking-tighter">
                Contact <span class="text-[#888888]">Us.</span>
            </h1>
            <p class="max-w-xl mx-auto text-[16px] md:text-[18px] text-[#666666] font-medium leading-relaxed">
                Have questions or need assistance? Our customer service team is here to help you experience the future of shopping.
            </p>
        </div>
    </section>

    <!-- Contact Content -->
    <section class="max-w-[1200px] mx-auto px-6 pb-24">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
            <!-- Contact Sidebar -->
            <div class="w-full lg:w-[400px] space-y-6">
                <!-- Location & Info -->
                <div class="bg-white rounded-[2rem] p-8 border border-[#EEEEEE] shadow-sm">
                    <h2 class="text-[20px] font-black text-[#1A1A1A] mb-8 flex items-center gap-3">
                        <span class="material-symbols-outlined text-[24px]">map</span> Our Headquarters
                    </h2>
                    
                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-[#F5F5F5] flex items-center justify-center text-[#1A1A1A] flex-shrink-0">
                                <span class="material-symbols-outlined text-[20px]">location_on</span>
                            </div>
                            <div>
                                <p class="text-[13px] font-bold text-[#888888] uppercase tracking-widest mb-1">Address</p>
                                <p class="text-[15px] font-black text-[#1A1A1A]">123 Business District,<br>Accra, Ghana</p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-[#F5F5F5] flex items-center justify-center text-[#1A1A1A] flex-shrink-0">
                                <span class="material-symbols-outlined text-[20px]">call</span>
                            </div>
                            <div>
                                <p class="text-[13px] font-bold text-[#888888] uppercase tracking-widest mb-1">Phone</p>
                                <p class="text-[15px] font-black text-[#1A1A1A]">+233 20 123 4567</p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-[#F5F5F5] flex items-center justify-center text-[#1A1A1A] flex-shrink-0">
                                <span class="material-symbols-outlined text-[20px]">mail</span>
                            </div>
                            <div>
                                <p class="text-[13px] font-bold text-[#888888] uppercase tracking-widest mb-1">Email</p>
                                <p class="text-[15px] font-black text-[#1A1A1A]">info@asoonlinemarket.com</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-10 pt-8 border-t border-[#F5F5F5]">
                        <h3 class="text-[16px] font-black text-[#1A1A1A] mb-4">Business Hours</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between text-[14px]">
                                <span class="text-[#888888] font-bold">Mon - Fri</span>
                                <span class="text-[#1A1A1A] font-black">8:00 AM - 6:00 PM</span>
                            </div>
                            <div class="flex justify-between text-[14px]">
                                <span class="text-[#888888] font-bold">Saturday</span>
                                <span class="text-[#1A1A1A] font-black">9:00 AM - 4:00 PM</span>
                            </div>
                            <div class="flex justify-between text-[14px]">
                                <span class="text-[#888888] font-bold">Sunday</span>
                                <span class="text-[#EF4444] font-black uppercase tracking-tighter">Closed</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Google Maps (Compact) -->
                <div class="bg-white rounded-[2rem] p-4 border border-[#EEEEEE] shadow-sm h-[300px] overflow-hidden group">
                    <iframe 
                        class="w-full h-full rounded-[1.5rem] grayscale-[0.5] group-hover:grayscale-0 transition-all duration-700"
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3970.3589870469786!2d-0.1868594852308864!3d5.611744995937145!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNcKwMzYnNDIuMyJOIDDCsDExJzEyLjciVw!5e0!3m2!1sen!2sgh!4v1640995200000" 
                        allowfullscreen="" 
                        loading="lazy">
                    </iframe>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="flex-1">
                <div class="bg-white rounded-[2rem] p-8 md:p-12 border border-[#EEEEEE] shadow-sm">
                    <h2 class="text-[28px] font-black text-[#1A1A1A] mb-10 flex items-center gap-3">
                        <span class="material-symbols-outlined text-[32px]">send</span> Send us a Message
                    </h2>

                    <div id="formMessages" class="mb-8 hidden"></div>

                    <form id="contactForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[12px] font-bold text-[#888888] uppercase tracking-widest ml-4">Full Name</label>
                                <input type="text" name="name" required 
                                    class="w-full bg-[#F9F9F9] border border-[#EEEEEE] rounded-full py-4 px-6 outline-none focus:border-primary transition-colors text-[15px]" 
                                    value="<?php echo htmlspecialchars($user_name); ?>" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[12px] font-bold text-[#888888] uppercase tracking-widest ml-4">Email Address</label>
                                <input type="email" name="email" required 
                                    class="w-full bg-[#F9F9F9] border border-[#EEEEEE] rounded-full py-4 px-6 outline-none focus:border-primary transition-colors text-[15px]" 
                                    value="<?php echo htmlspecialchars($user_email); ?>" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[12px] font-bold text-[#888888] uppercase tracking-widest ml-4">Phone Number</label>
                                <input type="tel" name="phone" 
                                    class="w-full bg-[#F9F9F9] border border-[#EEEEEE] rounded-full py-4 px-6 outline-none focus:border-primary transition-colors text-[15px]" 
                                    value="<?php echo htmlspecialchars($user_phone); ?>" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[12px] font-bold text-[#888888] uppercase tracking-widest ml-4">Subject</label>
                                <select name="subject" required 
                                    class="w-full bg-[#F9F9F9] border border-[#EEEEEE] rounded-full py-4 px-6 outline-none focus:border-primary transition-colors text-[15px] appearance-none">
                                    <option value="" disabled selected>Select a subject</option>
                                    <option value="General Inquiry">General Inquiry</option>
                                    <option value="Product Question">Product Question</option>
                                    <option value="Order Status">Order Status</option>
                                    <option value="Returns & Exchanges">Returns & Exchanges</option>
                                    <option value="Wholesale Inquiries">Wholesale Inquiries</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[12px] font-bold text-[#888888] uppercase tracking-widest ml-4">Your Message</label>
                            <textarea name="message" required rows="6" 
                                class="w-full bg-[#F9F9F9] border border-[#EEEEEE] rounded-[1.5rem] py-4 px-6 outline-none focus:border-primary transition-colors text-[15px] resize-none"
                                placeholder="How can we help you today?"></textarea>
                        </div>

                        <div class="flex items-center gap-3 ml-4">
                            <input type="checkbox" name="newsletter" id="newsletter" checked 
                                class="w-5 h-5 rounded border-[#EEEEEE] text-[#1A1A1A] focus:ring-[#1A1A1A]" />
                            <label for="newsletter" class="text-[14px] font-medium text-[#666666]">Subscribe to our newsletter for updates</label>
                        </div>

                        <button type="submit" id="submitBtn" class="w-full bg-primary text-white font-bold text-[16px] py-5 rounded-full flex items-center justify-center gap-3 hover:bg-primary shadow-xl hover:shadow-primary/10 transition-all active:scale-[0.98]">
                            Send Message <span class="material-symbols-outlined text-[24px]">verified</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const formMessages = document.getElementById('formMessages');
    const submitBtn = document.getElementById('submitBtn');

    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Loading state
            const originalBtnContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[24px]">sync</span> Sending...';
            submitBtn.disabled = true;
            formMessages.classList.add('hidden');

            const formData = new FormData(contactForm);

            fetch('process_contact.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'contact_success.php';
                } else {
                    formMessages.innerHTML = `
                        <div class="bg-[#FEF2F2] border border-[#FEE2E2] rounded-2xl p-4 flex items-center gap-3 text-[#EF4444]">
                            <span class="material-symbols-outlined">error</span>
                            <span class="text-[14px] font-bold">${data.message || 'Please check the form for errors.'}</span>
                        </div>
                    `;
                    formMessages.classList.remove('hidden');
                    submitBtn.innerHTML = originalBtnContent;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.innerHTML = originalBtnContent;
                submitBtn.disabled = false;
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>