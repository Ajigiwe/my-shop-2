<?php
/**
 * Storefront: Email Verification (Avazonia verify-pending layout)
 * - Validates a ?token= link and activates the account
 * - Shows "check your inbox" state with a resend action
 */
require_once 'includes/db.php';
require_once 'includes/email_config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$page_title = 'Verify Email';
$state = 'pending';   // pending | verified | success | error
$message = '';
$errors = [];
$email = isset($_GET['email']) ? urldecode(trim($_GET['email'])) : '';

// 1) Activation link with token
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    try {
        $stmt = $pdo->prepare("SELECT user_id, name, email, role, email_verified FROM users WHERE verification_token = ? AND verification_expires > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user && !(int)$user['email_verified']) {
            $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, verification_expires = NULL WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            // Carry guest cart items into the user's account cart
            asoMergeGuestCart($pdo, $user['user_id']);

            $state = 'verified';
            $message = 'Your email has been verified. Welcome to ASO Online Market!';

            // If they were mid-checkout, send them straight back with their cart.
            if (isset($_SESSION['pending_checkout']) && !empty($_SESSION['pending_checkout'])) {
                $_SESSION['redirect_after_login'] = 'checkout.php';
            }
        } else {
            $state = 'error';
            $errors[] = 'This verification link is invalid or has expired.';
        }
    } catch(PDOException $e) {
        error_log("Verification error: " . $e->getMessage());
        $state = 'error';
        $errors[] = 'An error occurred. Please try again.';
    }
}

// 2) Resend verification email
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    if (empty($email) || !validateEmail($email)) {
        $errors[] = 'Please enter a valid email address';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id, name, email_verified FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && !(int)$user['email_verified']) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                $stmt = $pdo->prepare("UPDATE users SET verification_token = ?, verification_expires = ? WHERE user_id = ?");
                $stmt->execute([$token, $expires, $user['user_id']]);

                sendVerificationEmail($email, $user['name'], $token);
                $state = 'success';
                $message = 'A new verification link has been sent to your email.';
            } else {
                $state = 'success';
                $message = 'If an account exists and is unverified, you will receive a new link shortly.';
            }
        } catch(PDOException $e) {
            error_log("Verification resend error: " . $e->getMessage());
            $errors[] = 'An error occurred. Please try again.';
        }
    }
}

// 3) Show a "please verify" prompt when redirected from login
if ($state === 'pending' && isset($_GET['required'])) {
    $state = 'success';
    $message = 'Please verify your email before signing in. Check your inbox for the link we sent.';
}

include 'includes/header.php';
?>

