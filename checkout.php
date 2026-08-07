<?php
/**
 * Checkout Page (Avazonia)
 */
require_once 'includes/db.php';
if (file_exists('vendor/autoload.php') && file_exists('vendor/composer/autoload_real.php')) {
    require_once 'vendor/autoload.php';
}
require_once 'includes/functions.php';
require_once 'includes/email_config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
$errors = [];
$settings = loadSiteSettings($pdo);

// Guests may fill the checkout form; login is required at the final order step.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_order') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid form submission. Please refresh and try again.']);
        exit();
    }

    // Persist the form so it survives the login/sign-up hop.
    $_SESSION['pending_checkout'] = array_intersect_key($_POST, array_flip([
        'full_name', 'email', 'phone', 'zone_id', 'address', 'city', 'payment_method', 'country'
    ]));

    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = 'checkout.php';
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'login_required' => true, 'redirect' => 'login.php']);
        exit();
    }

    $user_id = $_SESSION['user_id'];
}

// Shipping zones (domestic + international) from the shipping_zones table
$zone_rows = getShippingZones($pdo);
$zone_fees = [];
foreach ($zone_rows as $zr) {
    $zone_fees[(int)$zr['zone_id']] = $zr;
}
// First zone is the fallback (Accra, sort_order 1)
$default_zone_id = !empty($zone_fees) ? (int)array_keys($zone_fees)[0] : 1;
$free_threshold = (float)($settings['free_shipping_threshold'] ?? 500);

$cart_items = asoGetCartItems($pdo);

if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

$subtotal = 0;
$has_preorder = false;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    if (!empty($item['is_preorder']) || !empty($item['preorder_flag'])) {
        $has_preorder = true;
    }
}

// Resolve selected delivery zone
$zone_id = (int)($_GET['zone_id'] ?? $_POST['zone_id'] ?? $default_zone_id);
if (!isset($zone_fees[$zone_id])) {
    $zone_id = $default_zone_id;
}
$selected_zone = $zone_fees[$zone_id];
$default_ship = calculateShippingFee($subtotal, $selected_zone);
$total = $subtotal + $default_ship;
$pay_now = $total;

