<?php
// Include database connection
require_once 'includes/db.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    // If admin, redirect to admin panel
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit();
    } else {
        header('Location: index.php');
        exit();
    }
}

// Set page title
$page_title = 'Login';

$errors = [];
$email = '';
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    // If no errors, attempt login
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT user_id, name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && verifyPassword($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Redirect based on user role
                if ($user['role'] === 'admin') {
                    // Admin users go to admin panel
                    header('Location: admin/dashboard.php');
                    exit();
                } else {
                    // Regular users go to intended page or home
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
?>

<?php include 'includes/header.php'; ?>

<!-- Login Section with Background Image -->
<section class="login-section">
    <div class="login-background"></div>
    <div class="login-overlay"></div>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-4 col-lg-4">
                <div class="card shadow login-card">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Login</h2>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </form>

                        <div class="text-center">
                            <a href="forgot_password.php" class="text-decoration-none">Forgot your password?</a>
                        </div>

                        <hr class="my-4">

                        <div class="text-center">
                            <p class="mb-0">Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Auto-focus on email field
document.getElementById('email').focus();
</script>
