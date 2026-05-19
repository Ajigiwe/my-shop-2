<?php
/**
 * Storefront: Reset Password
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

$page_title = 'Reset Password';
$errors = [];
$success = '';
$validToken = false;
$email = '';

if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = $_GET['token'];
    $email = urldecode($_GET['email']);
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM password_resets 
            WHERE email = ? AND token = ? AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$email, $token]);
        $reset = $stmt->fetch();
        
        if ($reset) {
            $validToken = true;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if (empty($password)) {
                    $errors[] = 'Please enter a new password';
                } elseif (strlen($password) < 8) {
                    $errors[] = 'Password must be at least 8 characters long';
                } elseif ($password !== $confirm_password) {
                    $errors[] = 'Passwords do not match';
                }
                
                if (empty($errors)) {
                    try {
                        $pdo->beginTransaction();
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                        $stmt->execute([$hashedPassword, $email]);
                        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                        $pdo->commit();
                        
                        $subject = "Password Updated - " . STORE_NAME;
                        $message = "<p>Your password has been successfully updated.</p>";
                        sendEmail($email, $subject, $message);
                        
                        $success = 'Password reset successfully. You can now login.';
                        $validToken = false;
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        error_log("Password reset error: " . $e->getMessage());
                        $errors[] = 'An error occurred. Please try again.';
                    }
                }
            }
        } else {
            $errors[] = 'Invalid or expired reset link.';
        }
    } catch (PDOException $e) {
        error_log("Token validation error: " . $e->getMessage());
        $errors[] = 'An error occurred. Please try again.';
    }
} else {
    $errors[] = 'Invalid reset link.';
}

include 'includes/header.php';
?>

<main class="min-h-screen flex relative overflow-hidden bg-surface">
    <!-- Desktop Side Panel / Mobile Background -->
    <div class="absolute inset-0 lg:relative lg:w-1/2 h-full z-0">
        <img src="assets/images/login_side_panel.png" alt="Store Aesthetic" class="w-full h-full object-cover" />
        <div class="absolute inset-0 bg-primary/20 lg:bg-transparent backdrop-blur-[2px] lg:backdrop-blur-none"></div>
        <!-- Desktop Overlay Text -->
        <div class="hidden lg:flex absolute inset-0 flex-col justify-end p-xl bg-gradient-to-t from-black/60 via-transparent to-transparent">
            <h2 class="text-white font-headline-lg mb-sm">Secure Your Account</h2>
            <p class="text-white/80 font-body-md max-w-sm">Almost there. Set a strong new password to protect your account.</p>
        </div>
    </div>

    <!-- Form Side -->
    <div class="w-full lg:w-1/2 flex items-start justify-center py-xl px-md pt-[15vh] lg:pt-[15vh] z-10 relative">
        <div class="w-full max-w-[360px] bg-white/95 lg:bg-transparent backdrop-blur-md lg:backdrop-blur-none p-md lg:p-0 rounded-[2rem] lg:rounded-none shadow-2xl lg:shadow-none border border-white/20 lg:border-none">
            <div class="mb-md">
                <h1 class="font-headline-md text-headline-md text-on-background mb-xs">Reset Password</h1>
                <p class="text-on-surface-variant font-body-sm">Create a secure new password.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="bg-error/10 border border-error/20 rounded-lg p-sm mb-md">
                    <ul class="flex flex-col gap-xs list-disc pl-md">
                        <?php foreach ($errors as $error): ?>
                            <li class="text-error text-[12px]"><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-primary/10 border border-primary/20 rounded-lg p-sm mb-md flex items-center gap-sm">
                    <span class="material-symbols-outlined text-[#1A1A1A] text-[20px]">check_circle</span>
                    <p class="text-[#1A1A1A] text-[12px] font-label-sm"><?php echo $success; ?></p>
                </div>
                <a href="login.php" class="w-full bg-primary text-on-primary font-label-md py-sm rounded-lg flex items-center justify-center gap-xs hover:shadow-md transition-all active:scale-[0.98]">
                    Login Now
                </a>
            <?php elseif ($validToken): ?>
                <form method="POST" class="flex flex-col gap-md">
                    <div class="flex flex-col gap-xs">
                        <label for="password" class="font-label-sm text-on-surface">New Password</label>
                        <div class="relative group">
                            <input type="password" id="password" name="password" required minlength="8" 
                                   class="w-full bg-surface-container-low px-md py-sm pr-10 rounded-lg border border-outline-variant outline-none focus:border-primary transition-all font-body-md" 
                                   placeholder="••••••••" />
                            <button type="button" class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-[#1A1A1A] transition-colors" data-target="password">
                                <span class="material-symbols-outlined text-[20px]">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col gap-xs">
                        <label for="confirm_password" class="font-label-sm text-on-surface">Confirm New Password</label>
                        <div class="relative group">
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   class="w-full bg-surface-container-low px-md py-sm pr-10 rounded-lg border border-outline-variant outline-none focus:border-primary transition-all font-body-md" 
                                   placeholder="••••••••" />
                            <button type="button" class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-[#1A1A1A] transition-colors" data-target="confirm_password">
                                <span class="material-symbols-outlined text-[20px]">visibility</span>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-primary text-on-primary font-label-md py-sm rounded-lg hover:shadow-md transition-all active:scale-[0.98] mt-sm">
                        Update Password
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center flex flex-col gap-md">
                    <p class="text-on-surface-variant font-body-sm">Please use the link sent to your email to reset your password.</p>
                    <a href="forgot_password.php" class="text-[#1A1A1A] font-label-sm hover:underline">Request new link</a>
                </div>
            <?php endif; ?>

            <div class="mt-xl text-center">
                <a href="login.php" class="inline-flex items-center gap-xs text-[#1A1A1A] font-label-sm hover:underline">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to Login
                </a>
            </div>
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
