<?php
/**
 * User: Profile Settings (Avazonia account layout)
 */
require_once '../includes/db.php';
require_once '../includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = 'Settings';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email)) $errors[] = 'Email is required';

    if (empty($errors)) {
        try {
            $sql = "UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?";
            $params = [$name, $email, $phone, $address, $user_id];

            if (!empty($new_password)) {
                if (password_verify($old_password, $user['password'])) {
                    if ($new_password === $confirm_password) {
                        $sql = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, password = ? WHERE user_id = ?";
                        $params = [$name, $email, $phone, $address, password_hash($new_password, PASSWORD_DEFAULT), $user_id];
                    } else {
                        $errors[] = "New passwords don't match";
                    }
                } else {
                    $errors[] = "Current password is incorrect";
                }
            }

            if (empty($errors)) {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $success = "Settings updated successfully!";

                $_SESSION['user_name'] = $name;
                $user['name'] = $name;
                $user['email'] = $email;
                $user['phone'] = $phone;
                $user['address'] = $address;
            }
        } catch(PDOException $e) {
            $errors[] = "Update failed: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<section class="account-page" style="padding: 100px 0 80px; background: #fafafa; min-height: 80vh;">
    <div class="container" style="max-width: 1100px;">

        <!-- Breadcrumb & Header -->
        <nav style="margin-bottom: 32px;">
            <div style="font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); letter-spacing: 0.1em; display: flex; align-items: center; gap: 8px;">
                <a href="<?php echo $base; ?>index.php" style="color: inherit; text-decoration: none;">ASO</a>
                <span>/</span>
                <a href="dashboard.php" style="color: inherit; text-decoration: none;">Account</a>
                <span>/</span>
                <span style="color: var(--ink);">Settings</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 16px;">
                <div>
                    <a href="dashboard.php" style="display: inline-block; font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); text-decoration: none; margin-bottom: 12px; letter-spacing: 0.05em;">← Back to Account</a>
                    <h1 style="font-family: var(--f-display); font-weight: 800; font-size: 32px; margin: 0; color: var(--ink); letter-spacing: -0.02em;">Profile Settings</h1>
                </div>
            </div>
        </nav>

        <div class="account-grid" style="display: grid; grid-template-columns: 240px 1fr; gap: 48px;">

            <!-- Sidebar -->
            <?php include '_sidebar.php'; ?>

            <!-- Main Content -->
            <main style="min-width: 0;">
                <div style="background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 40px; max-width: 680px;">
                    <?php if ($success): ?>
                        <div style="background: #e6f7ec; color: #00a854; padding: 16px; border-radius: 8px; margin-bottom: 32px; font-size: 13px; font-weight: 500; border-left: 4px solid #00a854;">
                            ✅ <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div style="background: #fff1f0; color: #cf1322; padding: 16px; border-radius: 8px; margin-bottom: 32px; font-size: 13px; font-weight: 500; border-left: 4px solid #ff4d4f;">
                            <?php foreach ($errors as $error): ?>
                                <div>⚠️ <?php echo htmlspecialchars($error); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" style="display: flex; flex-direction: column; gap: 24px;">
                        <?php echo csrfField(); ?>
                        <div>
                            <h3 style="font-family: var(--f-mono); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--ink); border-bottom: 1px solid var(--light-gray); padding-bottom: 10px; margin-bottom: 20px;">Basic information</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div>
                                    <label style="display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px; letter-spacing: 0.05em;">Full Name</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required style="width: 100%; padding: 14px 16px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box;">
                                </div>
                                <div>
                                    <label style="display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px; letter-spacing: 0.05em;">Phone Number</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" style="width: 100%; padding: 14px 16px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box;">
                                </div>
                            </div>

                            <div style="margin-top: 20px;">
                                <label style="display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px; letter-spacing: 0.05em;">Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required style="width: 100%; padding: 14px 16px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box;">
                            </div>

                            <div style="margin-top: 20px;">
                                <label style="display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px; letter-spacing: 0.05em;">Default Address</label>
                                <textarea name="address" rows="3" style="width: 100%; padding: 14px 16px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box; resize: vertical;"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div style="padding-top: 24px; border-top: 1px solid #eee;">
                            <h3 style="font-family: var(--f-mono); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--ink); border-bottom: 1px solid var(--light-gray); padding-bottom: 10px; margin-bottom: 20px;">Security</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                                <div>
                                    <label style="display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px; letter-spacing: 0.05em;">Current Password</label>
                                    <input type="password" name="old_password" placeholder="••••••••" style="width: 100%; padding: 14px 16px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box;">
                                </div>
                                <div>
                                    <label style="display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px; letter-spacing: 0.05em;">New Password</label>
                                    <input type="password" name="new_password" placeholder="••••••••" style="width: 100%; padding: 14px 16px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box;">
                                </div>
                                <div>
                                    <label style="display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px; letter-spacing: 0.05em;">Confirm New Password</label>
                                    <input type="password" name="confirm_password" placeholder="••••••••" style="width: 100%; padding: 14px 16px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 14px; box-sizing: border-box;">
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 12px;">
                            <button type="submit" style="height: 52px; padding: 0 40px; background: var(--ink); color: #fff; border: none; border-radius: 8px; font-family: var(--f-display); font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; cursor: pointer; transition: 0.2s;">Save Changes</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</section>

<style>
    input:focus, textarea:focus { outline: none; border-color: var(--red) !important; box-shadow: 0 0 0 4px rgba(229,0,26,0.05); }
    button:hover { background: var(--red) !important; transform: translateY(-1px); box-shadow: 0 10px 20px rgba(229,0,26,0.1); }

    @media (max-width: 900px) {
        .account-page { padding: 60px 0 60px !important; }
        .account-grid { grid-template-columns: 1fr !important; gap: 32px !important; }
        .account-sidebar { position: static !important; }

        .account-page form > div > div[style*="grid-template-columns"] {
            grid-template-columns: 1fr !important;
            gap: 16px !important;
        }

        main > div { padding: 24px !important; }
        h1 { font-size: 24px !important; }
    }
</style>

<?php include '../includes/footer.php'; ?>
