<?php
/**
 * Admin: Manage Tags
 * - CRUD for product_tags with product counts
 */
require_once '../includes/db.php';
session_start();
require_once '../includes/admin_guard.php';

$page_title = 'Manage Tags';
$errors = [];
$success = '';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    } else {
    if ($action === 'create') {
        $tag_name = sanitizeInput($_POST['tag_name'] ?? '');
        if ($tag_name === '') {
            $errors[] = 'Tag name is required';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT IGNORE INTO product_tags (tag_name) VALUES (?)');
                $stmt->execute([$tag_name]);
                $success = 'Tag created successfully';
            } catch (PDOException $e) {
                error_log('Create tag error: ' . $e->getMessage());
                $errors[] = 'Error creating tag';
            }
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['tag_id'] ?? 0);
        $tag_name = sanitizeInput($_POST['tag_name'] ?? '');
        if ($id <= 0) $errors[] = 'Invalid tag';
        if ($tag_name === '') $errors[] = 'Tag name is required';
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare('UPDATE product_tags SET tag_name = ? WHERE tag_id = ?');
                $stmt->execute([$tag_name, $id]);
                $success = 'Tag updated successfully';
            } catch (PDOException $e) {
                error_log('Update tag error: ' . $e->getMessage());
                $errors[] = 'Error updating tag (name may already exist)';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['tag_id'] ?? 0);
        if ($id <= 0) $errors[] = 'Invalid tag';
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare('DELETE FROM product_tags WHERE tag_id = ?');
                $stmt->execute([$id]);
                $success = 'Tag deleted successfully';
            } catch (PDOException $e) {
                error_log('Delete tag error: ' . $e->getMessage());
                $errors[] = 'Error deleting tag';
            }
        }
    }
    }
}

// Fetch tags with counts
$tags = [];
try {
    $stmt = $pdo->query('SELECT t.*, COUNT(r.product_id) AS product_count
                         FROM product_tags t
                         LEFT JOIN product_tag_relations r ON t.tag_id = r.tag_id
                         GROUP BY t.tag_id
                         ORDER BY t.tag_name');
    $tags = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch tags error: ' . $e->getMessage());
}

include 'includes/avazonia_header.php';
?>

<style>
.tags-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 32px; align-items: start; }
@media (max-width: 1000px) { .tags-grid { grid-template-columns: 1fr; } }
.tag-chip {
    display: inline-block; padding: 6px 14px; border: 1px solid var(--light-gray);
    border-radius: 99px; font-size: 12px; font-weight: 700; background: var(--off); color: var(--ink);
}
.help-list { margin: 0; padding-left: 18px; font-size: 12px; line-height: 1.9; color: var(--mid-gray); }
</style>

<div class="admin-header">
    <h1>Tag Management</h1>
</div>

<?php if ($success): ?>
    <div class="alert-box alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert-box alert-error">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="tags-grid">
    <div>
        <div class="panel">
            <div class="panel-header"><div class="panel-title">Add New Tag</div></div>
            <div class="panel-body">
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="create">
                    <div class="field-group" style="margin-bottom: 8px;">
                        <label class="field-label">Tag Name</label>
                        <input type="text" class="field-input" name="tag_name" required placeholder="e.g. Best Seller">
                    </div>
                    <button class="btn-red w-100" type="submit" style="justify-content: center;">+ Add Tag</button>
                    <span class="field-sub">Tags help customers find related products. You can also create tags inline in the product editor.</span>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header"><div class="panel-title">How It Works</div></div>
            <div class="panel-body">
                <ul class="help-list">
                    <li>Tags are linked to products in the product editor.</li>
                    <li>Deleting a tag removes it from all products automatically.</li>
                    <li>Tags will appear on the storefront product page.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">All Tags <span style="opacity: 0.4;">(<?php echo count($tags); ?>)</span></div>
        </div>
        <div class="table-container" style="border: none; margin-bottom: 0; border-radius: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Tag</th>
                        <th>Products</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tags)): ?>
                        <tr><td colspan="3" style="text-align: center; padding: 48px; color: var(--mid-gray);">No tags yet. Create your first tag.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($tags as $tag): ?>
                    <tr>
                        <td><span class="tag-chip"><?php echo htmlspecialchars($tag['tag_name']); ?></span></td>
                        <td>
                            <span class="status-badge" style="background: var(--off); color: var(--ink);"><?php echo (int)$tag['product_count']; ?></span>
                        </td>
                        <td style="text-align: right;">
                            <div class="d-flex justify-content-end gap-2">
                                <button class="action-btn" type="button"
                                        onclick="openEditTag(<?php echo $tag['tag_id']; ?>, '<?php echo htmlspecialchars($tag['tag_name'], ENT_QUOTES); ?>')">Edit</button>
                                <form method="POST" action="" class="d-inline" onsubmit="return confirmAction(event, 'Delete this tag? It will be removed from all products.');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="tag_id" value="<?php echo $tag['tag_id']; ?>">
                                    <button class="action-btn danger" type="submit">Del</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Tag Modal -->
<div class="modal-overlay" id="editTagModal">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="closeModal('editTagModal')">×</button>
        <div class="modal-title">Rename Tag</div>
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="tag_id" id="editTagId">
            <div class="field-group">
                <label class="field-label">Tag Name</label>
                <input type="text" class="field-input" name="tag_name" id="editTagName" required>
            </div>
            <div class="modal-btn-row">
                <button type="button" class="btn-ink" style="flex: 1; justify-content: center;" onclick="closeModal('editTagModal')">Cancel</button>
                <button type="submit" class="btn-red" style="flex: 1; justify-content: center;">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditTag(id, name) {
    document.getElementById('editTagId').value = id;
    document.getElementById('editTagName').value = name;
    openModal('editTagModal');
}
</script>

<?php include 'includes/avazonia_footer.php'; ?>
