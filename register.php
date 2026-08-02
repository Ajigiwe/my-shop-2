<?php
/**
 * Storefront: Register (Avazonia auth-split layout)
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
            $verification_token = bin2hex(random_bytes(32));
            $token_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role, email_verified, verification_token, verification_expires) VALUES (?, ?, ?, ?, 'customer', 0, ?, ?)");
            $stmt->execute([$name, $email, $hashed_password, $phone, $verification_token, $token_expires]);

            $user_id = $pdo->lastInsertId();

            sendVerificationEmail($email, $name, $verification_token);

            header('Location: verify_email.php?email=' . urlencode($email));
            exit();
        } catch(PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors[] = 'An error occurred during registration. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="auth-split reverse">
    <!-- Form Side -->
    <div class="auth-form-side">
        <div style="max-width: 400px; width: 100%; margin: 0 auto;">

            <h1 style="font-family: var(--f-display); font-weight: 900; font-size: 40px; text-transform: uppercase; margin-bottom: 8px; line-height: 1; letter-spacing: -0.04em;">Join the Drop</h1>
            <p style="font-family: var(--f-body); font-size: 14px; color: var(--mid-gray); margin-bottom: 48px;">Create your account to access exclusive tech.</p>

            <?php if (!empty($errors)): ?>
                <div style="background: #fffafa; border: 1px solid #feeaea; color: var(--red); padding: 16px; font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; letter-spacing: .05em; border-radius: 4px; margin-bottom: 32px;">
                    <?php foreach ($errors as $error): ?>
                        [ERROR] <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" style="display: flex; flex-direction: column; gap: 24px;">
                <div class="form-group">
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); margin-bottom: 8px;">Full Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" placeholder="KWAME MENSAH" required style="width: 100%; height: 48px; background: #fff; border: 1px solid var(--light-gray); border-radius: 12px; padding: 0 16px; font-family: var(--f-mono); font-size: 12px; color: var(--ink); outline: none;">
                </div>

                <div class="form-group">
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); margin-bottom: 8px;">Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="USER@DOMAIN.COM" required style="width: 100%; height: 48px; background: #fff; border: 1px solid var(--light-gray); border-radius: 12px; padding: 0 16px; font-family: var(--f-mono); font-size: 12px; color: var(--ink); outline: none;">
                </div>

                <div class="form-group">
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); margin-bottom: 8px;">Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="024 000 0000" required style="width: 100%; height: 48px; background: #fff; border: 1px solid var(--light-gray); border-radius: 12px; padding: 0 16px; font-family: var(--f-mono); font-size: 12px; color: var(--ink); outline: none;">
                </div>

                <div class="form-group">
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); margin-bottom: 8px;">Password</label>
                    <div class="password-wrapper" style="position: relative;">
                        <input type="password" name="password" id="password-input" placeholder="••••••••" required style="width: 100%; height: 48px; background: #fff; border: 1px solid var(--light-gray); border-radius: 12px; padding: 0 48px 0 16px; font-family: var(--f-mono); font-size: 12px; color: var(--ink); outline: none;">
                        <button type="button" id="toggle-password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #BBB; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px;">
                            <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); margin-bottom: 8px;">Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="••••••••" required style="width: 100%; height: 48px; background: #fff; border: 1px solid var(--light-gray); border-radius: 12px; padding: 0 16px; font-family: var(--f-mono); font-size: 12px; color: var(--ink); outline: none;">
                </div>

                <script>
                document.getElementById('toggle-password').addEventListener('click', function() {
                    const input = document.getElementById('password-input');
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.querySelector('svg').style.color = type === 'text' ? 'var(--red)' : '#BBB';
                });
                </script>

                <label style="display: flex; align-items: flex-start; gap: 8px; font-family: var(--f-body); font-size: 12px; color: var(--mid-gray); cursor: pointer;">
                    <input type="checkbox" name="terms" required style="accent-color: var(--ink); margin-top: 2px;"> I agree to the <a href="legal/terms-conditions.php" style="color: var(--ink); font-weight: 600; text-decoration: underline;">Terms</a> and <a href="legal/privacy-policy.php" style="color: var(--ink); font-weight: 600; text-decoration: underline;">Privacy Policy</a>.
                </label>

                <button type="submit" class="btn-red" style="width: 100%; height: 48px; font-size: 11px; margin-top: 16px;">Create Account →</button>

                <div style="margin-top: 32px; text-align: center;">
                    <p style="font-family: var(--f-body); font-size: 13px; color: var(--mid-gray);">
                        Already have an account? <a href="login.php" style="color: var(--red); font-weight: 700; margin-left:8px; border-bottom: 1px solid var(--red); text-decoration: none;">Login here</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <!-- Graphic Side -->
    <div class="auth-graphic-side">
        <div style="position: absolute; inset: 0; background: linear-gradient(to bottom, transparent 40%, rgba(0,0,0,0.8)); z-index: 1;"></div>
        <img src="assets/images/login_side_panel.png" alt="ASO Brand Photography" style="width: 100%; height: 100%; object-fit: cover;">
        <div style="position: absolute; bottom: 80px; left: 80px; right: 80px; color: #fff; z-index: 2;">
            <p style="font-family: var(--f-display); font-weight: 900; font-size: 12px; text-transform: uppercase; letter-spacing: 0.2em; margin-bottom: 24px; opacity: 0.8;">ASO Online Market</p>
            <h2 style="font-family: var(--f-display); font-weight: 900; font-size: 48px; text-transform: uppercase; line-height: 1; letter-spacing: -0.04em;">JOIN THE<br>COMMUNITY.</h2>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
