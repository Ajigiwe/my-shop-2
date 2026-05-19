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
include 'includes/header-new.php';
?>

<div class="row g-4">
    <div class="col-12">
        <?php if (!$hasActiveColumn): ?>
            <div class="alert alert-info border-0 rounded-4 mb-4 small fw-bold animate-up">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Enable Status Toggling:</strong> Run this SQL to enable activation/deactivation:
                <code class="d-block mt-2 p-2 bg-light rounded">ALTER TABLE users ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 AFTER role;</code>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success border-0 rounded-4 mb-4 small fw-bold animate-up">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-danger border-0 rounded-4 mb-4 small fw-bold animate-up">
                <ul class="mb-0">
                    <?php foreach($errors as $e): ?><li><?php echo $e; ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="admin-card animate-up">
            <div class="admin-card-header">
                <h5 class="admin-card-title mb-0">Registered Accounts <span class="badge bg-light text-dark ms-2 rounded-pill"><?php echo count($users); ?></span></h5>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>User Info</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <?php if ($hasActiveColumn): ?><th>Status</th><?php endif; ?>
                            <th>Joined</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="fw-black text-[13px]"><?php echo htmlspecialchars($u['name']); ?></div>
                                <div class="small text-muted fw-bold uppercase tracking-widest text-[9px] mt-0.5">ID: #<?php echo $u['user_id']; ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-[13px]"><?php echo htmlspecialchars($u['email']); ?></div>
                                <div class="text-muted text-[11px] mt-0.5"><?php echo htmlspecialchars($u['phone'] ?? 'No phone'); ?></div>
                            </td>
                            <td>
                                <form method="POST" action="" class="d-flex align-items-center gap-1">
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                    <select class="form-select form-select-sm rounded-pill bg-light border-0 fw-bold text-[11px] px-2 py-0.5" name="role" <?php echo ($u['user_id']===(int)$_SESSION['user_id'])?'disabled':''; ?> onchange="this.form.submit()">
                                        <option value="customer" <?php echo $u['role']==='customer'?'selected':''; ?>>Customer</option>
                                        <option value="admin" <?php echo $u['role']==='admin'?'selected':''; ?>>Admin</option>
                                    </select>
                                </form>
                            </td>
                            <?php if ($hasActiveColumn): ?>
                            <td>
                                <span class="badge rounded-pill px-2 py-1 bg-<?php echo (int)$u['active'] === 1 ? 'success' : 'secondary'; ?>-subtle text-<?php echo (int)$u['active'] === 1 ? 'success' : 'secondary'; ?> fw-bold text-[10px]">
                                    <?php echo (int)$u['active'] === 1 ? 'Active' : 'Deactivated'; ?>
                                </span>
                            </td>
                            <?php endif; ?>
                            <td>
                                <div class="text-[11px] fw-bold text-muted"><?php echo date('M j, Y', strtotime($u['created_at'])); ?></div>
                            </td>
                            <td class="text-end">
                                <?php if ($hasActiveColumn && $u['user_id'] !== (int)$_SESSION['user_id']): ?>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                        <?php if ((int)$u['active'] === 1): ?>
                                            <input type="hidden" name="action" value="deactivate">
                                            <button class="btn-premium-outline px-2 py-1 text-danger border-danger/20 text-[12px]" type="submit" onclick="return confirmAction(event, 'Deactivate user?');">
                                                <i class="fas fa-user-slash"></i>
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="activate">
                                            <button class="btn-premium-outline px-2 py-1 text-success border-success/20 text-[12px]" type="submit" onclick="return confirmAction(event, 'Activate user?');">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <span class="text-[10px] text-muted fw-bold">System Protected</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer-new.php'; ?>



