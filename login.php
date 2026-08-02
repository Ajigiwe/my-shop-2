<?php
/**
 * Storefront: Login (auth-split layout)
 */
require_once 'includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


// Restore session from "remember me" cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $parts = explode('.', $_COOKIE['remember_me'], 3);
    if (count($parts) === 3) {
        list($rm_uid, $rm_token, $rm_sig) = $parts;
        $expected = hash_hmac('sha256', $rm_uid . '|' . $rm_token, REMEMBER_SECRET);
        if (hash_equals($expected, $rm_sig)) {
            try {
                $stmt = $pdo->prepare("SELECT user_id, name, email, role FROM users WHERE user_id = ?");
                $stmt->execute([(int)$rm_uid]);
                $rm_user = $stmt->fetch();
                if ($rm_user) {
                    $_SESSION['user_id'] = $rm_user['user_id'];
                    $_SESSION['user_name'] = $rm_user['name'];
                    $_SESSION['user_email'] = $rm_user['email'];
                    $_SESSION['user_role'] = $rm_user['role'];
                    if ($rm_user['role'] === 'admin') {
                        header('Location: admin/dashboard.php');
                        exit();
                    }
                }
            } catch(PDOException $e) {}
        }
    }
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
            $stmt = $pdo->prepare("SELECT user_id, name, email, password, role, email_verified FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && verifyPassword($password, $user['password'])) {
                if (!(int)($user['email_verified'] ?? 1)) {
                    header('Location: verify_email.php?email=' . urlencode($email) . '&required=1');
                    exit();
                }

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Carry guest cart items into the user's account cart
                asoMergeGuestCart($pdo, $user['user_id']);

                // Remember me — signed 30-day cookie
                if (isset($_POST['remember'])) {
                    $rm_token = bin2hex(random_bytes(32));
                    $rm_value = $user['user_id'] . '.' . $rm_token . '.' . hash_hmac('sha256', $user['user_id'] . '|' . $rm_token, REMEMBER_SECRET);
                    setcookie('remember_me', $rm_value, time() + (30 * 24 * 3600), '/', '', false, true);
                } else {
                    setcookie('remember_me', '', time() - 3600, '/');
                }

                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                    exit();
                } else {
                    if (!isset($_SESSION['redirect_after_login']) && isset($_SESSION['pending_checkout']) && !empty($_SESSION['pending_checkout'])) {
                        $_SESSION['redirect_after_login'] = 'checkout.php';
                    }
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

<div class="auth-split">
    <!-- Form Side -->
    <div class="auth-form-side">
        <div style="max-width: 400px; width: 100%; margin: 0 auto;">

            <h1 style="font-family: var(--f-display); font-weight: 900; font-size: 40px; text-transform: uppercase; margin-bottom: 8px; line-height: 1; letter-spacing: -0.04em;">Welcome back</h1>
            <p style="font-family: var(--f-body); font-size: 14px; color: var(--mid-gray); margin-bottom: 48px;">Please enter your details to initialize session.</p>

            <?php if (!empty($errors)): ?>
                <div style="background: #fffafa; border: 1px solid #feeaea; color: var(--red); padding: 16px; font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; letter-spacing: .05em; border-radius: 4px; margin-bottom: 32px;">
                    <?php foreach ($errors as $error): ?>
                        [ERROR] <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" style="display: flex; flex-direction: column; gap: 24px;">
                <div class="form-group">
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); margin-bottom: 8px;">Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="USER@DOMAIN.COM" required style="width: 100%; height: 48px; background: #fff; border: 1px solid var(--light-gray); border-radius: 12px; padding: 0 16px; font-family: var(--f-mono); font-size: 12px; color: var(--ink); outline: none;">
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

                <script>
                document.getElementById('toggle-password').addEventListener('click', function() {
                    const input = document.getElementById('password-input');
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.querySelector('svg').style.color = type === 'text' ? 'var(--red)' : '#BBB';
                });
                </script>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-family: var(--f-body); font-size: 12px; color: var(--mid-gray); cursor: pointer;">
                        <input type="checkbox" name="remember" style="accent-color: var(--ink);"> Remember for 30 days
                    </label>
                    <a href="forgot_password.php" style="font-family: var(--f-semi); font-size: 12px; color: var(--ink); font-weight: 600; text-decoration: underline;">Forgot password</a>
                </div>


                <button type="submit" class="btn-ink" style="width: 100%; height: 48px; font-size: 11px; margin-top: 16px;">Sign In →</button>

                <div style="margin-top: 32px; text-align: center;">
                    <p style="font-family: var(--f-body); font-size: 13px; color: var(--mid-gray);">
                        Don't have an account? <a href="register.php" style="color: var(--red); font-weight: 700; margin-left:8px; border-bottom: 1px solid var(--red); text-decoration: none;">Sign up</a>
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
            <h2 style="font-family: var(--f-display); font-weight: 900; font-size: 48px; text-transform: uppercase; line-height: 1; letter-spacing: -0.04em;">PREMIUM QUALITY.<br>DELIVERED FAST.</h2>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
