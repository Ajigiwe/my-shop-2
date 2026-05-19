<?php
// Quick test script to trigger sendStatusUpdateEmail via CLI
require_once __DIR__ . '/../includes/email_config.php';

$order_number = $argv[1] ?? 'TEST12345';
$status = $argv[2] ?? 'shipped';
$to = $argv[3] ?? (defined('STORE_EMAIL') ? STORE_EMAIL : 'minatoflash82@gmail.com');
$name = $argv[4] ?? 'Test User';

echo "Triggering status email to: $to for order: $order_number with status: $status\n";

$result = sendStatusUpdateEmail($order_number, $status, $to, $name);

if ($result) {
    echo "Email sent (sendStatusUpdateEmail returned true)\n";
} else {
    echo "Email failed (sendStatusUpdateEmail returned false). Check logs.\n";
}

echo "Done.\n";
