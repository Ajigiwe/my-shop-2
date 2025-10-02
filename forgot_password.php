<?php
// Include database connection and email configuration
require_once 'includes/db.php';
require_once 'includes/email_config.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Set page title
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
            // Check if email exists
            $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate secure token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store token in database
                $stmt = $pdo->prepare("
                    INSERT INTO password_resets (email, token, expires_at) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        token = VALUES(token),
                        created_at = NOW(),
                        expires_at = VALUES(expires_at)
                ");
                $stmt->execute([$email, $token, $expires]);
                
                // Send reset email
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
                    $success = 'Password reset link has been sent to your email. It will expire in 1 hour.';
                    // Clear the email field after successful submission
                    $email = '';
                } else {
                    $errors[] = 'Failed to send reset email. Please try again.';
                }
                
                // Log the password reset request
                error_log("Password reset email sent to: $email");
            } else {
                // Don't reveal if email exists or not for security
                $success = 'If an account with this email exists, you will receive password reset instructions shortly.';
            }
        } catch(PDOException $e) {
            error_log("Forgot password error: " . $e->getMessage());
            $errors[] = 'An error occurred. Please try again later.';
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<!-- Forgot Password Section with Background Image -->
<section class="login-section">
    <div class="login-background"></div>
    <div class="login-overlay"></div>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow login-card">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Forgot Password</h2>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center mb-4">
                                Enter your email address and we'll send you instructions to reset your password.
                            </p>

                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 mb-3">
                                    <i class="fas fa-paper-plane me-2"></i>Send Reset Instructions
                                </button>
                            </form>
                        <?php endif; ?>

                        <div class="text-center">
                            <a href="login.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i>Back to Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
