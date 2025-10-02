<?php
// Include database connection and functions
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
$page_title = 'Reset Password';

$errors = [];
$success = '';
$validToken = false;
$email = '';

// Check if token and email are provided
if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = $_GET['token'];
    $email = urldecode($_GET['email']);
    
    // Validate token
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
            
            // Process password reset
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
                        // Start transaction
                        $pdo->beginTransaction();
                        
                        // Update password
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                        $stmt->execute([$hashedPassword, $email]);
                        
                        // Delete used token
                        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                        
                        // Commit transaction
                        $pdo->commit();
                        
                        // Send confirmation email
                        $subject = "Password Updated - " . STORE_NAME;
                        $message = "
                            <p>Your password has been successfully updated.</p>
                            <p>If you did not make this change, please contact us immediately at <a href='mailto:" . STORE_EMAIL . "'>" . STORE_EMAIL . "</a></p>
                        ";
                        sendEmail($email, $subject, $message);
                        
                        $success = 'Your password has been reset successfully. You can now <a href="login.php">login</a> with your new password.';
                        $validToken = false; // Hide the form
                        
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        error_log("Password reset error: " . $e->getMessage());
                        $errors[] = 'An error occurred while resetting your password. Please try again.';
                    }
                }
            }
        } else {
            $errors[] = 'Invalid or expired reset link. Please request a new one.';
        }
    } catch (PDOException $e) {
        error_log("Token validation error: " . $e->getMessage());
        $errors[] = 'An error occurred. Please try again.';
    }
} else {
    $errors[] = 'Invalid reset link. Please use the link from your email.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo htmlspecialchars(STORE_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-key me-2"></i>Reset Your Password</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <div><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($validToken): ?>
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" 
                                               required minlength="8" autocomplete="new-password">
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <div class="invalid-feedback">
                                            Please enter a password with at least 8 characters.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" 
                                               name="confirm_password" required minlength="8" autocomplete="new-password">
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <div class="invalid-feedback">
                                            Please confirm your password.
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 mb-3">
                                    <i class="fas fa-save me-2"></i>Update Password
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-center">
                            <a href="login.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i>Back to Login
                            </a>
                            <?php if (!$validToken && empty($success)): ?>
                                <span class="mx-2">|</span>
                                <a href="forgot_password.php" class="text-decoration-none">
                                    <i class="fas fa-key me-1"></i>Request New Reset Link
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle password visibility
const toggleButtons = document.querySelectorAll('.toggle-password');
toggleButtons.forEach(button => {
    button.addEventListener('click', function() {
        const input = this.previousElementSibling;
        const icon = this.querySelector('i');
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });
});

// Form validation
(function () {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Check password match
const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirm_password');
const form = document.querySelector('form');

if (form) {
    form.addEventListener('submit', function(e) {
        if (password.value !== confirmPassword.value) {
            e.preventDefault();
            confirmPassword.setCustomValidity("Passwords do not match");
            confirmPassword.reportValidity();
        } else {
            confirmPassword.setCustomValidity('');
        }
    });
}
</script>
</body>
</html>
