<?php
/**
 * Storefront: Forgot Password
 */
require_once 'includes/db.php';
require_once 'includes/email_config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Forgot Password';
$errors = [];
$success = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $stmt = $pdo->prepare("
                    INSERT INTO password_resets (email, token, expires_at) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        token = VALUES(token),
                        created_at = NOW(),
                        expires_at = VALUES(expires_at)
                ");
                $stmt->execute([$email, $token, $expires]);
                
                $resetLink = SITE_URL . "reset_password.php?token=" . urlencode($token) . "&email=" . urlencode($email);
                $subject = "Password Reset Request - " . STORE_NAME;
                $message = "
                    <p>Hello " . htmlspecialchars($user['name']) . ",</p>
                    <p>You requested a password reset. Click the link below to reset your password:</p>
                    <p><a href='$resetLink' style='padding: 10px 15px; background: #0d6efd; color: white; text-decoration: none; border-radius: 4px;'>Reset Password</a></p>
                    <p>Or copy and paste this link in your browser:<br>
                    <code>$resetLink</code></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                ";
                
                if (sendEmail($email, $subject, $message)) {
                    $success = 'A reset link has been sent to your email. It will expire in 1 hour.';
                    $email = '';
                } else {
                    $errors[] = 'Failed to send reset email. Please try again.';
                }
            } else {
                $success = 'If an account with this email exists, you will receive instructions shortly.';
            }
        } catch(PDOException $e) {
            error_log("Forgot password error: " . $e->getMessage());
            $errors[] = 'An error occurred. Please try again.';
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
            <h2 class="text-white text-[48px] font-black leading-tight mb-4 tracking-tighter">Secure <span class="text-white/60">Access.</span></h2>
            <p class="text-white/70 text-[18px] font-medium max-w-sm">Don't worry, we'll help you get back into your account in no time.</p>
        </div>
    </div>

    <!-- Form Side -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 md:p-12 z-10 relative">
        <div class="w-full max-w-[420px] bg-white rounded-[2.5rem] p-10 md:p-14 border border-[#EEEEEE] shadow-sm">
            <div class="mb-10 text-center lg:text-left">
                <h1 class="text-[32px] font-black text-[#1A1A1A] mb-2 tracking-tight">Recover Account.</h1>
                <p class="text-[#888888] font-bold text-[14px] uppercase tracking-widest">Reset your password</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="bg-[#FEF2F2] border border-[#FEE2E2] rounded-2xl p-4 mb-8">
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

            <?php if ($success): ?>
                <div class="bg-[#F0FDF4] border border-[#DCFCE7] rounded-2xl p-6 mb-8 flex flex-col items-center text-center gap-4">
                    <span class="material-symbols-outlined text-[#22C55E] text-[48px]">check_circle</span>
                    <p class="text-[#1A1A1A] text-[15px] font-bold leading-relaxed"><?php echo htmlspecialchars($success); ?></p>
                </div>
                <a href="login.php" class="w-full bg-primary text-white font-bold text-[16px] py-5 rounded-full flex items-center justify-center gap-3 hover:bg-primary shadow-xl hover:shadow-primary/10 transition-all active:scale-[0.98]">
                    Return to Login <span class="material-symbols-outlined text-[20px]">login</span>
                </a>
            <?php else: ?>
                <form method="POST" class="space-y-8">
                    <div class="space-y-2">
                        <label for="email" class="text-[12px] font-bold text-[#888888] uppercase tracking-widest ml-4">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required 
                               class="w-full px-6 py-4 bg-[#F9F9F9] border border-[#EEEEEE] rounded-full focus:border-primary outline-none text-[15px] transition-all" 
                               placeholder="name@example.com" />
                    </div>

                    <button type="submit" class="w-full bg-primary text-white font-bold text-[16px] py-5 rounded-full hover:bg-primary shadow-xl hover:shadow-primary/10 transition-all active:scale-[0.98]">
                        Send Reset Link <span class="material-symbols-outlined text-[20px] ml-2">mail</span>
                    </button>
                </form>

                <div class="mt-10 text-center">
                    <a href="login.php" class="inline-flex items-center gap-2 text-[14px] font-black text-[#1A1A1A] hover:underline">
                        <span class="material-symbols-outlined text-[20px]">arrow_back</span> Back to Login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
