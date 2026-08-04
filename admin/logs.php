<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$page_title = 'System Logs';
$errors = [];

$log_dir = realpath(__DIR__ . '/../logs');

/**
 * Sanitize a log filename: only basenames matching email_YYYY-MM-DD.log.
 * @param string $name
 * @return string|null Clean filename or null if invalid
 */
function cleanLogFilename($name) {
    $name = basename((string)$name);
    if (preg_match('/^email_\d{4}-\d{2}-\d{2}\.log$/', $name)) {
        return $name;
    }
    return null;
}

// List available log files, newest first
$log_files = [];
if ($log_dir && is_dir($log_dir)) {
    foreach (glob($log_dir . '/email_*.log') as $path) {
        $log_files[] = basename($path);
    }
    usort($log_files, function ($a, $b) {
        return strcmp($b, $a);
    });
}

// Selected file (defaults to today's)
$selected = cleanLogFilename($_GET['file'] ?? '');
if (!$selected || !in_array($selected, $log_files, true)) {
    $selected = date('Y-m-d');
    $selected = 'email_' . $selected . '.log';
    if (!in_array($selected, $log_files, true)) {
        $selected = null;
    }
}

// Clear the selected log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    } else {
    $clear_file = cleanLogFilename($_POST['file'] ?? '');
    if ($clear_file && in_array($clear_file, $log_files, true)) {
        file_put_contents($log_dir . '/' . $clear_file, '');
    }
    header('Location: logs.php' . ($clear_file ? '?file=' . urlencode($clear_file) : ''));
    exit();
    }
}

// Read selected log contents
$log_content = '';
if ($selected) {
    $path = $log_dir . '/' . $selected;
    if (file_exists($path)) {
        $log_content = file_get_contents($path) ?: '';
    }
}

include 'includes/avazonia_header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert-box alert-error">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Email / Activity Logs</div>
        <?php if ($selected): ?>
        <form method="POST" class="d-inline">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="clear">
            <input type="hidden" name="file" value="<?php echo htmlspecialchars($selected); ?>">
            <button type="submit" class="action-btn danger" onclick="return confirmAction(event, 'Clear this log file (<?php echo htmlspecialchars($selected); ?>)?');">Clear this log</button>
        </form>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <form method="GET" style="margin-bottom: 24px;">
            <div class="field-grid" style="align-items: end;">
                <div class="field-group" style="margin-bottom: 0;">
                    <label class="field-label" for="logFileSelect">Select Log File</label>
                    <select name="file" id="logFileSelect" class="field-input">
                        <?php if (empty($log_files)): ?>
                            <option value="">No log files found</option>
                        <?php endif; ?>
                        <?php foreach ($log_files as $file): ?>
                            <option value="<?php echo htmlspecialchars($file); ?>" <?php echo ($file === $selected) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($file); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group" style="margin-bottom: 0;">
                    <button type="submit" class="btn-ink w-100" style="justify-content: center;">View</button>
                </div>
            </div>
        </form>

        <?php if ($selected): ?>
            <div style="font-family: var(--f-mono); font-size: 12px; font-weight: 700; color: var(--mid-gray); margin-bottom: 8px;">
                <?php echo htmlspecialchars($selected); ?>
                <?php if ($log_content !== ''): ?>
                    <span style="opacity: 0.6;">— <?php echo number_format(strlen($log_content)); ?> bytes</span>
                <?php endif; ?>
            </div>
            <pre style="border: 1px solid var(--light-gray); border-radius: 8px; padding: 16px; margin-bottom: 0; max-height: 500px; overflow: auto; background: #fff; font-family: var(--f-mono); font-size: 12px; white-space: pre-wrap; box-sizing: border-box;"><?php echo htmlspecialchars($log_content !== '' ? $log_content : 'Log file is empty.'); ?></pre>
        <?php else: ?>
            <div style="text-align: center; padding: 48px 0; color: var(--mid-gray);">
                <div style="font-weight: 800; margin-top: 8px;">No log file available</div>
                <div class="small">No email logs were found in the logs folder.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/avazonia_footer.php'; ?>
