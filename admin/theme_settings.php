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

// Fetch current settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {}

$page_title = 'Brand Settings';
include 'includes/header-new.php';
?>

<div class="row g-4">
    <!-- Config Column -->
    <div class="col-lg-6">
        <div class="admin-card animate-up">
            <div class="admin-card-header">
                <h5 class="admin-card-title mb-0">Core Branding</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($success_msg): ?>
                    <div class="alert alert-success border-0 rounded-4 mb-4 small fw-bold">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <div class="mb-3">
                        <label class="stat-label">Marketplace Identity</label>
                        <input type="text" name="site_name" required value="<?php echo htmlspecialchars($settings['site_name'] ?? 'ASO Online Market'); ?>" class="form-control rounded-3 py-2" placeholder="Site Name">
                    </div>

                    <div class="mb-3">
                        <label class="stat-label">Primary Brand Color</label>
                        <div class="d-flex gap-2">
                            <input type="color" name="primary_color" value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#1A1A1A'); ?>" class="form-control form-control-color border-0 p-0 rounded-3 shadow-sm" style="width: 50px; height: 50px;">
                            <input type="text" id="colorText" value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#1A1A1A'); ?>" class="form-control rounded-3 flex-grow-1 font-monospace" readonly>
                        </div>
                        <p class="text-[10px] text-muted fw-bold uppercase tracking-widest mt-2">Used for buttons, accent bars, and primary UI highlights.</p>
                    </div>

                    <div class="mb-3">
                        <label class="stat-label">Product Grid Layout</label>
                        <select name="products_per_row" class="form-select rounded-3 py-2 fw-bold">
                            <?php for($i=2; $i<=6; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo (isset($settings['products_per_row']) && $settings['products_per_row'] == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?> Cards Per Row
                                </option>
                            <?php endfor; ?>
                        </select>
                        <p class="text-[10px] text-muted fw-bold uppercase tracking-widest mt-2">Controls how many products appear side-by-side on large screens.</p>
                    </div>

                    <div class="mt-4 pt-2">
                        <button type="submit" class="btn-premium w-100 py-3">
                            <i class="fas fa-save me-2"></i>Deploy Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Preview Column -->
    <div class="col-lg-6">
        <div class="admin-card animate-up" style="animation-delay: 0.2s;">
            <div class="admin-card-header">
                <h5 class="admin-card-title mb-0">Interface Preview</h5>
            </div>
            <div class="card-body p-4">
                <div class="p-4 rounded-4 border bg-light">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="bg-primary-preview p-3 rounded-4 shadow-sm">
                            <i class="fas fa-shopping-bag text-white fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-black text-[#1A1A1A]-preview fs-5">Premium Experience</div>
                            <div class="small text-muted fw-bold">Live Component Simulation</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mb-4">
                        <button class="btn btn-primary-preview px-4 py-2 rounded-pill fw-bold small text-white border-0 shadow-sm" style="background-color: var(--primary-preview) !important;">Primary Action</button>
                        <button class="btn px-4 py-2 rounded-pill fw-bold small border-2" style="border-color: var(--primary-preview) !important; color: var(--primary-preview) !important;">Secondary</button>
                    </div>

                    <div class="p-3 bg-white rounded-3 border">
                        <div class="small text-muted fw-bold mb-2">Typography & Links</div>
                        <p class="small mb-0">Your brand color will be applied to <a href="#" class="text-[#1A1A1A]-preview fw-bold" style="color: var(--primary-preview) !important;">hyperlinks</a> and active states.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --primary-preview: <?php echo htmlspecialchars($settings['primary_color'] ?? '#1A1A1A'); ?>;
    }
    .text-[#1A1A1A]-preview { color: var(--primary-preview) !important; }
    .bg-primary-preview { background-color: var(--primary-preview) !important; }
</style>

<script>
document.querySelector('input[type="color"]').addEventListener('input', function(e) {
    const val = e.target.value.toUpperCase();
    document.getElementById('colorText').value = val;
    document.documentElement.style.setProperty('--primary-preview', val);
});
</script>

<?php include 'includes/footer-new.php'; ?>
