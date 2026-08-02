<?php
/**
 * Paystack Checkout (Avazonia)
 * Opens the Paystack Inline popup so customers pay without leaving the store.
 * Successful payments return to verify_payment.php to confirm the order.
 */

// Include database connection and Paystack configuration
require_once 'includes/db.php';
require_once 'vendor/autoload.php';
require_once 'includes/paystack_config.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? null;

// Validate order ID
if (!$order_id) {
    header('Location: cart.php');
    exit();
}

// Handle payment cancellation from the popup's onClose callback:
// cancel the order, restore the cart, and return to checkout so nothing "goes through".
if (isset($_GET['cancel']) && $_GET['cancel'] === '1') {
    try {
        $stmt = $pdo->prepare("UPDATE orders SET order_status = 'cancelled', payment_status = 'failed' WHERE order_id = ? AND user_id = ? AND payment_status = 'pending'");
        $stmt->execute([(int)$order_id, $user_id]);

        // Restore cart items so the customer can review or retry
        $st = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $st->execute([(int)$order_id]);
        $items = $st->fetchAll(PDO::FETCH_ASSOC);
        if ($items) {
            $ins = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
            foreach ($items as $it) {
                $ins->execute([$user_id, $it['product_id'], $it['quantity']]);
            }
        }
    } catch (PDOException $e) {
        error_log("Cancel order error: " . $e->getMessage());
    }
    header('Location: checkout.php?cancelled=1');
    exit();
}

// Get order details
try {
    // Normal database query
    $stmt = $pdo->prepare("
        SELECT o.*, u.name, u.email, u.phone
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        // Split name if needed, or just use as is
        $order['first_name'] = $order['name'];
        $order['last_name'] = '';
    } else {
        header('Location: cart.php');
        exit();
    }

    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    header('Location: cart.php');
    exit();
}

// Generate a unique reference for this transaction and persist it so
// verify_payment.php can look up the order when the popup reports success.
$reference = generateTransactionReference();
try {
    $stmt = $pdo->prepare("UPDATE orders SET payment_reference = ? WHERE order_id = ?");
    $stmt->execute([$reference, $order_id]);
} catch (PDOException $e) {
    error_log("Failed to store payment reference: " . $e->getMessage());
}

$paystack_public_key = getPaystackPublicKey();

$page_title = 'Paystack Payment';
include 'includes/header.php';
?>

<main style="padding: 120px 24px; min-height: 80vh; display: flex; align-items: center; justify-content: center; background: #fff;">
    <?php if (isset($error_message) || empty($paystack_public_key)): ?>
        <div style="max-width: 560px; width: 100%; text-align: center;">
            <div style="width: 80px; height: 80px; background: rgba(229,0,26,.1); border: 2px solid var(--red); border-radius: 100px; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px; font-size: 32px; color: var(--red);">!</div>

            <h1 style="font-family: var(--f-display); font-size: clamp(40px, 8vw, 56px); font-weight: 900; letter-spacing: -0.04em; line-height: 0.9; margin-bottom: 12px;">Payment<br><span style="color: var(--red);">Error</span></h1>

            <p style="font-family: var(--f-mono); font-size: 10px; color: #aaa; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 32px;">Could not initialize payment</p>

            <div style="background: #f9f9f9; border: 1px solid #eee; padding: 24px; border-radius: 12px; margin-bottom: 32px; text-align: left; font-size: 14px; line-height: 1.7;">
                <p style="color: var(--red); font-weight: 700; margin-bottom: 12px;"><?php echo htmlspecialchars($error_message ?? 'Payment configuration error'); ?></p>
                <p style="color: var(--mid-gray); font-size: 13px;">Order ID: <?php echo htmlspecialchars($order_id); ?></p>
                <p style="color: var(--mid-gray); font-size: 13px;">Amount: <?php echo formatCurrency($order['total_amount']); ?></p>
            </div>

            <a href="checkout.php" class="btn-ink" style="display:inline-block; padding: 14px 32px; font-size: 11px; text-decoration: none; border-radius: 12px;">← Back to Checkout</a>
        </div>
    <?php else: ?>
        <div style="max-width: 560px; width: 100%; text-align: center;">
            <div style="width: 80px; height: 80px; border: 3px solid var(--light-gray); border-top-color: var(--red); border-radius: 100px; margin: 0 auto 32px; animation: paystackSpin 1s linear infinite;"></div>

            <h1 style="font-family: var(--f-display); font-size: clamp(40px, 8vw, 56px); font-weight: 900; letter-spacing: -0.04em; line-height: 0.9; margin-bottom: 12px;">Payment<br><span style="color: var(--red);">Window</span></h1>

            <p style="font-family: var(--f-mono); font-size: 10px; color: #aaa; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 32px;">Opening secure payment popup...</p>

            <button id="paystack-retry" style="display:none;" onclick="pays()">Open Payment</button>
        </div>
    <?php endif; ?>
</main>

<?php if (empty($error_message) && !empty($paystack_public_key)): ?>
<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
    var pays = function() {
        var handler = PaystackPop.setup({
            key: '<?php echo htmlspecialchars($paystack_public_key, ENT_QUOTES); ?>',
            email: '<?php echo htmlspecialchars($order['email'], ENT_QUOTES); ?>',
            amount: <?php echo (int)formatAmountForPaystack($order['total_amount']); ?>,
            currency: 'GHS',
            ref: '<?php echo htmlspecialchars($reference, ENT_QUOTES); ?>',
            metadata: <?php echo json_encode(['order_id' => $order_id, 'user_id' => $user_id]); ?>,
            callback: function(response) {
                // Payment succeeded — verify server-side, then confirm the order.
                window.location.href = '<?php echo SITE_URL; ?>verify_payment.php?reference=' + response.reference;
            },
            onClose: function() {
                // User closed the popup without paying. Cancel the order, restore the
                // cart, and return to checkout so nothing "goes through".
                window.location.href = 'checkout_paystack.php?cancel=1&order_id=<?php echo (int)$order_id; ?>';
            }
        });
        handler.openIframe();
    };

    if (typeof PaystackPop !== 'undefined') {
        pays();
    } else {
        // Retry a few times in case the CDN script loads after this block.
        var tries = 0;
        var timer = setInterval(function() {
            tries++;
            if (typeof PaystackPop !== 'undefined') {
                clearInterval(timer);
                pays();
            } else if (tries > 25) {
                clearInterval(timer);
                var btn = document.getElementById('paystack-retry');
                if (btn) btn.style.display = 'inline-block';
            }
        }, 200);
    }
</script>
<?php endif; ?>

<style>
@keyframes paystackSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<?php include 'includes/footer.php'; ?>