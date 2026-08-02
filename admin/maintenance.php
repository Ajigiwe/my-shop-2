<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$page_title = 'Maintenance Mode';

// Fetch current settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Maintenance settings fetch error: " . $e->getMessage());
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $mode = isset($_POST['maintenance_mode']) ? '1' : '0';
    $message = $_POST['maintenance_message'] ?? "We're doing a little maintenance. We'll be back shortly.";

    try {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['maintenance_mode', $mode]);
        $stmt->execute(['maintenance_message', $message]);
        header('Location: maintenance.php?saved=1');
        exit();
    } catch (PDOException $e) {
        error_log("Maintenance settings save error: " . $e->getMessage());
    }
}

$maintenance_mode = ($settings['maintenance_mode'] ?? '0') === '1';
$maintenance_message = $settings['maintenance_message'] ?? "We're doing a little maintenance. We'll be back shortly.";

include 'includes/avazonia_header.php';
?>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert-box alert-success">Maintenance settings saved</div>
<?php endif; ?>

<div class="maintenance-grid">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">Maintenance Configuration</div>
            <span class="status-badge <?php echo $maintenance_mode ? 'status-suspended' : 'status-active'; ?>">
                <?php echo $maintenance_mode ? 'Maintenance ON' : 'Maintenance OFF'; ?>
            </span>
        </div>
        <div class="panel-body">
            <form method="POST">
                <input type="hidden" name="action" value="save">
                <div class="check-row" style="padding-top: 0; margin-bottom: 32px;">
                    <input type="checkbox" name="maintenance_mode" id="maintenanceMode" class="field-check" <?php echo $maintenance_mode ? 'checked' : ''; ?>>
                    <label class="field-label" for="maintenanceMode" style="margin: 0;">Enable maintenance mode</label>
                </div>

                <div class="field-group" style="margin-bottom: 0;">
                    <label class="field-label" for="maintenanceMessage">Maintenance Message</label>
                    <textarea name="maintenance_message" id="maintenanceMessage" rows="4" class="field-input"><?php echo htmlspecialchars($maintenance_message); ?></textarea>
                    <span class="field-sub">Shown to customers while the store is offline.</span>
                </div>

                <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--light-gray);">
                    <button type="submit" class="btn-red">Save Settings</button>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><div class="panel-title">Customer Preview</div></div>
        <div class="panel-body">
            <div style="background: #0D0D0D; color: #fff; padding: 40px 24px; text-align: center;">
                <div style="font-family: var(--f-display); font-size: 22px; font-weight: 800; text-transform: uppercase; letter-spacing: -0.02em; margin-bottom: 8px;">
                    <?php echo htmlspecialchars($settings['site_name'] ?? 'ASO Online Market'); ?><span style="color: <?php echo htmlspecialchars($settings['primary_color'] ?? 'var(--red)'); ?>;">.</span>
                </div>
                <div style="font-family: var(--f-mono); font-size: 10px; letter-spacing: 0.2em; color: rgba(255,255,255,0.6); margin-bottom: 24px;">STORE OFFLINE</div>
                <div style="width: 56px; height: 56px; margin: 0 auto 24px; border: 2px solid rgba(255,255,255,0.4); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: rgba(255,255,255,0.9);">!</div>
                <p style="margin: 0; font-weight: 700; font-size: 14px;"><?php echo nl2br(htmlspecialchars($maintenance_message)); ?></p>
            </div>
            <p style="font-size: 12px; color: var(--mid-gray); margin: 12px 0 0;">This is what your storefront will display to visitors while maintenance mode is enabled.</p>
        </div>
    </div>
</div>

<?php include 'includes/avazonia_footer.php'; ?>
