<?php
/**
 * Storefront: Login
 */
require_once 'includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit();
    } else {
        header('Location: index.php');
        exit();
    }
}

$page_title = 'Login';
$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT user_id, name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && verifyPassword($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                    exit();
                } else {
                    $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                    unset($_SESSION['redirect_after_login']);
                    header("Location: $redirect");
                    exit();
                }
            } else {
                $errors[] = 'Invalid email or password';
            }
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $errors[] = 'An error occurred during login. Please try again.';
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
            <h2 class="text-white text-[48px] font-black leading-tight mb-4 tracking-tighter">Premium Quality.<br><span class="text-white/60">Delivered Fast.</span></h2>
            <p class="text-white/70 text-[18px] font-medium max-w-sm">Experience the fusion of fresh groceries and light-speed technology hardware.</p>
        </div>
    </div>

    <!-- Form Side -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8 md:p-12 z-10 relative">
        <div class="w-full max-w-[420px] bg-white rounded-3xl sm:rounded-[2.5rem] p-6 sm:p-10 md:p-12 border border-[#EEEEEE] shadow-sm">
            <div class="mb-6 sm:mb-8 text-center lg:text-left">
                <h1 class="text-[26px] sm:text-[32px] font-black text-[#1A1A1A] mb-1 sm:mb-2 tracking-tight">Welcome Back.</h1>
                <p class="text-[#888888] font-bold text-[11px] sm:text-[12px] uppercase tracking-widest">Sign in to your account</p>
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

            <form action="" method="POST" class="space-y-4 sm:space-y-5">
                <!-- Email Address -->
                <div class="space-y-1.5">
                    <label for="email" class="text-[11px] font-bold text-[#888888] uppercase tracking-widest ml-4">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required 
                           class="w-full px-5 py-3 sm:px-6 sm:py-4 bg-[#F9F9F9] border border-[#EEEEEE] rounded-full focus:border-primary outline-none text-[14px] sm:text-[15px] transition-all" />
                </div>

                <!-- Password -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between px-4">
                        <label for="password" class="text-[11px] font-bold text-[#888888] uppercase tracking-widest">Password</label>
                        <a href="forgot_password.php" class="text-[11px] sm:text-[12px] font-bold text-[#1A1A1A] hover:underline">Forgot?</a>
                    </div>
                    <div class="relative group">
                        <input type="password" id="password" name="password" required 
                               class="w-full px-5 py-3 sm:px-6 sm:py-4 bg-[#F9F9F9] border border-[#EEEEEE] rounded-full focus:border-primary outline-none text-[14px] sm:text-[15px] transition-all" />
                        <button type="button" id="togglePassword" class="absolute right-5 top-1/2 -translate-y-1/2 text-[#888888] hover:text-[#1A1A1A] transition-colors">
                            <span class="material-symbols-outlined text-[20px]" id="toggleIcon">visibility</span>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center gap-2.5 ml-4">
                    <input type="checkbox" id="remember" name="remember" class="w-4.5 h-4.5 rounded border-[#EEEEEE] text-[#1A1A1A] focus:ring-[#1A1A1A]" />
                    <label for="remember" class="text-[13px] sm:text-[14px] font-medium text-[#666666] cursor-pointer">Keep me logged in</label>
                </div>

                <!-- Action Button -->
                <button type="submit" class="w-full bg-primary text-white font-bold text-[15px] sm:text-[16px] py-3.5 sm:py-4 rounded-full mt-4 hover:bg-primary shadow-xl hover:shadow-primary/10 transition-all active:scale-[0.98]">
                    Sign In <span class="material-symbols-outlined text-[20px] ml-2 align-middle">login</span>
                </button>
            </form>

            <p class="mt-6 sm:mt-8 text-center text-[13px] sm:text-[14px] text-[#666666] font-medium">
                Don't have an account? <a href="register.php" class="text-[#1A1A1A] font-black hover:underline">Create One</a>
            </p>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const emailField = document.getElementById('email');
    if (emailField) emailField.focus();

    const toggleBtn = document.getElementById('togglePassword');
    const passInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    if (toggleBtn && passInput && toggleIcon) {
        toggleBtn.addEventListener('click', function() {
            const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passInput.setAttribute('type', type);
            toggleIcon.textContent = type === 'password' ? 'visibility' : 'visibility_off';
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
