<?php
/**
 * Paystack Checkout (Inline)
 * - Requires login and a pending checkout in session
 * - Computes total server-side and initializes Paystack inline with reference
 */
require_once 'includes/db.php';
require_once 'includes/paystack_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'user/checkout.php';
    header('Location: login.php');
    exit();
}

$ref = $_GET['ref'] ?? '';
if (!$ref) {
    header('Location: user/checkout.php');
    exit();
}

// Ensure we have a pending checkout state
$pending = $_SESSION['pending_checkout'] ?? null;
if (!$pending || !in_array(($pending['payment_method'] ?? ''), ['paystack'], true)) {
    header('Location: user/checkout.php');
    exit();
}

// Fetch user (for email)
try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        header('Location: user/checkout.php');
        exit();
    }
} catch (PDOException $e) {
    error_log('Paystack user fetch error: ' . $e->getMessage());
    header('Location: user/checkout.php');
    exit();
}

// Compute total from cart server-side
$total = 0;
$cart_items = [];
try {
    $stmt = $pdo->prepare('SELECT c.*, p.name, p.price FROM cart c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll();
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
} catch (PDOException $e) {
    error_log('Paystack cart fetch error: ' . $e->getMessage());
}

if ($total <= 0) {
    header('Location: cart.php');
    exit();
}

// Amount in pesewas (GHS -> pesewas)
$amount_pesewas = (int) round($total * 100);
$customer_email = $user['email'] ?? '';
$public_key = PAYSTACK_PUBLIC_KEY;

if (!$public_key) {
    die('Paystack public key not configured. Set PAYSTACK_PUBLIC_KEY in environment or includes/paystack_config.php');
}

$page_title = 'Secure Payment - Paystack';
include 'includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <div class="card">
        <div class="card-header"><h4 class="mb-0">Pay with Paystack</h4></div>
        <div class="card-body">
          <p class="mb-3">Amount to pay: <strong class="text-primary"><?php echo formatCurrency($total); ?></strong></p>
          <p class="text-muted">You will complete your payment securely via Paystack.</p>
          <button id="paystackBtn" class="btn btn-success btn-lg w-100">
            <i class="fas fa-lock me-2"></i>Pay Now with Paystack
          </button>
          <a href="user/checkout.php" class="btn btn-outline-secondary w-100 mt-2">Back to Checkout</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
(function(){
  const payBtn = document.getElementById('paystackBtn');
  payBtn.addEventListener('click', function(){
    const handler = PaystackPop.setup({
      key: <?php echo json_encode($public_key); ?>,
      email: <?php echo json_encode($customer_email); ?>,
      amount: <?php echo json_encode($amount_pesewas); ?>, // amount in pesewas
      currency: 'GHS',
      ref: <?php echo json_encode($ref); ?>,
      callback: function(response){
        // redirect to server verification
        window.location.href = 'paystack_verify.php?reference=' + encodeURIComponent(response.reference);
      },
      onClose: function(){
        alert('Payment window closed. You can try again.');
      }
    });
    handler.openIframe();
  });
})();
</script>

<?php include 'includes/footer.php'; ?>
