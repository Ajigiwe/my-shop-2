<?php
// Include database connection
require_once 'includes/db.php';

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
                // In a real application, you would:
                // 1. Generate a secure reset token
                // 2. Store it in the database with expiration
                // 3. Send an email with the reset link
                
                // For demo purposes, we'll just show a success message
                $success = 'If an account with this email exists, you will receive password reset instructions shortly.';
                
                // Log the password reset request
                error_log("Password reset requested for email: $email");
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
