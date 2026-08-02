<?php
/**
 * Storefront: Shopping Cart (Avazonia)
 */
require_once 'includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Guest cart is supported — no login required to view/modify the cart.
// Users are prompted to log in/sign up right before placing an order.

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        if ($quantity > 0) {
            if (isset($_SESSION['user_id'])) {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$quantity, $_SESSION['user_id'], $product_id]);
            } else {
                $cart = asoGuestCart();
                $cart[$product_id] = $quantity;
                $_SESSION['cart'] = $cart;
            }
        }
    } elseif (isset($_POST['remove_item'])) {
        $product_id = (int)$_POST['product_id'];
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
        } else {
            $cart = asoGuestCart();
            unset($cart[$product_id]);
            $_SESSION['cart'] = $cart;
        }
    } elseif (isset($_POST['clear_all'])) {
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } else {
            unset($_SESSION['cart']);
        }
    }

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => true]);
        exit();
    }

    header('Location: cart.php');
    exit();
}

$cart_items = asoGetCartItems($pdo);
$total = 0;
$item_count = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
    $item_count += $item['quantity'];
}

// Normalize items to the Avazonia cart shape
$cart = [];
foreach ($cart_items as $item) {
    $images = !empty($item['all_images']) ? explode(',', $item['all_images']) : [];
    $img_file = !empty($images[0]) ? $images[0] : ($item['image'] ?? 'placeholder.jpg');
    $cart[] = [
        'key'           => $item['product_id'],
        'product_id'    => $item['product_id'],
        'qty'           => (int)$item['quantity'],
        'name'          => $item['name'],
        'image'         => getProductImage($img_file),
        'category_name' => $item['category_name'],
        'variant_name'  => 'Universal',
        'is_preorder'   => false,
        'currency'      => 'GHS',
        'price_ghs'     => (float)$item['price'],
    ];
}
$totalItems = $item_count;

$shipping_accra  = (float)($settings['shipping_accra'] ?? 15);
$shipping_kumasi = (float)($settings['shipping_kumasi'] ?? 25);
$shipping_others = (float)($settings['shipping_others'] ?? 60);
$shipping_pickup = (float)($settings['shipping_pickup'] ?? 0);
$free_threshold  = (float)($settings['free_shipping_threshold'] ?? 500);
$default_ship    = $total >= $free_threshold ? 0 : $shipping_accra;

$page_title = 'Shopping Cart';
include 'includes/header.php';
?>

<style>
.cart-page { padding-top: 68px; min-height: 100svh; background: var(--gray-50); }

/* PAGE HEADER */
.cart-header { background: var(--white); border-bottom: 1px solid var(--gray-200); padding: 32px 0 24px; }
.cart-header-inner { display: flex; justify-content: space-between; align-items: flex-end; }
.cart-h1 { font-family: var(--f-display); font-weight: 700; font-size: clamp(32px, 4vw, 48px); letter-spacing: -.03em; line-height: 1; color: var(--ink); }
.cart-h1 .cnt { color: var(--red); font-size: 0.8em; margin-left: 8px; }
.cart-header-sub { font-family: var(--f-mono); font-size: 10px; color: var(--gray-400); letter-spacing: .08em; text-align: right; }
.cart-header-sub strong { display: block; font-family: var(--f-display); font-size: 24px; color: var(--black); letter-spacing: -.02em; }

/* LAYOUT */
.cart-layout { display: grid; grid-template-columns: 1fr 380px; gap: 32px; padding: 40px 0 80px; align-items: start; }

