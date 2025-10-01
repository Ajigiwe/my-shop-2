<?php
// Session persistence test
session_start();

echo "<h1>🔍 Session Persistence Test</h1>\n";

if (isset($_POST['set_data'])) {
    // Set session data
    $_SESSION['test_user_id'] = 3;
    $_SESSION['test_pending_order'] = [
        'total_amount' => 250.00,
        'payment_method' => 'paystack',
        'test_data' => 'test_value_' . time()
    ];

    echo "<h2>✅ Session Data Set!</h2>\n";
    echo "<p>Session ID: " . session_id() . "</p>\n";
    echo "<p><a href='?'>Refresh to test persistence</a></p>\n";
    echo "<p><a href='paystack_payment.php'>Go to Paystack Payment</a></p>\n";
} else {
    echo "<h2>Current Session Data:</h2>\n";
    echo "<pre>\n";
    echo "Session ID: " . session_id() . "\n";
    echo "User ID: " . ($_SESSION['test_user_id'] ?? 'NOT SET') . "\n";
    echo "Pending Order: " . (isset($_SESSION['test_pending_order']) ? 'EXISTS' : 'MISSING') . "\n";
    if (isset($_SESSION['test_pending_order'])) {
        echo "Order Total: " . $_SESSION['test_pending_order']['total_amount'] . "\n";
        echo "Test Data: " . $_SESSION['test_pending_order']['test_data'] . "\n";
    }
    echo "</pre>\n";

    echo "<h2>Set Session Data:</h2>\n";
    echo "<form method='POST' action=''>\n";
    echo "<input type='hidden' name='set_data' value='1'>\n";
    echo "<button type='submit'>Set Test Session Data</button>\n";
    echo "</form>\n";
}

echo "<hr>\n";
echo "<p><a href='test_paystack.php'>← Back to Paystack Test</a></p>\n";
?>
