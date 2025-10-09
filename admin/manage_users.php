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

<?php include '../includes/header.php'; ?>

<div class="container py-4">
  

    <!-- Back to Dashboard Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        <div>
            <h2 class="mb-0">Manage Users</h2>
        </div>
        <div>
            <!-- Spacer for centering -->
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!$hasActiveColumn): ?>
        <div class="alert alert-info">
            <strong>Note:</strong> The <code>users</code> table does not have an <code>active</code> column. To enable Activate/Deactivate actions, run this SQL in phpMyAdmin:
            <pre class="mb-0">ALTER TABLE users ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 AFTER role;</pre>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Users</h5>
            <span class="badge bg-primary"><?php echo count($users); ?></span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <?php if ($hasActiveColumn): ?><th>Status</th><?php endif; ?>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo $u['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($u['name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo htmlspecialchars($u['phone'] ?? ''); ?></td>
                                <td>
                                    <form method="POST" action="" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                        <select class="form-select form-select-sm w-auto" name="role" <?php echo ($u['user_id']===(int)$_SESSION['user_id'])?'disabled':''; ?>>
                                            <option value="customer" <?php echo $u['role']==='customer'?'selected':''; ?>>Customer</option>
                                            <option value="admin" <?php echo $u['role']==='admin'?'selected':''; ?>>Admin</option>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary" type="submit" <?php echo ($u['user_id']===(int)$_SESSION['user_id'])?'disabled':''; ?>>Update</button>
                                    </form>
                                </td>
                                <?php if ($hasActiveColumn): ?>
                                    <td>
                                        <?php if ((int)$u['active'] === 1): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <?php if ($hasActiveColumn): ?>
                                        <?php if ((int)$u['active'] === 1): ?>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="action" value="deactivate">
                                                <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Deactivate this user?');" <?php echo ($u['user_id']===(int)$_SESSION['user_id'])?'disabled':''; ?>>
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="action" value="activate">
                                                <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                                <button class="btn btn-sm btn-outline-success" type="submit" onclick="return confirm('Activate this user?');">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
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



