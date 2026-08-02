<?php
/**
 * Stock Alert Notification System
 * Run manually or via cron: php admin/includes/stock_alerts.php
 * Or include from a scheduled task.
 */
require_once __DIR__ . '/../includes/db.php';

function sendStockAlert($product, $threshold) {
    $adminEmail = 'aso@admin.gh';
    $siteName = 'ASO Online Market';
    $productName = $product['name'] ?? 'Unknown Product';
    $sku = $product['sku'] ?? 'N/A';
    $stock = (int)($product['stock_quantity'] ?? 0);
    $alertEmail = $product['alert_email'] ?? $adminEmail;

    $subject = "[Low Stock Alert] {$productName} — {$stock} remaining (threshold: {$threshold})";
    $message = "Hi,\n\n";
    $message .= "The product \"{$productName}\" (SKU: {$sku}) has dropped to {$stock} units, which is at or below the low-stock threshold of {$threshold}.\n\n";
    $message .= "Current stock: {$stock}\n";
    $message .= "Threshold: {$threshold}\n";
    $message .= "Product page: " . SITE_URL . "product.php?id=" . $product['product_id'] . "\n\n";
    $message .= "Please restock soon to avoid running out.\n\n";
    $message .= "— {$siteName} Admin";

    $headers = "From: noreply@" . str_replace(['http://', 'https://'], '', SITE_URL) . "\r\n";
    $headers .= "Reply-To: {$adminEmail}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $sent = @mail($alertEmail, $subject, $message, $headers);
    return $sent;
}

function checkAndSendAlerts($pdo) {
    $alertsSent = 0;
    $alertsFailed = 0;

    try {
        $stmt = $pdo->prepare("
            SELECT p.* FROM products p
            WHERE p.status = 'published'
            AND p.stock_quantity > 0
            AND p.stock_quantity <= COALESCE(p.low_stock_threshold, 5)
            AND p.alert_enabled = 1
        ");
        $stmt->execute();
        $products = $stmt->fetchAll();

        foreach ($products as $product) {
            $threshold = (int)($product['low_stock_threshold'] ?? 5);
            $alertEmail = $product['alert_email'] ?? null;

            // Check if we already sent an alert recently (within last 24 hours)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM stock_alert_log
                WHERE product_id = ?
                AND notified = 1
                AND notified_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([$product['product_id']]);
            $recentAlerts = (int)$stmt->fetchColumn();

            if ($recentAlerts > 0) {
                continue; // Already notified recently
            }

            // Log the alert
            $stmt = $pdo->prepare("
                INSERT INTO stock_alert_log (product_id, threshold, current_stock, notified)
                VALUES (?, ?, ?, 1)
            ");
            $stmt->execute([$product['product_id'], $threshold, (int)$product['stock_quantity']]);

            // Send email
            $sent = sendStockAlert($product, $threshold);
            if ($sent) {
                $alertsSent++;
            } else {
                $alertsFailed++;
                error_log("Stock alert email failed for product_id={$product['product_id']}");
            }
        }
    } catch (PDOException $e) {
        error_log("Stock alert check error: " . $e->getMessage());
    }

    return ['sent' => $alertsSent, 'failed' => $alertsFailed];
}

// Run if called directly from CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "Running stock alert check...\n";
    $result = checkAndSendAlerts($pdo);
    echo "Alerts sent: {$result['sent']}, Failed: {$result['failed']}\n";
}

return ['checkAndSendAlerts' => 'checkAndSendAlerts'];