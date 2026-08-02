<?php
/**
 * Storefront: Forgot Password (Avazonia auth-split layout)
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
                
                if (sendPasswordResetEmail($email, $user['name'], $resetLink)) {
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

<div class="auth-split">
  <!-- Form Side -->
  <div class="auth-form-side">
    <div style="max-width: 400px; width: 100%; margin: 0 auto;">

      <a href="login.php" style="display:inline-flex;align-items:center;gap:8px;font-family:var(--f-semi);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--mid-gray);text-decoration:none;margin-bottom:40px;">
        ← Back to Login
      </a>

      <h1 style="font-family: var(--f-display); font-weight: 900; font-size: 38px; text-transform: uppercase; margin-bottom: 8px; line-height: 1; letter-spacing: -0.04em;">Forgot Password</h1>
      <p style="font-family: var(--f-body); font-size: 14px; color: var(--mid-gray); margin-bottom: 48px;">Enter the email on your account and we'll send you a reset link.</p>

      <?php if ($success): ?>
        <div style="background: #F0FDF4; border: 1px solid #BBF7D0; color: #16A34A; padding: 20px 24px; border-radius: 12px; margin-bottom: 32px; font-family: var(--f-body); font-size: 14px; line-height: 1.6;">
          <strong>📬 Email Sent!</strong><br>
          <?php echo htmlspecialchars($success); ?>
        </div>
        <div style="text-align:center; margin-top:12px;">
          <a href="login.php" class="btn-ink" style="display:inline-block;padding:14px 32px;font-size:11px;text-decoration:none;border-radius:12px;">Back to Login</a>
        </div>
      <?php else: ?>

        <?php if (!empty($errors)): ?>
          <div style="background: #fffafa; border: 1px solid #feeaea; color: var(--red); padding: 16px; font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; letter-spacing: .05em; border-radius: 4px; margin-bottom: 32px;">
            <?php foreach ($errors as $error): ?>
              [ERROR] <?php echo htmlspecialchars($error); ?><br>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST" style="display:flex;flex-direction:column;gap:24px;">
          <div class="form-group">
            <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Email Address</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="USER@DOMAIN.COM" required
              style="width:100%;height:48px;background:#fff;border:1px solid var(--light-gray);border-radius:12px;padding:0 16px;font-family:var(--f-mono);font-size:12px;color:var(--ink);outline:none;">
          </div>
          <button type="submit" class="btn-red" style="width:100%;height:48px;font-size:11px;margin-top:8px;">Send Reset Link →</button>
        </form>

      <?php endif; ?>
    </div>
  </div>

  <!-- Graphic Side -->
  <div class="auth-graphic-side">
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,transparent 40%,rgba(0,0,0,0.8));z-index:1;"></div>
    <img src="assets/images/login_side_panel.png" alt="" style="width:100%;height:100%;object-fit:cover;">
    <div style="position:absolute;bottom:80px;left:80px;right:80px;color:#fff;z-index:2;">
      <p style="font-family:var(--f-display);font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:0.2em;margin-bottom:24px;opacity:0.8;">Account Recovery</p>
      <h2 style="font-family:var(--f-display);font-weight:900;font-size:48px;text-transform:uppercase;line-height:1;letter-spacing:-0.04em;">WE'VE GOT<br>YOUR BACK.</h2>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
