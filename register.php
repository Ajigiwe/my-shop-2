<?php
/**
 * Storefront: Register
 */
require_once 'includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Register';
$errors = [];
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters long';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    } elseif (!validatePhone($phone)) {
        $errors[] = 'Please enter a valid phone number';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    }
    
    if (empty($confirm_password)) {
        $errors[] = 'Please confirm your password';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email already exists';
            }
        } catch(PDOException $e) {
            error_log("Registration check error: " . $e->getMessage());
            $errors[] = 'An error occurred. Please try again.';
        }
    }
    
    if (empty($errors)) {
        try {
            $hashed_password = hashPassword($password);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'customer')");
            $stmt->execute([$name, $email, $hashed_password, $phone]);
            
            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'customer';
            
            header('Location: index.php?registered=1');
            exit();
        } catch(PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors[] = 'An error occurred during registration. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<main class="min-h-screen flex relative overflow-hidden bg-[#F9F9F9]">
    <!-- Desktop Side Panel -->
    <div class="hidden lg:block lg:w-1/2 h-screen sticky top-0">
        <img src="assets/images/login_side_panel.png" alt="Store Aesthetic" class="w-full h-full object-cover" />
        <div class="absolute inset-0 bg-primary/10"></div>
        <div class="absolute inset-0 flex flex-col justify-end p-20 bg-gradient-to-t from-black/80 via-transparent to-transparent">
            <h2 class="text-white text-[48px] font-black leading-tight mb-4 tracking-tighter">Join the <span class="text-white/60">Community.</span></h2>
            <p class="text-white/70 text-[18px] font-medium max-w-sm">Create an account to unlock premium benefits and experience the future of shopping.</p>
        </div>
    </div>

    <!-- Form Side -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8 md:p-12 z-10 relative">
        <div class="w-full max-w-[480px] bg-white rounded-3xl sm:rounded-[2.5rem] p-6 sm:p-10 md:p-12 border border-[#EEEEEE] shadow-sm">
            <div class="mb-6 sm:mb-8 text-center lg:text-left">
                <h1 class="text-[26px] sm:text-[32px] font-black text-[#1A1A1A] mb-1 sm:mb-2 tracking-tight">Create Account.</h1>
                <p class="text-[#888888] font-bold text-[11px] sm:text-[12px] uppercase tracking-widest">Start your journey with us</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="bg-[#FEF2F2] border border-[#FEE2E2] rounded-2xl p-4 mb-6">
                    <ul class="flex flex-col gap-1">
                        <?php foreach ($errors as $error): ?>
                            <li class="text-[#EF4444] text-[13px] font-bold flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">error</span>
                                <?php echo htmlspecialchars($error); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="space-y-4">
                <!-- Full Name -->
                <div class="space-y-1.5">
                    <label for="name" class="text-[11px] font-bold text-[#888888] uppercase tracking-widest ml-4">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required 
                           class="w-full px-5 py-3 sm:px-6 sm:py-4 bg-[#F9F9F9] border border-[#EEEEEE] rounded-full focus:border-primary outline-none text-[14px] sm:text-[15px] transition-all" />
                </div>

                <!-- Email -->
                <div class="space-y-1.5">
                    <label for="email" class="text-[11px] font-bold text-[#888888] uppercase tracking-widest ml-4">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required 
                           class="w-full px-5 py-3 sm:px-6 sm:py-4 bg-[#F9F9F9] border border-[#EEEEEE] rounded-full focus:border-primary outline-none text-[14px] sm:text-[15px] transition-all" />
                </div>

                <!-- Phone -->
                <div class="space-y-1.5">
                    <label for="phone" class="text-[11px] font-bold text-[#888888] uppercase tracking-widest ml-4">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required 
                           class="w-full px-5 py-3 sm:px-6 sm:py-4 bg-[#F9F9F9] border border-[#EEEEEE] rounded-full focus:border-primary outline-none text-[14px] sm:text-[15px] transition-all" />
                </div>

                <!-- Passwords -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="password" class="text-[11px] font-bold text-[#888888] uppercase tracking-widest ml-4">Password</label>
                        <div class="relative group">
                            <input type="password" id="password" name="password" required 
                                   class="w-full px-5 py-3 sm:px-6 sm:py-4 bg-[#F9F9F9] border border-[#EEEEEE] rounded-full focus:border-primary outline-none text-[14px] sm:text-[15px] transition-all" />
                            <button type="button" class="password-toggle absolute right-5 top-1/2 -translate-y-1/2 text-[#888888] hover:text-[#1A1A1A] transition-colors" data-target="password">
                                <span class="material-symbols-outlined text-[20px]">visibility</span>
                            </button>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label for="confirm_password" class="text-[11px] font-bold text-[#888888] uppercase tracking-widest ml-4">Confirm</label>
                        <div class="relative group">
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   class="w-full px-5 py-3 sm:px-6 sm:py-4 bg-[#F9F9F9] border border-[#EEEEEE] rounded-full focus:border-primary outline-none text-[14px] sm:text-[15px] transition-all" />
                            <button type="button" class="password-toggle absolute right-5 top-1/2 -translate-y-1/2 text-[#888888] hover:text-[#1A1A1A] transition-colors" data-target="confirm_password">
                                <span class="material-symbols-outlined text-[20px]">visibility</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Terms -->
                <div class="flex items-start gap-2.5 ml-4">
                    <input type="checkbox" id="terms" name="terms" required class="mt-1 w-4.5 h-4.5 rounded border-[#EEEEEE] text-[#1A1A1A] focus:ring-[#1A1A1A]" />
                    <label for="terms" class="text-[11px] sm:text-[12px] font-medium text-[#666666] leading-relaxed">
                        I agree to the <a href="#" class="text-[#1A1A1A] font-bold hover:underline">Terms</a> and <a href="#" class="text-[#1A1A1A] font-bold hover:underline">Privacy Policy</a>.
                    </label>
                </div>

                <!-- Action Button -->
                <button type="submit" class="w-full bg-primary text-white font-bold text-[15px] sm:text-[16px] py-3.5 sm:py-4 rounded-full mt-4 hover:bg-primary shadow-xl hover:shadow-primary/10 transition-all active:scale-[0.98]">
                    Create Account <span class="material-symbols-outlined text-[20px] ml-2 align-middle">person_add</span>
                </button>
            </form>

            <p class="mt-6 sm:mt-8 text-center text-[13px] sm:text-[14px] text-[#666666] font-medium">
                Already have an account? <a href="login.php" class="text-[#1A1A1A] font-black hover:underline">Sign In</a>
            </p>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('.material-symbols-outlined');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
