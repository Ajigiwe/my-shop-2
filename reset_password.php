<?php
/**
 * Storefront: Reset Password (Avazonia auth-split layout)
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
                        
                        $stmt = $pdo->prepare("SELECT name FROM users WHERE email = ?");
                        $stmt->execute([$email]);
                        $userName = $stmt->fetchColumn();
                        sendPasswordChangedEmail($email, $userName ?: '');
                        
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

<div class="auth-split">
  <!-- Form Side -->
  <div class="auth-form-side">
    <div style="max-width: 400px; width: 100%; margin: 0 auto;">

      <h1 style="font-family: var(--f-display); font-weight: 900; font-size: 38px; text-transform: uppercase; margin-bottom: 8px; line-height: 1; letter-spacing: -0.04em;">New Password</h1>
      <p style="font-family: var(--f-body); font-size: 14px; color: var(--mid-gray); margin-bottom: 48px;">Choose a strong password for your ASO Online Market account.</p>

      <?php if (!empty($errors)): ?>
        <div style="background: #fffafa; border: 1px solid #feeaea; color: var(--red); padding: 16px; font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; letter-spacing: .05em; border-radius: 4px; margin-bottom: 32px;">
          <?php foreach ($errors as $error): ?>
            [ERROR] <?php echo htmlspecialchars($error); ?><br>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div style="background: #F0FDF4; border: 1px solid #BBF7D0; color: #16A34A; padding: 20px 24px; border-radius: 12px; margin-bottom: 32px; font-family: var(--f-body); font-size: 14px; line-height: 1.6;">
          <strong>✅ Password Updated!</strong><br>
          <?php echo htmlspecialchars($success); ?>
        </div>
        <div style="text-align:center; margin-top:12px;">
          <a href="login.php" class="btn-ink" style="display:inline-block;padding:14px 32px;font-size:11px;text-decoration:none;border-radius:12px;">Back to Login</a>
        </div>
      <?php elseif ($validToken): ?>

        <form action="reset_password.php?token=<?php echo urlencode($token); ?>&email=<?php echo urlencode($email); ?>" method="POST" style="display:flex;flex-direction:column;gap:24px;" id="resetForm">

          <div class="form-group">
            <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">New Password</label>
            <div style="position:relative;">
              <input type="password" name="password" id="pw1" placeholder="••••••••" required minlength="8"
                style="width:100%;height:48px;background:#fff;border:1px solid var(--light-gray);border-radius:12px;padding:0 48px 0 16px;font-family:var(--f-mono);font-size:12px;color:var(--ink);outline:none;">
              <button type="button" onclick="togglePw('pw1',this)" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--mid-gray);font-size:18px;">👁</button>
            </div>
            <p style="font-family:var(--f-body);font-size:11px;color:var(--mid-gray);margin-top:6px;">Minimum 8 characters</p>
          </div>

          <div class="form-group">
            <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Confirm Password</label>
            <div style="position:relative;">
              <input type="password" name="confirm_password" id="pw2" placeholder="••••••••" required minlength="8"
                style="width:100%;height:48px;background:#fff;border:1px solid var(--light-gray);border-radius:12px;padding:0 48px 0 16px;font-family:var(--f-mono);font-size:12px;color:var(--ink);outline:none;">
              <button type="button" onclick="togglePw('pw2',this)" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--mid-gray);font-size:18px;">👁</button>
            </div>
          </div>

          <!-- Password strength bar -->
          <div>
            <div style="height:4px;background:var(--light-gray);border-radius:4px;overflow:hidden;">
              <div id="strengthBar" style="height:100%;width:0%;background:var(--red);transition:width .3s,background .3s;border-radius:4px;"></div>
            </div>
            <p id="strengthLabel" style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);margin-top:4px;"></p>
          </div>

          <button type="submit" class="btn-red" style="width:100%;height:48px;font-size:11px;margin-top:8px;">Update Password →</button>
        </form>

      <?php else: ?>
        <p style="font-family:var(--f-body);font-size:14px;color:var(--mid-gray);margin-bottom:24px;">Please request a new reset link.</p>
        <a href="forgot_password.php" class="btn-red" style="display:inline-block;padding:14px 32px;font-size:11px;text-decoration:none;border-radius:12px;">Request New Link →</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Graphic Side -->
  <div class="auth-graphic-side">
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,transparent 40%,rgba(0,0,0,0.8));z-index:1;"></div>
    <img src="assets/images/login_side_panel.png" alt="" style="width:100%;height:100%;object-fit:cover;">
    <div style="position:absolute;bottom:80px;left:80px;right:80px;color:#fff;z-index:2;">
      <p style="font-family:var(--f-display);font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:0.2em;margin-bottom:24px;opacity:0.8;">Account Security</p>
      <h2 style="font-family:var(--f-display);font-weight:900;font-size:48px;text-transform:uppercase;line-height:1;letter-spacing:-0.04em;">RESET &<br>SECURE<br>YOUR ACCOUNT.</h2>
    </div>
  </div>
</div>

<script>
function togglePw(inputId, btn) {
  const input = document.getElementById(inputId);
  input.type = input.type === 'password' ? 'text' : 'password';
}

// Password strength meter
document.getElementById('pw1')?.addEventListener('input', function() {
  const v = this.value;
  const bar = document.getElementById('strengthBar');
  const label = document.getElementById('strengthLabel');
  let score = 0;
  if (v.length >= 6) score++;
  if (v.length >= 10) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^a-zA-Z0-9]/.test(v)) score++;
  const levels = [
    { pct:'20%', color:'#DC2626', text:'Very Weak' },
    { pct:'40%', color:'#EA580C', text:'Weak' },
    { pct:'60%', color:'#F59E0B', text:'Fair' },
    { pct:'80%', color:'#16A34A', text:'Good' },
    { pct:'100%', color:'#15803D', text:'Strong' },
  ];
  const lvl = levels[Math.max(0, score - 1)] || levels[0];
  bar.style.width = lvl.pct;
  bar.style.background = lvl.color;
  label.textContent = v.length > 0 ? lvl.text : '';
  label.style.color = lvl.color;
});

// Client-side match check
document.getElementById('resetForm')?.addEventListener('submit', function(e) {
  const p1 = document.getElementById('pw1').value;
  const p2 = document.getElementById('pw2').value;
  if (p1 !== p2) {
    e.preventDefault();
    alert('Passwords do not match.');
  }
});
</script>

<?php include 'includes/footer.php'; ?>
