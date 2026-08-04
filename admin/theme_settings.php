<?php
require_once '../includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_msg = 'Invalid form submission. Please refresh and try again.';
    } else {
    $primary_color = $_POST['primary_color'] ?? '#0d631b';
    $site_name = $_POST['site_name'] ?? 'ASO Online Market';
    $products_per_row = (int)($_POST['products_per_row'] ?? 4);

    try {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['primary_color', $primary_color]);
        $stmt->execute(['site_name', $site_name]);
        $stmt->execute(['products_per_row', $products_per_row]);
        $success_msg = 'Settings updated successfully!';
    } catch (PDOException $e) {
        $error_msg = 'Update failed: ' . $e->getMessage();
    }
    }
}

// Fetch current settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {}

$page_title = 'Brand Settings';
include 'includes/avazonia_header.php';
?>

<?php if ($success_msg): ?>
    <div class="alert-box alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="alert-box alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<div class="settings-grid">
    <div class="panel">
        <div class="panel-header"><div class="panel-title">Core Branding</div></div>
        <div class="panel-body">
            <form method="POST">
                <?php echo csrfField(); ?>
                <div class="field-group">
                    <label class="field-label">Marketplace Identity</label>
                    <input type="text" name="site_name" required value="<?php echo htmlspecialchars($settings['site_name'] ?? 'ASO Online Market'); ?>" class="field-input" placeholder="Site Name">
                </div>

                <div class="field-group">
                    <label class="field-label">Primary Brand Color</label>
                    <div class="field-grid">
                        <input type="color" name="primary_color" value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#1A1A1A'); ?>" class="color-input">
                        <input type="text" id="colorText" value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#1A1A1A'); ?>" class="field-input" style="font-family: var(--f-mono);" readonly>
                    </div>
                    <span class="field-sub">Used for buttons, accent bars, and primary UI highlights.</span>
                </div>

                <div class="field-group">
                    <label class="field-label">Product Grid Layout</label>
                    <select name="products_per_row" class="field-input">
                        <?php for($i=2; $i<=6; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo (isset($settings['products_per_row']) && $settings['products_per_row'] == $i) ? 'selected' : ''; ?>>
                                <?php echo $i; ?> Cards Per Row
                            </option>
                        <?php endfor; ?>
                    </select>
                    <span class="field-sub">Controls how many products appear side-by-side on large screens.</span>
                </div>

                <button type="submit" class="btn-red w-100" style="justify-content: center;">Deploy Settings</button>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><div class="panel-title">Interface Preview</div></div>
        <div class="panel-body">
            <div class="p-4" style="border: 1px solid var(--light-gray); background: var(--off);">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="p-3" style="background-color: var(--primary-preview);">
                        <span style="color: #fff; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; font-size: 12px;">ASO</span>
                    </div>
                    <div>
                        <div class="fw-black" style="font-size: 18px;">Premium Experience</div>
                        <div class="small" style="color: var(--mid-gray); font-weight: 700;">Live Component Simulation</div>
                    </div>
                </div>

                <div class="d-flex gap-2 mb-4">
                    <button class="btn-red" style="background-color: var(--primary-preview); border-color: var(--primary-preview);">Primary Action</button>
                    <button class="btn-ink" style="color: var(--primary-preview); border-color: var(--primary-preview); background: transparent;">Secondary</button>
                </div>

                <div class="p-3" style="border: 1px solid var(--light-gray); background: #fff;">
                    <div class="small fw-bold" style="color: var(--mid-gray); margin-bottom: 8px;">Typography &amp; Links</div>
                    <p class="small" style="margin: 0;">Your brand color will be applied to <a href="#" style="color: var(--primary-preview) !important; font-weight: 800;">hyperlinks</a> and active states.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --primary-preview: <?php echo htmlspecialchars($settings['primary_color'] ?? '#1A1A1A'); ?>;
    }
</style>

<script>
document.querySelector('input[type="color"]').addEventListener('input', function(e) {
    const val = e.target.value.toUpperCase();
    document.getElementById('colorText').value = val;
    document.documentElement.style.setProperty('--primary-preview', val);
});
</script>

<?php include 'includes/avazonia_footer.php'; ?>