/* CART ITEMS CARD */
.cart-card { background: var(--white); border: 1px solid var(--light-gray); border-radius: 24px; overflow: hidden; padding: 12px; }
.cart-card-head {
  display: grid; grid-template-columns: 120px 1fr 130px 100px 44px;
  gap: 16px; padding: 16px 24px;
  background: transparent; border-bottom: 1px solid var(--light-gray);
  font-family: var(--f-mono); font-size: 9px; font-weight: 700;
  letter-spacing: .12em; text-transform: uppercase; color: var(--mid-gray);
}
.cart-row {
  display: grid; grid-template-columns: 120px 1fr 130px 100px 44px;
  gap: 16px; align-items: center; padding: 24px;
  border-bottom: 1px solid var(--gray-50); transition: all .3s var(--ease);
  border-radius: 16px;
}
.cart-row:last-child { border-bottom: none; }
.cart-row:hover { background: var(--off); transform: scale(1.005); }
.ci-img {
  width: 120px; height: 120px;
  background: var(--white); border-radius: 16px; overflow: hidden;
  border: 1px solid var(--light-gray);
}
.ci-img img { width: 100%; height: 100%; object-fit: contain; padding: 12px; display: block; }
.ci-name { font-family: var(--f-display); font-weight: 600; font-size: 16px; letter-spacing: -.01em; line-height: 1.1; margin-bottom: 6px; color: var(--ink); }
.ci-meta { font-family: var(--f-mono); font-size: 9px; color: var(--gray-400); letter-spacing: .08em; }
.qty-inline { display: flex; align-items: center; border: 1px solid var(--light-gray); border-radius: 20px; width: fit-content; height: 32px; overflow: hidden; background: var(--off); }
.qib { width: 28px; height: 100%; background: none; border: none; font-size: 14px; cursor: pointer; color: var(--ink); display: flex; align-items: center; justify-content: center; transition: background .2s; }
.qib:hover { background: var(--paper); }
.qin { width: 28px; height: 100%; display: flex; align-items: center; justify-content: center; font-family: var(--f-mono); font-size: 11px; font-weight: 700; color: var(--ink); line-height: 1; }
.ci-price { font-family: var(--f-display); font-weight: 600; font-size: 16px; letter-spacing: -.01em; text-align: right; color: var(--ink); }
.ci-del { background: none; border: none; color: var(--mid-gray); font-size: 11px; cursor: pointer; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all .2s; }
.ci-del:hover { background: rgba(229,0,26,.08); color: var(--red); }
.cart-footer-row { padding: 18px 24px; background: var(--gray-50); display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--gray-200); }
.cart-footer-cnt { font-family: var(--f-mono); font-size: 10px; color: var(--gray-400); letter-spacing: .06em; }

/* SUMMARY CARD */
.summary-card { background: var(--white); border: 1px solid var(--light-gray); border-radius: 24px; padding: 40px; position: sticky; top: 120px; }
.sum-title { font-family: var(--f-display); font-weight: 700; font-size: 24px; letter-spacing: -.02em; margin-bottom: 32px; color: var(--ink); }
.zone-sel {
  width: 100%; background: var(--off); border: 1px solid var(--light-gray);
  padding: 14px 18px; font-family: var(--f-body); font-size: 13px; color: var(--ink);
  border-radius: 30px; outline: none; cursor: pointer; appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%235A5A5A' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 12px center;
  background-color: var(--gray-50);
  margin-bottom: 16px; transition: border-color .2s;
}
.zone-sel:focus { border-color: var(--red); }
.sum-line { display: flex; justify-content: space-between; padding: 12px 0; font-size: 14px; }
.sum-l { color: var(--mid-gray); font-family: var(--f-body); font-weight: 400; }
.sum-v { font-weight: 700; color: var(--ink); }
.sum-v.red { color: var(--red); }
.sum-total { display: flex; justify-content: space-between; align-items: center; padding-top: 24px; margin-top: 12px; border-top: 1px solid var(--light-gray); }
.sum-tl { font-family: var(--f-display); font-weight: 700; font-size: 20px; letter-spacing: -.01em; color: var(--ink); }
.sum-tv { font-family: var(--f-display); font-weight: 800; font-size: 36px; letter-spacing: -.03em; color: var(--ink); }
.checkout-btn {
  width: 100%; height: 60px; margin-top: 24px;
  background: var(--red); border: none; color: #fff;
  font-family: var(--f-mono); font-size: 11px; font-weight: 800;
  letter-spacing: .12em; text-transform: uppercase;
  cursor: pointer; border-radius: 30px;
  display: flex; align-items: center; justify-content: center; gap: 12px;
  transition: all .3s var(--ease); text-decoration: none;
  box-shadow: 0 8px 24px rgba(232,0,45,0.2);
}
.checkout-btn:hover { background: var(--red-deep); transform: translateY(-2px); box-shadow: 0 12px 32px rgba(232,0,45,0.3); }
.paystack-line { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 14px; font-family: var(--f-mono); font-size: 9px; color: var(--gray-400); letter-spacing: .06em; }
.pay-methods { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--gray-100); }
.fpay { border: 1px solid var(--gray-200); padding: 2px 6px; border-radius: 2px; font-size: 8px; }