// Pre-fill form from a pending guest checkout (after login/sign-up hop).
$pending = $_SESSION['pending_checkout'] ?? [];
$pf_name  = $pending['full_name']  ?? ($_SESSION['user_name'] ?? '');
$pf_email = $pending['email']      ?? ($_SESSION['user_email'] ?? '');
$pf_phone = $pending['phone']      ?? '';
$pf_city  = $pending['city']       ?? '';
$pf_addr  = $pending['address']    ?? '';
$pf_pay   = $pending['payment_method'] ?? 'paystack';
$pf_country = $pending['country']  ?? '';
$pending_zone = (int)($pending['zone_id'] ?? 0);
if ($pending_zone > 0 && isset($zone_fees[$pending_zone])) {
    $zone_id = $pending_zone;
    $selected_zone = $zone_fees[$zone_id];
    $default_ship = calculateShippingFee($subtotal, $selected_zone);
    $total = $subtotal + $default_ship;
    $pay_now = $total;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_order') {
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $country = sanitizeInput($_POST['country'] ?? '');
    $payment_method = ($_POST['payment_method'] ?? 'paystack') === 'paystack' ? 'paystack' : 'in_person';

    $posted_zone = (int)($_POST['zone_id'] ?? $default_zone_id);
    if (!isset($zone_fees[$posted_zone])) {
        $posted_zone = $default_zone_id;
    }
    $selected_zone = $zone_fees[$posted_zone];
    $is_international = ($selected_zone['zone_type'] ?? 'domestic') === 'international';
    $shipping = calculateShippingFee($subtotal, $selected_zone);
    $total_with_ship = $subtotal + $shipping;

    if (empty($full_name) || empty($phone) || empty($city) || empty($address) || empty($email)) {
        $errors[] = 'All required fields must be filled';
    }
    if ($is_international && empty($country)) {
        $errors[] = 'Country is required for international delivery';
    }

    if (empty($errors)) {
        // Normalize Ghana phone number (Avazonia shows a +233 prefix box)
        if (!$is_international && !preg_match('/^\+/', $phone)) {
            $phone = '+233' . preg_replace('/\D/', '', $phone);
        }
        $shipping_address = $full_name . "\n" . $city . "\n" . $address;
        if ($is_international) {
            $shipping_address .= "\n" . ($country ?: '');
        }
        $shipping_label = ($selected_zone['flag_emoji'] ?? '') . ' ' . $selected_zone['zone_name'] . ($is_international && $country ? ' — ' . $country : '');
        try {
            $pdo->beginTransaction();
            $order_number = 'NX-' . str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, payment_method, shipping_address, billing_address, email, phone, shipping_zone_id, country, shipping_label, order_notes, order_status, payment_status, order_date, order_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW(), ?)");
            $stmt->execute([$user_id, $total_with_ship, $payment_method, $shipping_address, $shipping_address, $email, $phone, $posted_zone, $is_international ? $country : null, $shipping_label, '', $order_number]);
            $order_id = $pdo->lastInsertId();

            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            }

            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $pdo->commit();

            unset($_SESSION['pending_checkout']);

            // For Paystack, the confirmation email is sent only after the payment
            // is verified (see verify_payment.php), so cancelled/failed payments
            // never confirm. Other methods confirm immediately.
            if ($payment_method !== 'paystack') {
                // Send order confirmation email via shared mailer
                try {
                    $email_items = array_map(function($it) {
                        return ['name' => $it['name'], 'price' => $it['price'], 'quantity' => $it['quantity']];
                    }, $cart_items);
                    $order_details = [
                        'items' => $email_items,
                        'shipping_address' => $shipping_address,
                        'payment_method' => $payment_method,
                        'order_date' => date('Y-m-d H:i:s'),
                        'total' => $total_with_ship,
                    ];
                    sendOrderConfirmationEmail($email, $full_name, $order_number, $order_details);
                } catch (Exception $e) {
                    error_log("Order confirmation email failed: " . $e->getMessage());
                }
            }

            createAdminNotification('order', "New order {$order_number} from {$full_name}", 'manage_orders.php');

            header('Content-Type: application/json');
            $redirect = ($payment_method === 'paystack') ? 'checkout_paystack.php?order_id=' . $order_id : 'order_confirmation.php?order_id=' . $order_id;
            echo json_encode(['success' => true, 'redirect' => $redirect]);
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Processing failed: ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit();
    }
}

$page_title = 'Checkout';
include 'includes/header.php';
?>

