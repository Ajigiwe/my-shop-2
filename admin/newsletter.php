<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$page_title = 'Newsletter';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
        $stmt->execute([$id]);
    }
    header('Location: newsletter.php?deleted=1');
    exit();
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === '1') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=newsletter_subscribers.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['email', 'name', 'subscribed_at', 'status']);

    $subscribers = $pdo->query("SELECT email, name, subscribed_at, is_active FROM newsletter_subscribers ORDER BY subscribed_at DESC")->fetchAll();
    foreach ($subscribers as $row) {
        fputcsv($out, [
            $row['email'],
            $row['name'] ?? '',
            $row['subscribed_at'],
            (int)$row['is_active'] === 1 ? 'active' : 'inactive'
        ]);
    }
    fclose($out);
    exit();
}

// Data for page
$total_active = (int)$pdo->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE is_active = 1")->fetchColumn();
$subscribers = $pdo->query("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC")->fetchAll();

include 'includes/avazonia_header.php';
?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert-box alert-success">Subscriber removed</div>
<?php endif; ?>

<div class="analytics-grid">
    <div class="stat-card-bold">
        <span class="label">Active Subscribers</span>
        <span class="value"><?php echo number_format($total_active); ?></span>
        <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray);">CURRENTLY OPTED IN</div>
    </div>
    <div class="stat-card-bold">
        <span class="label">Total Contacts</span>
        <span class="value"><?php echo number_format(count($subscribers)); ?></span>
        <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray);">ALL TIME LIST SIZE</div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Newsletter Subscribers <span style="opacity: 0.4;">(<?php echo count($subscribers); ?>)</span></div>
        <a href="newsletter.php?export=1" class="action-btn">Export CSV</a>
    </div>
    <div class="table-container" style="border: none; margin-bottom: 0; border-radius: 0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Subscribed</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscribers)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 48px; color: var(--mid-gray);">No subscribers yet.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($subscribers as $sub): ?>
                <tr>
                    <td style="font-weight: 700;"><?php echo htmlspecialchars($sub['email']); ?></td>
                    <td><?php echo htmlspecialchars($sub['name'] ?? '—'); ?></td>
                    <td style="color: var(--mid-gray);"><?php echo date('M j, Y H:i', strtotime($sub['subscribed_at'])); ?></td>
                    <td style="text-align: center;">
                        <span class="status-badge <?php echo (int)$sub['is_active'] === 1 ? 'status-active' : 'status-suspended'; ?>">
                            <?php echo (int)$sub['is_active'] === 1 ? 'Active' : 'Unsubscribed'; ?>
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <form method="POST" class="d-inline" onsubmit="return confirmAction(event, 'Remove subscriber <?php echo htmlspecialchars($sub['email']); ?>?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int)$sub['id']; ?>">
                            <button type="submit" class="action-btn danger">Del</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/avazonia_footer.php'; ?>