@media (max-width: 1024px) {
  .cart-layout { grid-template-columns: 1fr; }
  .summary-card { position: static; }
  .cart-card-head { display: none; }
  .cart-row {
    grid-template-columns: 80px 1fr;
    grid-template-areas:
        "img info"
        "img actions";
    gap: 16px;
    padding: 20px;
    align-items: start;
  }
  .ci-img { grid-area: img; width: 80px; height: 80px; }
  .cart-row > div:nth-child(2) { grid-area: info; }
  .cart-row > div:nth-child(3) {
    grid-area: actions;
    display: flex !important;
    align-items: center;
    gap: 12px;
    margin-top: 4px;
  }
  .ci-price { display: none; }
  .cart-row > div:nth-child(5) {
    grid-area: actions;
    justify-self: end;
    display: block !important;
  }
}
@media (max-width: 640px) {
  .cart-header-inner { flex-direction: column; align-items: flex-start; gap: 12px; }
  .cart-header-sub { text-align: left; }
  .cart-layout { padding: 24px 0 60px; }
}
</style>

<div class="cart-page">

  <!-- HEADER -->
  <div class="cart-header">
    <div class="container cart-header-inner">
      <div>
        <div style="font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; letter-spacing: .1em; color: var(--gray-400); margin-bottom: 16px;">
            <a href="index.php" style="color: inherit; text-decoration: none;">Home</a> <span style="margin: 0 8px;">/</span> <span style="color: var(--black);">Cart</span>
        </div>
        <h1 class="cart-h1">Your Cart <span class="cnt">(<?= $totalItems ?>)</span></h1>
      </div>
    </div>
  </div>

  <div class="container">
    <?php if (empty($cart)): ?>
        <div style="padding: 240px 0; text-align: center; background: #fff;">
            <p style="font-family: var(--f-mono); font-size: 14px; color: #1a1a1a; text-transform: uppercase; letter-spacing: 0.45em; margin-bottom: 56px; font-weight: 500;">Your bag is currently empty.</p>
            <div style="display: flex; justify-content: center;">
                <a href="shop.php" class="btn-red" style="padding: 24px 64px; border-radius: 4px; text-transform: uppercase; font-family: var(--f-mono); font-size: 12px; letter-spacing: 0.15em; text-decoration: none; font-weight: 800; background: var(--red); color: #fff;">Shop the drops</a>
            </div>
        </div>
    <?php else: ?>
        <div class="cart-layout">
          <!-- ITEMS -->
          <div>
            <div class="cart-card">
              <div class="cart-card-head">
                <span></span><span>Product</span><span style="text-align:center">Quantity</span>
                <span style="text-align:right">Subtotal</span><span></span>
              </div>

              <?php foreach ($cart as $key => $item): ?>
                  <div class="cart-row">
                    <div class="ci-img"><img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"></div>
                    <div>
                        <div class="ci-name"><a href="product.php?id=<?= $item['product_id'] ?>" style="color: inherit; text-decoration: none;"><?= htmlspecialchars($item['name']) ?></a></div>
                        <div class="ci-meta">
                            <?= strtoupper(htmlspecialchars($item['category_name'] ?? 'Gadget')) ?> · <?= strtoupper(htmlspecialchars($item['variant_name'] ?? 'Universal')) ?>
                            <?php if($item['is_preorder'] ?? 0): ?>
                                <span style="color:var(--red); font-weight:700; display:block; margin-top:4px;">PRE-ORDER ITEM</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <div class="qty-inline">
                            <button type="button" class="qib cart-qty-minus" data-product-id="<?= $item['product_id'] ?>" <?= $item['qty'] <= 1 ? 'disabled' : '' ?>>−</button>
                            <span class="qin cart-qty-input"><?= $item['qty'] ?></span>
                            <button type="button" class="qib cart-qty-plus" data-product-id="<?= $item['product_id'] ?>">+</button>
                        </div>
                    </div>
                    <div class="ci-price" data-price="<?= $item['price_ghs'] ?>"><?= formatCurrency($item['price_ghs'] * $item['qty']) ?></div>
                    <button type="button" class="ci-del cart-remove" data-product-id="<?= $item['product_id'] ?>" title="Remove item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 15px; height: 15px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                  </div>
              <?php endforeach; ?>

              <div class="cart-footer-row">
                <span class="cart-footer-cnt"><?= $totalItems ?> ITEMS IN CART</span>
                <a href="shop.php" style="font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; letter-spacing: .1em; color: var(--gray-400); text-decoration: none;">← Continue Shopping</a>
              </div>
            </div>
          </div>

      <!-- SUMMARY -->
      <div class="summary-card">
        <div class="sum-title">Order Summary</div>

        <select class="zone-sel" id="cart-zone" onchange="updateCartTotal(this)">
          <option value="<?= $shipping_accra ?>" data-id="1">📍 Accra & Greater Accra — ₵<?= number_format($shipping_accra, 0) ?> (1–2 days)</option>
          <option value="<?= $shipping_kumasi ?>" data-id="2">📍 Kumasi / Takoradi — ₵<?= number_format($shipping_kumasi, 0) ?> (2–3 days)</option>
          <option value="<?= $shipping_others ?>" data-id="3">📍 All Other Regions — ₵<?= number_format($shipping_others, 0) ?> (3–5 days)</option>
          <option value="<?= $shipping_pickup ?>" data-id="4">🏪 Store Pickup — <?= $shipping_pickup > 0 ? '₵' . number_format($shipping_pickup, 0) : 'Free' ?></option>
        </select>

        <div class="sum-line"><span class="sum-l">Subtotal</span><span class="sum-v">₵<?= number_format($total, 2) ?></span></div>
        <div class="sum-line"><span class="sum-l">Delivery</span><span class="sum-v" id="cart-ship-val"><?= $default_ship > 0 ? '₵' . number_format($default_ship, 2) : 'FREE' ?></span></div>

        <div class="sum-total">
          <span class="sum-tl">Estimated Total</span>
          <span class="sum-tv" id="cart-est-total">₵<?= number_format($total + $default_ship, 2) ?></span>
        </div>

        <a href="checkout.php?zone_id=1" class="checkout-btn" id="cart-checkout-btn">
          Proceed to Checkout
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </a>
        <?php if (!isset($_SESSION['user_id'])): ?>
        <div style="margin-top:16px; padding:14px 18px; background:var(--off); border:1px dashed var(--light-gray); border-radius:12px; font-family:var(--f-mono); font-size:9px; line-height:1.6; color:var(--mid-gray); letter-spacing:.04em;">
            You're checking out as a guest. You'll be asked to <strong style="color:var(--ink);">log in or create an account</strong> right before you place your order — your items stay safe in your cart.
        </div>
        <?php endif; ?>
        <div class="paystack-line">🔒 SECURED BY PAYSTACK</div>
        <div class="pay-methods">
          <span class="fpay">MTN MOMO</span><span class="fpay">TELECEL</span>
          <span class="fpay">AT</span><span class="fpay">VISA</span><span class="fpay">MC</span>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