<style>
body { background: #f8f8f8; color: #111; font-family: var(--f-body); }
.co-page { padding-top: calc(68px + var(--nav-offset, 0px)); min-height: 100svh; }

/* PROGRESS */
.pb-bar { background: #fff; border-bottom: 1px solid #eee; height: 48px; position: sticky; top: calc(68px + var(--nav-offset, 0px)); z-index: 90; }
.pb-inner { display: flex; align-items: stretch; height: 100%; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.pb-step { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; font-family: var(--f-mono); font-size: 10px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: #ccc; border-bottom: 2px solid transparent; }
.pb-step.on { color: #111; border-bottom-color: var(--red); }
.pb-step.done { color: #16a34a; }
.pb-n { width: 22px; height: 22px; border: 1.5px solid currentColor; display: flex; align-items: center; justify-content: center; border-radius: 0; font-size: 10px; }
.pb-step.on .pb-n { background: var(--red); border-color: var(--red); color: #fff; }

/* LAYOUT */
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.co-layout { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 32px; padding: 40px 0 80px; align-items: start; }

/* CARDS */
.co-card { background: #fff; border: 1px solid #eee; border-radius: 4px; margin-bottom: 24px; overflow: hidden; }
.co-card-head { padding: 18px 24px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 12px; }
.co-card-n { width: 24px; height: 24px; background: var(--red); display: flex; align-items: center; justify-content: center; color: #fff; font-family: var(--f-mono); font-size: 11px; font-weight: 700; border-radius: 2px; }
.co-card-t { font-family: var(--f-display); font-weight: 700; font-size: 18px; letter-spacing: -.01em; }
.co-card-body { padding: 32px; }

/* FORMS */
.co-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.co-row.full { grid-template-columns: 1fr; }
.fg { display: flex; flex-direction: column; gap: 8px; }
.fl { font-family: var(--f-mono); font-size: 8px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #999; }
.fi { width: 100%; height: 48px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 0 16px; font-family: var(--f-body); font-size: 14px; outline: none; transition: border .2s; }
.fi:focus { border-color: #111; background: #fff; }
.fs { width: 100%; height: 48px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 0 16px; font-family: var(--f-body); font-size: 14px; outline: none; appearance: none; cursor: pointer; }

/* PHONE COMPONENT */
.phone-box { display: flex; border: 1px solid #ddd; background: #f9f9f9; border-radius: 4px; overflow: hidden; }
.phone-pfx { padding: 0 16px; background: #f0f0f0; border-right: 1px solid #eee; display: flex; align-items: center; font-family: var(--f-mono); font-size: 10px; font-weight: 600; color: #999; white-space: nowrap; }
.phone-box .fi { border: none; background: none; flex: 1; border-radius: 0; }

/* PAYMENT GRID */
.pay-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px; }
.pay-item { padding: 24px 12px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; text-align: center; transition: all .2s; background: #fff; display: flex; flex-direction: column; align-items: center; gap: 8px; }
.pay-item:hover { border-color: #999; }
.pay-item.on { border: 1.5px solid var(--red); background: rgba(229,0,26,.02); }
.pay-icon { font-size: 20px; filter: grayscale(1); opacity: .5; }
.pay-item.on .pay-icon { filter: none; opacity: 1; }
.pay-lbl { font-family: var(--f-mono); font-size: 8px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #999; }
.pay-item.on .pay-lbl { color: var(--red); }
.pay-sub { font-size: 9px; color: #999; margin-top: 4px; }

/* INFO BOXES */
.paystack-note { background: #f9f9f9; border: 1px solid #eee; padding: 24px; border-radius: 4px; display: flex; gap: 16px; align-items: flex-start; }
.paystack-note .note-t { font-size: 13px; line-height: 1.5; color: #666; }

/* SIDEBAR SUMMARY */
.co-side { position: sticky; top: 140px; }
.co-sum { background: #fff; border: 1px solid #eee; border-radius: 4px; padding: 28px; }
.sum-t { font-family: var(--f-display); font-weight: 700; font-size: 20px; color: #111; margin-bottom: 12px; }
.sum-line-h { width: 100%; height: 2px; background: #111; margin-bottom: 24px; }
.itm-row { display: flex; gap: 16px; margin-bottom: 16px; align-items: flex-start; }
.itm-img { width: 44px; height: 44px; border: 1px solid #eee; border-radius: 4px; overflow: hidden; flex-shrink: 0; }
.itm-img img { width: 100%; height: 100%; object-fit: contain; padding: 4px; }
.itm-n { font-family: var(--f-display); font-weight: 700; font-size: 14px; line-height: 1; color: #111; }
.itm-m { font-family: var(--f-mono); font-size: 9px; color: #aaa; margin-top: 4px; text-transform: uppercase; }
.itm-p { font-family: var(--f-display); font-weight: 700; font-size: 15px; margin-left: auto; }

.co-line { display: flex; justify-content: space-between; padding: 6px 0; font-size: 12px; }
.co-lbl { color: #999; }
.co-val { font-weight: 700; color: #111; }

.co-total-row { border-top: 2px solid #111; margin-top: 12px; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; }
.co-total-l { font-family: var(--f-display); font-weight: 800; font-size: 16px; text-transform: uppercase; }
.co-total-v { font-family: var(--f-display); font-weight: 900; font-size: 38px; letter-spacing: -.03em; }

.co-due-box { margin-top: 24px; padding: 20px; background: var(--off); border-radius: 4px; border: 1px solid var(--light-gray); }
.co-due-lbl { font-family: var(--f-mono); font-size: 9px; color: var(--mid-gray); text-transform: uppercase; margin-bottom: 8px; }
.co-due-val { font-family: var(--f-display); font-size: 32px; font-weight: 900; color: var(--red); }

.co-pay-btn { width: 100%; height: 56px; background: var(--red); border: none; color: #fff; font-family: var(--f-display); font-size: 13px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 12px; margin-top: 24px; border-radius: 4px; }
.co-pay-btn:hover { background: #c70016; }
.co-pay-btn:disabled { opacity: .6; cursor: not-allowed; }
.co-sec-note { text-align: center; color: #ccc; font-family: var(--f-mono); font-size: 8px; letter-spacing: .1em; margin-top: 16px; text-transform: uppercase; }
.co-terms { text-align: center; color: #999; font-family: var(--f-mono); font-size: 8px; letter-spacing: .08em; margin-top: 12px; text-transform: uppercase; }
.co-terms a { color: #111; text-decoration: underline; }

.fg { position: relative; }
.err-icon { position: absolute; right: 12px; top: 40px; width: 20px; height: 20px; background: #d32f2f; border-radius: 50%; display: none; align-items: center; justify-content: center; color: #fff; font-size: 12px; font-weight: 800; pointer-events: none; }
.err-txt { color: #d32f2f; font-size: 11px; margin-top: 4px; display: none; font-family: var(--f-body); }
.fi.err { border-bottom: 2px solid #d32f2f !important; }

@media (max-width: 900px) {
    .co-layout { grid-template-columns: 1fr; }
    .co-side { position: static; }
}
@media (max-width: 640px) {
    .pb-step-name { display: none; }
    .co-row { grid-template-columns: 1fr; }
}
</style>

<div class="co-page" id="coView">
    <?php echo csrfField(); ?>
    <div class="pb-bar">
        <div class="pb-inner">
            <a href="cart.php" class="pb-step done" style="text-decoration:none;"><div class="pb-n">✓</div> <span class="pb-step-name">Cart</span></a>
            <div class="pb-step on"><div class="pb-n">2</div> <span class="pb-step-name">Checkout</span></div>
            <div class="pb-step"><div class="pb-n">3</div> <span class="pb-step-name">Confirmation</span></div>
        </div>
    </div>

    <div class="container">
        <!-- STACKED FORM ERROR BANNER -->
        <div id="co-error-banner" style="display:none; background:#fbe9e7; border-top:4px solid #d32f2f; padding:20px 24px; margin-bottom:32px; font-family:var(--f-body); font-size:15px; align-items:center; gap:16px;">
            <div style="width:24px; height:24px; background:#d32f2f; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:14px; font-weight:900; flex-shrink:0;">!</div>
            <div style="color:#d32f2f; font-weight:600;">You must complete all required fields</div>
        </div>

        <div class="co-layout">
            <!-- MAIN CONTENT -->
            <div>
                <!-- STEP 1: CONTACT -->
                <div class="co-card">
                    <div class="co-card-head">
                        <div class="co-card-n">1</div>
                        <div class="co-card-t">Contact Info</div>
                    </div>
                    <div class="co-card-body">
                        <div class="co-row">
                            <div class="fg">
                                <label class="fl">Full Name</label>
                                <div style="position:relative;">
                                    <input class="fi" type="text" id="co-name" placeholder="Kwame Mensah" value="<?php echo htmlspecialchars($pf_name); ?>">
                                    <div class="err-icon">!</div>
                                </div>
                                <div class="err-txt">This is a required field</div>
                            </div>
                            <div class="fg">
                                <label class="fl">Email Address</label>
                                <div style="position:relative;">
                                    <input class="fi" type="email" id="co-email" placeholder="kwame@example.com" value="<?php echo htmlspecialchars($pf_email); ?>">
                                    <div class="err-icon">!</div>
                                </div>
                                <div class="err-txt">This is a required field</div>
                            </div>
                        </div>
                        <div class="co-row full">
                            <div class="fg">
                                <label class="fl">Phone Number</label>
                                <div style="position:relative;">
                                    <div class="phone-box">
                                        <div class="phone-pfx">GH +233</div>
                                        <input class="fi" type="tel" id="co-phone" placeholder="24 000 0000" value="<?php echo htmlspecialchars($pf_phone); ?>">
                                    </div>
                                    <div class="err-icon">!</div>
                                </div>
                                <div class="err-txt">This is a required field</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: DELIVERY -->
                <div class="co-card">
                    <div class="co-card-head">
                        <div class="co-card-n">2</div>
                        <div class="co-card-t">Delivery Details</div>
                    </div>
                    <div class="co-card-body">
                        <div class="co-row full">
                            <div class="fg">
                                <label class="fl">Delivery Zone</label>
                                <div style="position:relative;">
                                    <select class="fs" id="co-zone" onchange="selectZone(this)">
                                        <?php foreach ($zone_rows as $zr): ?>
                                            <?php $zrid = (int)$zr['zone_id']; $zrate = (float)$zr['flat_rate'];
                                                $zn = htmlspecialchars($zr['zone_name']); $zflag = htmlspecialchars($zr['flag_emoji'] ?? '');
                                                $zfree = !empty($zr['free_threshold']) ? (float)$zr['free_threshold'] : null;
                                                $free = ($zfree !== null && $subtotal >= $zfree) ? 0 : $zrate;
                                            ?>
                                            <option value="<?php echo $zrid; ?>" data-type="<?php echo $zr['zone_type']; ?>" <?php echo $zone_id == $zrid ? 'selected' : ''; ?>>
                                                <?php echo $zflag; ?> <?php echo $free > 0 ? $zn . ' — ₵' . number_format($free, 0) : $zn . ' — Free'; ?> <?php echo !empty($zr['estimated_days']) ? '(' . htmlspecialchars($zr['estimated_days']) . ')' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="err-icon" style="top:50%; transform:translateY(-50%); right:32px;">!</div>
                                </div>
                                <div class="err-txt">This is a required field</div>
                            </div>
                        </div>
                        <div class="co-row full" id="co-country-row" <?php echo ($selected_zone['zone_type'] ?? 'domestic') === 'international' ? '' : 'style="display:none;"'; ?>>
                            <div class="fg">
                                <label class="fl">Country (for international delivery)</label>
                                <div style="position:relative;">
                                    <input class="fi" type="text" id="co-country" placeholder="e.g. United Kingdom" value="<?php echo htmlspecialchars($pf_country); ?>">
                                    <div class="err-icon">!</div>
                                </div>
                                <div class="err-txt">Country is required for international delivery</div>
                            </div>
                        </div>
                        <div class="co-row full">
                            <div class="fg">
                                <label class="fl">Street Address / Digital Address</label>
                                <div style="position:relative;">
                                    <input class="fi" type="text" id="co-address" placeholder="No. 24 Liberation Road / GA-182-1234" value="<?php echo htmlspecialchars($pf_addr); ?>">
                                    <div class="err-icon">!</div>
                                </div>
                                <div class="err-txt">This is a required field</div>
                            </div>
                        </div>
                        <div class="co-row">
                            <div class="fg">
                                <label class="fl">City</label>
                                <div style="position:relative;">
                                    <input class="fi" type="text" id="co-city" placeholder="Accra" value="<?php echo htmlspecialchars($pf_city); ?>">
                                    <div class="err-icon">!</div>
                                </div>
                                <div class="err-txt">This is a required field</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: PAYMENT -->
                <div class="co-card">
                    <div class="co-card-head">
                        <div class="co-card-n">3</div>
                        <div class="co-card-t">Payment Method</div>
                    </div>
                    <div class="co-card-body">
                        <!-- Payment Selection Grid -->
                        <div class="pay-grid">
                            <div class="pay-item <?php echo $pf_pay === 'paystack' ? 'on' : ''; ?>" onclick="selectPayMethod('paystack')" id="pay-paystack">
                                <span style="font-size: 24px; margin-bottom: 8px; display: block;">💳</span>
                                <div class="pay-lbl" style="font-size: 11px; font-weight: 800;">Pay online</div>
                                <div class="pay-sub">Paystack or MoMo</div>
                            </div>

                            <div class="pay-item <?php echo $pf_pay === 'in_person' ? 'on' : ''; ?>" onclick="selectPayMethod('in_person')" id="pay-inperson">
                                <span style="font-size: 24px; margin-bottom: 8px; display: block;">🏧</span>
                                <div class="pay-lbl" style="font-size: 11px; font-weight: 800;">In Person</div>
                                <div class="pay-sub">Bank transfer or cash on delivery</div>
                            </div>
                        </div>

                        <input type="hidden" id="co-payment-method" value="<?php echo htmlspecialchars($pf_pay); ?>">

                        <div id="paystack-info" class="paystack-note" <?php echo $pf_pay === 'in_person' ? 'style="display:none"' : ''; ?>>
                            <span style="font-size:24px;">🛡️</span>
                            <div class="note-t">
                                <strong style="color: #111; display: block; margin-bottom: 4px;">Secure Online Checkout</strong>
                                Pay with card, bank, or MoMo via Paystack.
                            </div>
                        </div>

                        <div id="bank-info" <?php echo $pf_pay === 'in_person' ? '' : 'style="display:none"'; ?> style="background: #fff8e1; border: 1px solid #ffe082; padding: 24px; border-radius: 4px; gap: 16px; align-items: flex-start;">
                            <span style="font-size:24px;">📦</span>
                            <div style="font-size: 13px; line-height: 1.5; color: #856404;">
                                <strong style="color: #111; display: block; margin-bottom: 4px;">Pay by Bank Transfer</strong>
                                You will receive our account details on the confirmation page. Transfer the total to reserve your order.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR SUMMARY -->
            <div class="co-side">
                <div class="co-sum">
                    <div class="sum-t">Your Order</div>
                    <div class="sum-line-h"></div>

                    <div class="co-items" style="margin-bottom:24px; border-bottom:1px solid #f0f0f0; padding-bottom:24px;">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="itm-row">
                                <div class="itm-img"><img src="<?php echo htmlspecialchars(getProductImage($item['image'] ?? 'placeholder.jpg')); ?>" alt=""></div>
                                <div style="flex:1;">
                                    <div class="itm-n"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="itm-m">QTY: <?php echo (int)$item['quantity']; ?> · <?php echo strtoupper(htmlspecialchars($item['category_name'] ?? 'Gadget')); ?></div>
                                </div>
                                <div class="itm-p">₵<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="co-line"><span class="co-lbl">Subtotal</span><span class="co-val">₵<?php echo number_format($subtotal, 2); ?></span></div>
                    <div class="co-line"><span class="co-lbl">Delivery</span><span class="co-val" id="disp-ship"><?php echo $default_ship > 0 ? '₵' . number_format($default_ship, 2) : 'FREE'; ?></span></div>

                    <div class="co-total-row">
                        <div class="co-total-l">Order Total</div>
                        <div class="co-total-v" id="disp-main-total">₵<?php echo number_format($total, 2); ?></div>
                    </div>

                    <div class="co-due-box">
                        <div class="co-due-lbl">Due Now</div>
                        <div class="co-due-val" id="disp-total">₵<?php echo number_format($pay_now, 2); ?></div>
                    </div>

                    <button class="co-pay-btn" id="co-pay-btn" onclick="placeOrder(event)">
                        🛍️ PAY ₵<?php echo number_format($pay_now, 2); ?> NOW →
                    </button>

                    <div class="co-sec-note">🔒 SECURED BY PAYSTACK · PCI-DSS</div>

                    <div class="co-terms">By placing your order you agree to our <a href="<?php echo $base; ?>legal/terms-conditions.php">Terms &amp; Conditions</a>.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const CO = {
    subtotal: <?php echo $subtotal; ?>,
    zones: <?php echo json_encode(array_values(array_map(function($z) { return ['id'=>(int)$z['zone_id'], 'rate'=>(float)$z['flat_rate'], 'free'=>!empty($z['free_threshold']) ? (float)$z['free_threshold'] : null, 'type'=>$z['zone_type']]; }, $zone_rows))); ?>
};

function currentZone() {
    const sel = document.getElementById('co-zone');
    if (!sel) return null;
    const id = parseInt(sel.value, 10);
    return CO.zones.find(z => z.id === id) || null;
}

function selectPayMethod(m) {
    document.getElementById('co-payment-method').value = m;

    document.getElementById('pay-paystack').classList.toggle('on', m === 'paystack');
    document.getElementById('pay-inperson').classList.toggle('on', m === 'in_person');

    document.getElementById('paystack-info').style.display = (m === 'paystack' ? 'flex' : 'none');
    document.getElementById('bank-info').style.display = (m === 'in_person' ? 'flex' : 'none');

    updateShip(document.getElementById('co-zone'));
}

function currentShip() {
    const z = currentZone();
    if (!z) return 0;
    let shipVal = z.rate;
    if (z.free !== null && z.free !== undefined && CO.subtotal >= z.free) shipVal = 0;
    return shipVal;
}

function selectZone(el) {
    const z = currentZone();
    const countryRow = document.getElementById('co-country-row');
    if (countryRow) {
        countryRow.style.display = (z && z.type === 'international') ? 'flex' : 'none';
        if (!(z && z.type === 'international')) {
            const c = document.getElementById('co-country');
            if (c) c.value = '';
        }
    }
    updateShip(el);
}

function updateShip(el) {
    if (!el) return;
    const shipVal = currentShip();
    const total = CO.subtotal + shipVal;

    document.getElementById('disp-ship').innerText = shipVal > 0 ? '₵' + shipVal.toFixed(2) : 'FREE';
    document.getElementById('disp-total').innerText = '₵' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (document.getElementById('disp-main-total')) {
        document.getElementById('disp-main-total').innerText = '₵' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    const m = document.getElementById('co-payment-method').value;
    const btn = document.getElementById('co-pay-btn');
    if (btn) {
        if (m === 'in_person') {
            btn.innerHTML = '📦 PLACE ORDER (BANK TRANSFER) →';
        } else {
            btn.innerHTML = '🛍️ PAY ₵' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' NOW →';
        }
    }
}

function validateForm() {
    const fields = ['co-name', 'co-email', 'co-phone', 'co-address', 'co-city'];
    let anyMissing = false;

    fields.forEach(fid => {
        const el = document.getElementById(fid);
        const fg = el.closest('.fg');
        const icon = fg.querySelector('.err-icon');
        const txt = fg.querySelector('.err-txt');

        if (!el || !el.value.trim()) {
            el.classList.add('err');
            if (icon) icon.style.display = 'flex';
            if (txt) txt.style.display = 'block';
            anyMissing = true;
        } else {
            el.classList.remove('err');
            if (icon) icon.style.display = 'none';
            if (txt) txt.style.display = 'none';
        }
    });

    // International country is required
    const z = currentZone();
    const countryEl = document.getElementById('co-country');
    if (z && z.type === 'international' && countryEl) {
        if (!countryEl.value.trim()) {
            countryEl.classList.add('err');
            const fg = countryEl.closest('.fg');
            const icon = fg.querySelector('.err-icon');
            const txt = fg.querySelector('.err-txt');
            if (icon) icon.style.display = 'flex';
            if (txt) txt.style.display = 'block';
            anyMissing = true;
        } else {
            countryEl.classList.remove('err');
            const fg = countryEl.closest('.fg');
            const icon = fg.querySelector('.err-icon');
            const txt = fg.querySelector('.err-txt');
            if (icon) icon.style.display = 'none';
            if (txt) txt.style.display = 'none';
        }
    }

    const banner = document.getElementById('co-error-banner');
    if (anyMissing) {
        banner.style.display = 'flex';
        banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }

    banner.style.display = 'none';
    return true;
}

function placeOrder(evt) {
    if (!validateForm()) return;

    const btn = document.getElementById('co-pay-btn');
    const oldText = btn.innerHTML;
    btn.innerHTML = 'Processing Order...';
    btn.disabled = true;

    const sel = document.getElementById('co-zone');
    const zoneId = sel.options[sel.selectedIndex].value;

    const payload = new URLSearchParams({
        action: 'process_order',
        csrf_token: document.querySelector('input[name="csrf_token"]')?.value || '',
        full_name: document.getElementById('co-name').value,
        email: document.getElementById('co-email').value,
        phone: document.getElementById('co-phone').value,
        zone_id: zoneId,
        address: document.getElementById('co-address').value,
        city: document.getElementById('co-city').value,
        country: document.getElementById('co-country')?.value || '',
        payment_method: document.getElementById('co-payment-method').value
    });

    fetch('checkout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: payload.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect;
        } else if (data.login_required) {
            window.location.href = window.SHOP_URL + 'login.php';
        } else {
            if (typeof showToast === 'function') showToast(data.message, 'danger', 3000);
            else alert(data.message);
            btn.innerHTML = oldText;
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        alert('Connectivity error. Please try again.');
        btn.innerHTML = oldText;
        btn.disabled = false;
    });
}

window.addEventListener('DOMContentLoaded', () => {
    selectZone(document.getElementById('co-zone'));
});
</script>

<?php include 'includes/footer.php'; ?>