<div class="auth-split">
  <!-- Form Side -->
  <div class="auth-form-side">
    <div style="max-width: 480px; width: 100%; margin: 0 auto; text-align: center;">

          <?php if ($state === 'verified'): ?>
          <?php
          $verified_target = (isset($_SESSION['pending_checkout']) && !empty($_SESSION['pending_checkout'])) ? 'checkout.php' : 'index.php';
          $verified_label  = (isset($_SESSION['pending_checkout']) && !empty($_SESSION['pending_checkout'])) ? 'Continue to Checkout →' : 'Start Shopping →';
          ?>
          <div style="width: 96px; height: 96px; background: #F0FDF4; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px; border: 2px solid #BBF7D0;">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                  <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
          </div>
          <h1 style="font-family: var(--f-display); font-weight: 900; font-size: 36px; text-transform: uppercase; margin-bottom: 8px; line-height: 1; letter-spacing: -0.04em;">Email Verified!</h1>
          <p style="font-family: var(--f-body); font-size: 15px; color: var(--mid-gray); margin-bottom: 40px; line-height: 1.6;">
              <?php echo htmlspecialchars($message); ?>
          </p>
          <a href="<?php echo $verified_target; ?>" class="btn-red" style="display:inline-block; padding: 14px 40px; font-size: 11px; text-decoration:none; border-radius:12px; margin-bottom: 20px;"><?php echo $verified_label; ?></a>
      <?php elseif ($state === 'error'): ?>
          <div style="width: 96px; height: 96px; background: #FFF0F2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px; border: 2px solid #FECDD3;">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#E5001A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
          </div>
          <h1 style="font-family: var(--f-display); font-weight: 900; font-size: 36px; text-transform: uppercase; margin-bottom: 8px; line-height: 1; letter-spacing: -0.04em;">Link Invalid</h1>
          <?php if (!empty($errors)): ?>
              <p style="font-family: var(--f-body); font-size: 15px; color: var(--red); margin-bottom: 40px; line-height: 1.6;">
                  <?php echo htmlspecialchars(implode(' ', $errors)); ?>
              </p>
          <?php endif; ?>
          <a href="login.php" class="btn-ink" style="display:inline-block; padding: 14px 40px; font-size: 11px; text-decoration:none; border-radius:12px; margin-bottom: 20px;">Back to Login</a>
      <?php else: ?>
          <div style="width: 96px; height: 96px; background: #FFF0F2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px; border: 2px solid #FECDD3;">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#E5001A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
          </div>

          <h1 style="font-family: var(--f-display); font-weight: 900; font-size: 36px; text-transform: uppercase; margin-bottom: 8px; line-height: 1; letter-spacing: -0.04em;">Check Your Inbox</h1>
          <p style="font-family: var(--f-body); font-size: 15px; color: var(--mid-gray); margin-bottom: 40px; line-height: 1.6;">
              We sent a verification link to your email. Click it to activate your account.<br>
              <strong style="color: var(--ink);">It may take a minute or two.</strong>
          </p>

          <?php if ($message): ?>
              <div style="background: #F0FDF4; border: 1px solid #BBF7D0; color: #16A34A; padding: 20px 24px; border-radius: 12px; margin-bottom: 32px; font-family: var(--f-body); font-size: 14px; line-height: 1.6; text-align: left;">
                  <?php echo htmlspecialchars($message); ?>
              </div>
          <?php endif; ?>

          <?php if (!empty($errors)): ?>
              <div style="background: #fffafa; border: 1px solid #feeaea; color: var(--red); padding: 16px; font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; letter-spacing: .05em; border-radius: 4px; margin-bottom: 32px; text-align: left;">
                  <?php foreach ($errors as $error): ?>
                      [ERROR] <?php echo htmlspecialchars($error); ?><br>
                  <?php endforeach; ?>
              </div>
          <?php endif; ?>

          <div style="background: #F9F9F9; border: 1px solid var(--light-gray); border-radius: 12px; padding: 24px; margin-bottom: 32px; text-align: left;">
              <p style="font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); margin-bottom: 12px;">WHAT'S NEXT?</p>
              <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px;">
                  <div style="width: 24px; height: 24px; background: var(--red); color:#fff; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; flex-shrink:0;">1</div>
                  <p style="font-family: var(--f-body); font-size: 14px; color: #555; margin: 0; line-height: 1.5;">Open the email from <strong>ASO Online Market</strong></p>
              </div>
              <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px;">
                  <div style="width: 24px; height: 24px; background: var(--red); color:#fff; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; flex-shrink:0;">2</div>
                  <p style="font-family: var(--f-body); font-size: 14px; color: #555; margin: 0; line-height: 1.5;">Click <strong>"Verify My Email"</strong></p>
              </div>
              <div style="display: flex; align-items: flex-start; gap: 12px;">
                  <div style="width: 24px; height: 24px; background: var(--red); color:#fff; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; flex-shrink:0;">3</div>
                  <p style="font-family: var(--f-body); font-size: 14px; color: #555; margin: 0; line-height: 1.5;">You're in! Start shopping</p>
              </div>
          </div>

          <form action="verify_email.php" method="POST" style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px; text-align: left;">
              <div class="form-group">
                  <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); margin-bottom: 8px;">Email Address</label>
                  <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required style="width: 100%; height: 48px; background: #fff; border: 1px solid var(--light-gray); border-radius: 12px; padding: 0 16px; font-family: var(--f-mono); font-size: 12px; color: var(--ink); outline: none;">
              </div>
              <button type="submit" class="btn-ink" style="width: 100%; height: 48px; font-size: 11px;">Resend Verification Email</button>
          </form>

          <a href="index.php" class="btn-ink" style="display:inline-block; padding: 14px 32px; font-size: 11px; text-decoration:none; border-radius:12px; margin-bottom: 20px;">Continue Shopping</a>
      <?php endif; ?>

      <p style="font-family: var(--f-body); font-size: 13px; color: var(--mid-gray);">
          Already verified? <a href="login.php" style="color: var(--red); font-weight: 700; text-decoration: underline;">Sign In</a>
      </p>
    </div>
  </div>

  <!-- Graphic Side -->
  <div class="auth-graphic-side">
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,transparent 40%,rgba(0,0,0,0.8));z-index:1;"></div>
    <img src="assets/images/login_side_panel.png" alt="" style="width:100%;height:100%;object-fit:cover;">
    <div style="position:absolute;bottom:80px;left:80px;right:80px;color:#fff;z-index:2;">
      <p style="font-family:var(--f-display);font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:0.2em;margin-bottom:24px;opacity:0.8;">ASO Online Market</p>
      <h2 style="font-family:var(--f-display);font-weight:900;font-size:48px;text-transform:uppercase;line-height:1;letter-spacing:-0.04em;">YOUR ACCOUNT<br>IS ALMOST<br>READY.</h2>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
