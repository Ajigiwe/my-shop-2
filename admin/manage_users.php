<?php
/**
 * Admin: Manage Users
 * - Admin-only actions to change roles and optionally activate/deactivate users
 * - Detects if the "active" column exists to enable status actions without breaking when missing
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';

$page_title = 'Manage Users';
$errors = [];
$success = '';

// Detect if 'active' column exists to support deactivate/activate
$hasActiveColumn = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'active'");
    $hasActiveColumn = (bool)$stmt->fetch();
} catch (PDOException $e) {
    $hasActiveColumn = false;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Invalid form submission. Please refresh and try again.';
        } else {
        // Update user role
        if ($action === 'update_role') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            $role = sanitizeInput($_POST['role'] ?? 'customer');
            if (!in_array($role, ['customer','admin'], true)) $errors[] = 'Invalid role';
            if ($user_id <= 0) $errors[] = 'Invalid user';
            if ($user_id === (int)$_SESSION['user_id'] && $role !== 'admin') {
                $errors[] = 'You cannot remove your own admin role';
            }
            if (empty($errors)) {
                $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE user_id = ?');
                $stmt->execute([$role, $user_id]);
                $success = 'User role updated';
            }
        } elseif ($action === 'deactivate' && $hasActiveColumn) {
            // Deactivate account (requires 'active' column)
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id <= 0) $errors[] = 'Invalid user';
            if ($user_id === (int)$_SESSION['user_id']) {
                $errors[] = 'You cannot deactivate your own account';
            }
            if (empty($errors)) {
                $stmt = $pdo->prepare('UPDATE users SET active = 0 WHERE user_id = ?');
                $stmt->execute([$user_id]);
                $success = 'User deactivated';
            }
        } elseif ($action === 'activate' && $hasActiveColumn) {
            // Activate account (requires 'active' column)
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id <= 0) $errors[] = 'Invalid user';
            if (empty($errors)) {
                $stmt = $pdo->prepare('UPDATE users SET active = 1 WHERE user_id = ?');
                $stmt->execute([$user_id]);
                $success = 'User activated';
            }
        }
        }
    }
} catch (PDOException $e) {
    error_log('Users management error: ' . $e->getMessage());
    $errors[] = 'Database error occurred';
}

// Fetch users
$users = [];
try {
    // Fetch user list; include 'active' if the column exists
    $extra = $hasActiveColumn ? ', active' : '';
    $stmt = $pdo->query("SELECT user_id, name, email, phone, role, created_at, updated_at$extra FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch users error: ' . $e->getMessage());
}
?>

<?php
$page_title = 'User Management';
include 'includes/avazonia_header.php';
?>

<style>
.role-select {
    padding: 6px 12px; border: 1px solid var(--light-gray); border-radius: 4px;
    font-family: var(--f-semi); font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; background: var(--off); color: var(--ink); cursor: pointer;
}
.role-select:focus { outline: none; border-color: var(--red); }
</style>

<div class="admin-header">
    <h1>User Management</h1>
</div>

<?php if (!$hasActiveColumn): ?>
    <div class="alert-box alert-info">
        <div>
            <strong>Enable Status Toggling:</strong> Run this SQL to enable activation/deactivation:
            <code style="display: block; margin-top: 10px; padding: 10px 14px; background: var(--off); border-radius: 4px;">ALTER TABLE users ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 AFTER role;</code>
        </div>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert-box alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($errors): ?>
    <div class="alert-box alert-error">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Registered Accounts <span style="opacity: 0.4;">(<?php echo count($users); ?>)</span></div>
    </div>
    <div class="table-container" style="border: none; margin-bottom: 0; border-radius: 0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User Info</th>
                    <th>Contact</th>
                    <th>Role</th>
                    <?php if ($hasActiveColumn): ?><th>Status</th><?php endif; ?>
                    <th>Joined</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div style="font-weight: 800;"><?php echo htmlspecialchars($u['name']); ?></div>
                        <div style="font-size: 10px; opacity: 0.5; font-family: var(--f-mono); margin-top: 2px;">ID: #<?php echo $u['user_id']; ?></div>
                    </td>
                    <td>
                        <div style="font-weight: 700; font-size: 12px;"><?php echo htmlspecialchars($u['email']); ?></div>
                        <div style="color: var(--mid-gray); font-size: 11px; margin-top: 2px;"><?php echo htmlspecialchars($u['phone'] ?? 'No phone'); ?></div>
                    </td>
                    <td>
                        <form method="POST" action="" class="d-flex align-items-center gap-2">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="update_role">
                            <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                            <select class="role-select" name="role" <?php echo ($u['user_id']===(int)$_SESSION['user_id'])?'disabled':''; ?> onchange="this.form.submit()">
                                <option value="customer" <?php echo $u['role']==='customer'?'selected':''; ?>>Customer</option>
                                <option value="admin" <?php echo $u['role']==='admin'?'selected':''; ?>>Admin</option>
                            </select>
                        </form>
                    </td>
                    <?php if ($hasActiveColumn): ?>
                    <td>
                        <span class="status-badge <?php echo (int)$u['active'] === 1 ? 'status-active' : 'status-suspended'; ?>">
                            <?php echo (int)$u['active'] === 1 ? 'Active' : 'Deactivated'; ?>
                        </span>
                    </td>
                    <?php endif; ?>
                    <td>
                        <div style="font-size: 11px; font-weight: 700; color: var(--mid-gray);"><?php echo date('M j, Y', strtotime($u['created_at'])); ?></div>
                    </td>
                    <td style="text-align: right;">
                        <?php if ($hasActiveColumn && $u['user_id'] !== (int)$_SESSION['user_id']): ?>
                            <form method="POST" action="" class="d-inline">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                <?php if ((int)$u['active'] === 1): ?>
                                    <input type="hidden" name="action" value="deactivate">
                                    <button class="action-btn danger" type="submit" onclick="return confirmAction(event, 'Deactivate user?');">Deactivate</button>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="activate">
                                    <button class="action-btn" type="submit" onclick="return confirmAction(event, 'Activate user?');">Activate</button>
                                <?php endif; ?>
                            </form>
                        <?php else: ?>
                            <span style="font-size: 10px; color: var(--mid-gray); font-weight: 800;">System Protected</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/avazonia_footer.php'; ?>