function updateCartTotal(el) {
    if (!el) return;
    const subtotal = <?= $total ?? 0 ?>;
    const threshold = <?= (int)$free_threshold ?>;
    let shipVal = parseFloat(el.value) || 0;

    if (subtotal >= threshold) {
        shipVal = 0;
    }

    const estTotal = subtotal + shipVal;

    document.getElementById('cart-ship-val').innerText = shipVal > 0 ? '₵' + shipVal.toFixed(2) : 'FREE';
    document.getElementById('cart-est-total').innerText = '₵' + estTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const zoneId = el.options[el.selectedIndex].getAttribute('data-id');
    const checkoutBtn = document.getElementById('cart-checkout-btn');
    if (checkoutBtn && zoneId) {
        checkoutBtn.href = 'checkout.php?zone_id=' + zoneId;
    }
}
window.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('cart-zone');
    if (sel) updateCartTotal(sel);

    function postForm(url, data) {
        const body = new URLSearchParams(data);
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(res => res.json());
    }

    document.querySelectorAll('.cart-qty-plus, .cart-qty-minus').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const input = btn.parentElement.querySelector('.cart-qty-input');
            const qty = Math.max(1, parseInt(input.innerText) + (btn.classList.contains('cart-qty-plus') ? 1 : -1));
            postForm('ajax/update_cart.php', { product_id: btn.dataset.productId, quantity: qty })
                .then(data => { if (data.success) { if (window.refreshCartCounter && data.cart_count) window.refreshCartCounter(data.cart_count); window.location.reload(); } });
        });
    });

    document.querySelectorAll('.cart-remove').forEach(function(btn) {
        btn.addEventListener('click', function() {
            postForm('ajax/remove_from_cart.php', { product_id: btn.dataset.productId })
                .then(data => { if (data.success) { if (window.refreshCartCounter && data.cart_count) window.refreshCartCounter(data.cart_count); window.location.reload(); } });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
