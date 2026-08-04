<?php
/**
 * Email Configuration
 * - Settings for sending emails (invoices, notifications, etc.)
 * 
 * Uses PHPMailer with SMTP for reliable email delivery.
 * Templates follow the Avazonia email design system (shared email_layout shell).
 */

// Load Composer's autoloader safely
if (file_exists(__DIR__ . '/../vendor/autoload.php') && file_exists(__DIR__ . '/../vendor/composer/autoload_real.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Dotenv\Dotenv')) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
            $dotenv->safeLoad();
        } catch (Throwable $e) {
            error_log("Dotenv load error in email_config: " . $e->getMessage());
        }
    }
}

// Store information
define('STORE_EMAIL', $_ENV['STORE_EMAIL'] ?? 'minatoflash82@gmail.com');
define('STORE_NAME', $_ENV['STORE_NAME'] ?? 'ASO Online Market');
if (!defined('SITE_URL')) {
    define('SITE_URL', rtrim($_ENV['SITE_URL'] ?? 'http://localhost/my-shop-2-main/', '/') . '/');
}
if (!defined('PRIMARY_COLOR')) {
    // Use the site's configured brand color (from site_settings) so emails match the storefront.
    $brand_color = '#E8002D';
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'primary_color' LIMIT 1");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['setting_value'])) {
                $brand_color = $row['setting_value'];
            }
        } catch (PDOException $e) {}
    }
    define('PRIMARY_COLOR', $brand_color);
}

// Development mode - Set to false in production to send real emails
define('EMAIL_DEVELOPMENT_MODE', filter_var($_ENV['EMAIL_DEVELOPMENT_MODE'] ?? 'false', FILTER_VALIDATE_BOOLEAN));

// SMTP Configuration
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
define('SMTP_PORT', (int) ($_ENV['SMTP_PORT'] ?? 587));
define('SMTP_SECURE', $_ENV['SMTP_SECURE'] ?? 'tls');  // 'tls' or 'ssl'

// Create logs directory if it doesn't exist
if (!file_exists(dirname(__FILE__) . '/../logs')) {
    mkdir(dirname(__FILE__) . '/../logs', 0755, true);
}

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Require Composer's autoloader safely
if (file_exists(__DIR__ . '/../vendor/autoload.php') && file_exists(__DIR__ . '/../vendor/composer/autoload_real.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

/**
 * Send an email with HTML support using PHPMailer with SMTP
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message HTML email content
 * @param string $headers Not used, kept for backward compatibility
 * @return bool True if email was sent successfully, false otherwise
 */
function sendEmail($to, $subject, $message, $headers = '') {
    // Log email attempt
    $log_message = "To: $to\n";
    $log_message .= "Subject: $subject\n";
    
    if (EMAIL_DEVELOPMENT_MODE) {
        // In development, log the email instead of sending it
        $log_message = "DEVELOPMENT MODE - Email not sent:\n" . $log_message;
        $log_message .= "--- Message ---\n$message\n";
        
        $log_file = dirname(__FILE__) . '/../logs/email_' . date('Y-m-d') . '.log';
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "]\n" . $log_message . "\n\n", FILE_APPEND);
        return true;
    }

    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 20;
        
        // Enable verbose debug output in development
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        }

        // Recipients
        $mail->setFrom(STORE_EMAIL, STORE_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(STORE_EMAIL, STORE_NAME);

        // Attach logo if it exists for CID inline embedding
        $logo_path = dirname(__DIR__) . '/assets/images/logo-v3.png';
        if (!file_exists($logo_path)) {
            $logo_path = dirname(__DIR__) . '/assets/images/logo.png';
        }
        if (file_exists($logo_path)) {
            $mail->addEmbeddedImage($logo_path, 'store_logo', 'logo.png');
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);

        // Send the email
        $result = $mail->send();
        
        // Log successful email
        $log_message = "Email sent successfully using SMTP\n" . $log_message;
        
    } catch (Exception $e) {
        // Log the error
        $log_message = "Failed to send email: " . $mail->ErrorInfo . "\n" . $log_message;
        $result = false;
    }
    
    // Write to log file
    $log_file = dirname(__FILE__) . '/../logs/email_' . date('Y-m-d') . '.log';
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] " . $log_message . "\n\n", FILE_APPEND);
    
    return $result;
}

/**
 * Get email log for debugging
 * 
 * @param int $lines Number of log entries to retrieve (most recent first)
 * @return string Formatted log entries
 */
function getEmailLog($lines = 10) {
    $log_file = dirname(__FILE__) . '/../logs/email_' . date('Y-m-d') . '.log';
    
    if (!file_exists($log_file)) {
        return "No email log found for today.";
    }
    
    $log_content = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $log_entries = array_chunk($log_content, 2); // Group log entries (timestamp + content)
    $log_entries = array_slice($log_entries, -$lines);
    
    $formatted_log = "";
    foreach ($log_entries as $entry) {
        $formatted_log .= implode("\n", $entry) . "\n\n";
    }
    
    return $formatted_log;
}

/**
 * Shared Avazonia email shell.
 * Wrap every template body between the hero/body blocks with this layout.
 *
 * @param string $content  The email hero + body HTML
 * @param string $preheader Hidden preview text
 * @param string $toEmail   Recipient address (shown in footer)
 * @return string Full HTML email
 */
function email_layout($content, $preheader = '', $toEmail = '') {
    $appName   = STORE_NAME;
    $appUrl    = SITE_URL;
    $siteEmail = STORE_EMAIL;
    $year      = date('Y');
    $primaryColor = PRIMARY_COLOR;
    $eAppName  = htmlspecialchars($appName);
    $eSiteEmail = htmlspecialchars($siteEmail);
    $eToEmail  = htmlspecialchars($toEmail);

    ob_start(); ?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?= $eAppName ?></title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap');
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #F4F4F5; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #111; -webkit-font-smoothing: antialiased; }
    .email-wrap { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,.05); border: 1px solid rgba(0,0,0,0.05); }
    .email-header { background: #0A0A0A; padding: 32px 40px; text-align: center; }
    .email-header a { color: #fff; text-decoration: none; font-size: 24px; font-weight: 900; letter-spacing: -0.04em; text-transform: uppercase; }
    .email-header a span { color: <?= $primaryColor ?>; }
    .email-hero { background: <?= $primaryColor ?>; padding: 48px 40px; color: #fff; text-align: center; }
    .email-hero h1 { font-size: 32px; font-weight: 900; letter-spacing: -0.03em; margin-bottom: 12px; line-height: 1.2; }
    .email-hero p { font-size: 16px; opacity: 0.95; line-height: 1.6; font-weight: 500; }
    .email-body { padding: 48px 40px; }
    .email-body p { font-size: 16px; line-height: 1.6; color: #444; margin-bottom: 20px; }
    .email-body h2 { font-size: 20px; font-weight: 700; color: #111; margin: 32px 0 16px; letter-spacing: -0.02em; }
    .btn-primary { display: inline-block; background: <?= $primaryColor ?>; color: #fff !important; text-decoration: none; padding: 16px 36px; border-radius: 50px; font-weight: 700; font-size: 15px; letter-spacing: 0.03em; margin: 24px 0; text-align: center; }
    .btn-secondary { display: inline-block; background: #0A0A0A; color: #fff !important; text-decoration: none; padding: 16px 36px; border-radius: 50px; font-weight: 700; font-size: 15px; letter-spacing: 0.03em; margin: 8px 0; text-align: center; }
    .order-table { width: 100%; border-collapse: collapse; margin: 24px 0; }
    .order-table th { background: #FAFAFA; padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #888; border-bottom: 1px solid #EAEAEA; }
    .order-table td { padding: 16px; border-bottom: 1px solid #F0F0F0; font-size: 15px; color: #333; }
    .order-table tr:last-child td { border-bottom: none; }
    .status-badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; }
    .status-paid { background: #DCFCE7; color: #16A34A; }
    .status-shipped { background: #DBEAFE; color: #1D4ED8; }
    .status-cancelled { background: #FEE2E2; color: #DC2626; }
    .status-refunded { background: #FEF3C7; color: #D97706; }
    .divider { border: none; border-top: 1px solid #EAEAEA; margin: 32px 0; }
    .info-block { background: #FAFAFA; border-left: 4px solid <?= $primaryColor ?>; padding: 20px 24px; border-radius: 0 12px 12px 0; margin: 24px 0; font-size: 15px; color: #444; line-height: 1.6; }
    .notice { background: #FFFBEB; border: 1px solid #FDE68A; padding: 20px 24px; border-radius: 12px; margin: 24px 0; font-size: 14px; color: #92400E; line-height: 1.6; }
    .email-footer { background: #FAFAFA; border-top: 1px solid #EAEAEA; padding: 32px 40px; text-align: center; }
    .email-footer p { font-size: 13px; color: #888; line-height: 1.6; }
    .email-footer a { color: <?= $primaryColor ?>; text-decoration: none; font-weight: 500; }
    @media (max-width: 600px) {
      .email-wrap { margin: 16px; border-radius: 12px; }
      .email-hero, .email-body, .email-footer, .email-header { padding: 32px 24px; }
      .email-hero h1 { font-size: 26px; }
      .btn-primary, .btn-secondary { display: block; width: 100%; box-sizing: border-box; }
    }
  </style>
</head>
<body>
  <?php if ($preheader): ?>
  <span style="display:none;max-height:0;overflow:hidden;"><?= htmlspecialchars($preheader) ?></span>
  <?php endif; ?>
  <div class="email-wrap">
    <!-- Header -->
    <div class="email-header">
      <a href="<?= $appUrl ?>"><?= $eAppName ?><span>.</span></a>
    </div>

    <?= $content ?>

    <!-- Footer -->
    <div class="email-footer">
      <p>
        © <?= $year ?> <?= $eAppName ?> — Crafted in Takoradi, Ghana<br>
        <a href="<?= $appUrl ?>">Shop</a> &nbsp;·&nbsp;
        <a href="<?= $appUrl ?>user/orders.php">My Account</a> &nbsp;·&nbsp;
        <a href="mailto:<?= $eSiteEmail ?>">Support</a>
      </p>
      <p style="margin-top:12px;">This email was sent to <strong><?= $eToEmail ?></strong>.
      If you didn't request this, you can safely ignore it.</p>
    </div>
  </div>
</body>
</html>
<?php
    return ob_get_clean();
}

/**
 * Send an account email verification link (24h expiry)
 *
 * @param string $user_email Recipient email
 * @param string $user_name Recipient name
 * @param string $token Verification token
 * @return bool True if email was sent successfully
 */
function sendVerificationEmail($user_email, $user_name, $token) {
    $verifyLink = SITE_URL . 'verify_email.php?token=' . urlencode($token);
    $subject = "Verify Your Email - " . STORE_NAME;

    ob_start(); ?>
<div class="email-hero">
  <h1>Welcome to <?= htmlspecialchars(STORE_NAME) ?> 👋</h1>
  <p>You're almost in. Just verify your email to activate your account.</p>
</div>
<div class="email-body">
  <p>Hi <strong><?= htmlspecialchars($user_name) ?></strong>,</p>
  <p>Thanks for creating an account! We're excited to have you join our marketplace.</p>
  <p>To get started, please verify your email address by clicking the button below:</p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="<?= htmlspecialchars($verifyLink) ?>" class="btn-primary">✉ Verify My Email</a>
  </div>

  <div class="notice">
    ⏱ This link expires in <strong>24 hours</strong>. If you didn't create an account, please ignore this email.
  </div>

  <hr class="divider">

  <p style="font-size: 13px; color: #999;">Or paste this URL in your browser:<br>
    <a href="<?= htmlspecialchars($verifyLink) ?>" style="color: <?= PRIMARY_COLOR ?>; word-break: break-all;"><?= htmlspecialchars($verifyLink) ?></a>
  </p>
</div>
<?php
    $content = ob_get_clean();
    return sendEmail($user_email, $subject, email_layout($content, 'Verify your email to activate your account', $user_email));
}

/**
 * Build the shared order-table rows + totals block used by confirmation & invoice emails.
 *
 * @param array  $items    Order items
 * @param float  $subtotal Computed subtotal
 * @param float  $shipping Shipping fee
 * @param float  $total    Grand total
 * @return string HTML for order table
 */
function email_order_table($items, $subtotal, $shipping, $total) {
    $rows = '';
    foreach ($items as $item) {
        $name  = $item['name'] ?? ($item['product_name'] ?? 'Product');
        $price = (float)($item['price'] ?? ($item['product_price'] ?? 0));
        $qty   = (int)($item['quantity'] ?? ($item['qty'] ?? 1));
        $variant = $item['variant_label'] ?? '';
        $variant_html = $variant ? '<br><span style="font-size:12px;color:#888;">' . htmlspecialchars($variant) . '</span>' : '';
        $rows .= "
        <tr>
          <td><strong>" . htmlspecialchars($name) . "</strong>{$variant_html}</td>
          <td style=\"text-align:right;\">" . $qty . "</td>
          <td style=\"text-align:right;\">" . formatCurrency($price * $qty) . "</td>
        </tr>";
    }
    if ($rows === '') {
        $rows = '<tr><td colspan="3" style="text-align:center; color:#888;">No items found in this order.</td></tr>';
    }
    return "
    <table class=\"order-table\">
      <thead>
        <tr>
          <th>Item</th>
          <th style=\"text-align:right;\">Qty</th>
          <th style=\"text-align:right;\">Price</th>
        </tr>
      </thead>
      <tbody>{$rows}</tbody>
      <tfoot>
        <tr>
          <td colspan=\"2\" style=\"text-align:right; font-weight:600; font-size:13px; color:#888; padding-top:16px;\">Subtotal</td>
          <td style=\"text-align:right; padding-top:16px;\">" . formatCurrency($subtotal) . "</td>
        </tr>
        <tr>
          <td colspan=\"2\" style=\"text-align:right; font-weight:600; font-size:13px; color:#888;\">Shipping</td>
          <td style=\"text-align:right;\">" . ($shipping > 0 ? formatCurrency($shipping) : 'FREE') . "</td>
        </tr>
        <tr>
          <td colspan=\"2\" style=\"text-align:right; font-weight:700; font-size:15px;\">Total</td>
          <td style=\"text-align:right; font-weight:700; font-size:15px; color: " . PRIMARY_COLOR . ";\">" . formatCurrency($total) . "</td>
        </tr>
      </tfoot>
    </table>";
}

/**
 * Send an order confirmation email to the customer
 * (Avazonia "Order Placed" template)
 *
 * @param string $customer_email Customer's email address
 * @param string $customer_name Customer's full name
 * @param int $order_id Order number (e.g. NX-000123)
 * @param array $order_details Order details (items, shipping_address, payment_method, total, order_date)
 * @return bool True if email was sent successfully, false otherwise
 */
function sendOrderConfirmationEmail($customer_email, $customer_name, $order_id, $order_details) {
    $subject = "Order Confirmation #$order_id - " . STORE_NAME;

    $subtotal = 0;
    foreach (($order_details['items'] ?? []) as $item) {
        $subtotal += (float)($item['price'] ?? ($item['product_price'] ?? 0)) * (int)($item['quantity'] ?? ($item['qty'] ?? 1));
    }

    $total = (float)($order_details['total'] ?? $order_details['total_amount'] ?? $subtotal);
    $shipping = (float)($order_details['shipping'] ?? 0);
    if ($shipping <= 0 && $total > $subtotal) {
        $shipping = $total - $subtotal;
    }
    if ($shipping < 0) $shipping = 0;

    $order_date = $order_details['order_date'] ?? date('Y-m-d H:i:s');
    $shipping_address = $order_details['shipping_address'] ?? '';
    $payment_method = ucwords(str_replace('_', ' ', $order_details['payment_method'] ?? 'Not specified'));
    $order_number = $order_id;

    ob_start(); ?>
<div class="email-hero">
  <h1>Order Confirmed! 🎉</h1>
  <p>Your order <strong>#<?= htmlspecialchars($order_number) ?></strong> has been received and is being processed.</p>
</div>
<div class="email-body">
  <p>Hi <strong><?= htmlspecialchars($customer_name) ?></strong>,</p>
  <p>Thanks for shopping with <?= htmlspecialchars(STORE_NAME) ?>! We've received your order and will notify you as soon as it's processed.</p>

  <h2>📦 Order Summary</h2>
  <?= email_order_table($order_details['items'] ?? [], $subtotal, $shipping, $total) ?>

  <h2>🚚 Delivery Details</h2>
  <div class="info-block">
    <?= nl2br(htmlspecialchars($shipping_address)) ?>
  </div>

  <p style="font-size:13px; color:#888;">Payment Method: <strong><?= htmlspecialchars($payment_method) ?></strong></p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="<?= SITE_URL ?>user/orders.php" class="btn-primary">View My Orders →</a>
  </div>

  <p style="font-size:13px; color:#999;">Order placed on <?= date('D, d M Y · H:i', strtotime($order_date)) ?></p>
</div>
<?php
    $content = ob_get_clean();
    return sendEmail($customer_email, $subject, email_layout($content, 'Your order #' . $order_number . ' is confirmed!', $customer_email));
}

/**
 * Send an invoice email to the customer
 * (Avazonia order summary layout, invoice variant)
 *
 * @param string $customer_email Customer's email address
 * @param string $customer_name Customer's full name
 * @param int $order_id Order ID
 * @param array $order_details Order details (items, subtotal, shipping, total, status, payment_method)
 * @return bool True if email was sent successfully, false otherwise
 */
function sendInvoiceEmail($customer_email, $customer_name, $order_id, $order_details) {
    $order_number = $order_details['order_number'] ?? $order_id;
    $subject = "Invoice for Order #$order_number - " . STORE_NAME;

    $subtotal = 0;
    foreach (($order_details['items'] ?? []) as $item) {
        $subtotal += (float)($item['price'] ?? ($item['product_price'] ?? 0)) * (int)($item['quantity'] ?? 1);
    }

    $total = (float)($order_details['total'] ?? $order_details['total_amount'] ?? $subtotal);
    $shipping = (float)($order_details['shipping'] ?? 0);
    if ($shipping <= 0 && $total > $subtotal) {
        $shipping = $total - $subtotal;
    }
    if ($shipping < 0) $shipping = 0;

    $status = ucfirst($order_details['status'] ?? ($order_details['order_status'] ?? 'processing'));
    $payment_method = ucwords(str_replace('_', ' ', $order_details['payment_method'] ?? 'not specified'));
    $order_date = $order_details['order_date'] ?? date('Y-m-d H:i:s');

    ob_start(); ?>
<div class="email-hero">
  <h1>Your Invoice 🧾</h1>
  <p>Invoice <strong>#<?= htmlspecialchars($order_number) ?></strong> — <?= htmlspecialchars($status) ?></p>
</div>
<div class="email-body">
  <p>Hi <strong><?= htmlspecialchars($customer_name) ?></strong>,</p>
  <p>Thank you for your purchase! Please find the details of your order below.</p>

  <div class="info-block">
    <table style="width:100%; font-size:14px;">
      <tr>
        <td style="color:#888; padding-bottom:8px;">Order Ref</td>
        <td style="font-weight:700; text-align:right;">#<?= htmlspecialchars($order_number) ?></td>
      </tr>
      <tr>
        <td style="color:#888; padding-bottom:8px;">Order Date</td>
        <td style="text-align:right;"><?= date('D, d M Y · H:i', strtotime($order_date)) ?></td>
      </tr>
      <tr>
        <td style="color:#888; padding-bottom:8px;">Status</td>
        <td style="text-align:right;"><strong><?= htmlspecialchars($status) ?></strong></td>
      </tr>
      <tr>
        <td style="color:#888;">Payment Method</td>
        <td style="text-align:right;"><?= htmlspecialchars($payment_method) ?></td>
      </tr>
    </table>
  </div>

  <h2>🧾 Order Summary</h2>
  <?= email_order_table($order_details['items'] ?? [], $subtotal, $shipping, $total) ?>

  <p>If you have any questions about this invoice, reply to this email or reach out via WhatsApp — we're always here to help.</p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="<?= SITE_URL ?>user/orders.php" class="btn-primary">View My Orders →</a>
  </div>
</div>
<?php
    $content = ob_get_clean();
    return sendEmail($customer_email, $subject, email_layout($content, 'Invoice #' . $order_number, $customer_email));
}

/**
 * Send an order status update notification to the customer
 * (Avazonia "Order Status Update" template)
 *
 * @param string $order_number Order number
 * @param string $new_status New order status
 * @param string $customer_email Recipient email
 * @param string $customer_name Recipient name
 * @return bool True if email was sent successfully, false otherwise
 */
function sendStatusUpdateEmail($order_number, $new_status, $customer_email, $customer_name = 'Customer') {
    $status_labels = [
        'pending'     => ['label' => 'Pending',     'color' => '#6B7280', 'icon' => '⏳', 'msg' => 'Your order has been received and is awaiting confirmation.'],
        'processing'  => ['label' => 'Processing',  'color' => '#FA8C16', 'icon' => '⚙️', 'msg' => 'Your order is now being processed and prepared for dispatch.'],
        'confirmed'   => ['label' => 'Confirmed',   'color' => '#00A854', 'icon' => '✅', 'msg' => 'Great news! Your order has been approved and confirmed.'],
        'approved'    => ['label' => 'Approved',    'color' => '#00A854', 'icon' => '✅', 'msg' => 'Great news! Your order has been approved and confirmed.'],
        'paid'        => ['label' => 'Paid',        'color' => '#16A34A', 'icon' => '💳', 'msg' => 'Your payment has been received. Your order is now being prepared.'],
        'paid-full'   => ['label' => 'Paid In Full','color' => '#16A34A', 'icon' => '💳', 'msg' => 'Your payment has been received in full. Your order is now being prepared.'],
        'shipped'     => ['label' => 'Shipped',     'color' => '#1D4ED8', 'icon' => '🚚', 'msg' => 'Exciting news — your order has been shipped and is on its way to you.'],
        'arrived'     => ['label' => 'Arrived',     'color' => '#111111', 'icon' => '📦', 'msg' => 'Your pre-ordered item has arrived at our warehouse and is ready for final delivery.'],
        'delivered'   => ['label' => 'Delivered',   'color' => '#00A854', 'icon' => '🏁', 'msg' => 'Your order has been marked as delivered. We hope you enjoy your purchase!'],
        'cancelled'   => ['label' => 'Cancelled',   'color' => '#DC2626', 'icon' => '❌', 'msg' => 'Your order has been cancelled. If you did not request this, please contact support immediately.'],
        'refunded'    => ['label' => 'Refunded',    'color' => '#D97706', 'icon' => '💰', 'msg' => 'A refund has been processed for your order.'],
    ];

    $cur = $status_labels[strtolower($new_status)] ?? ['label' => ucfirst($new_status), 'color' => '#333333', 'icon' => 'ℹ️', 'msg' => 'The status of your order has been updated.'];
    $subject = "Order Status Update - {$cur['label']} (#{$order_number})";

    ob_start(); ?>
<div class="email-hero" style="background: linear-gradient(135deg, <?= $cur['color'] ?> 0%, #333 100%);">
  <h1>Order Status Update <?= $cur['icon'] ?></h1>
  <p>Order <strong>#<?= htmlspecialchars($order_number) ?></strong> is now <strong><?= strtoupper($cur['label']) ?></strong>.</p>
</div>
<div class="email-body">
  <p>Hi <strong><?= htmlspecialchars($customer_name) ?></strong>,</p>
  <p><?= $cur['msg'] ?></p>

  <div class="info-block">
    <table style="width:100%; font-size:14px;">
      <tr>
        <td style="color:#888; padding-bottom:8px;">Order Ref</td>
        <td style="font-weight:700; text-align:right;">#<?= htmlspecialchars($order_number) ?></td>
      </tr>
      <tr>
        <td style="color:#888; padding-bottom:8px;">New Status</td>
        <td style="text-align:right;">
          <span style="padding:4px 10px; border-radius:4px; background:<?= $cur['color'] ?>; color:#fff; font-size:10px; font-weight:700; text-transform:uppercase;">
            <?= $cur['label'] ?>
          </span>
        </td>
      </tr>
      <tr>
        <td style="color:#888;">Update Time</td>
        <td style="text-align:right;"><?= date('M d, Y H:i') ?></td>
      </tr>
    </table>
  </div>

  <p>You can track your order progress and view more details in your account dashboard.</p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="<?= SITE_URL ?>user/orders.php" class="btn-primary">View My Orders →</a>
  </div>

  <p>If you have any questions, feel free to reply to this email or reach out via WhatsApp.</p>
</div>
<?php
    $content = ob_get_clean();
    return sendEmail($customer_email, $subject, email_layout($content, 'Order Update — #' . $order_number, $customer_email));
}

/**
 * Send a password reset email (1h expiry)
 *
 * @param string $email Recipient email
 * @param string $name Recipient name
 * @param string $resetLink Reset URL
 * @return bool True if email was sent successfully
 */
function sendPasswordResetEmail($email, $name, $resetLink) {
    $subject = "Password Reset Request - " . STORE_NAME;

    ob_start(); ?>
<div class="email-hero" style="background: linear-gradient(135deg, #0A0A0A 0%, #333 100%);">
  <h1>Reset Your Password 🔐</h1>
  <p>We received a request to reset your <?= htmlspecialchars(STORE_NAME) ?> account password.</p>
</div>
<div class="email-body">
  <p>Hi <strong><?= htmlspecialchars($name ?: 'there') ?></strong>,</p>
  <p>Someone (hopefully you!) requested a password reset for the account associated with <strong><?= htmlspecialchars($email) ?></strong>.</p>
  <p>Click the button below to choose a new password:</p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="<?= htmlspecialchars($resetLink) ?>" class="btn-secondary">🔑 Reset My Password</a>
  </div>

  <div class="notice">
    ⏱ This link expires in <strong>1 hour</strong>. After that, you'll need to request a new reset link.
  </div>

  <hr class="divider">

  <p>If you didn't request a password reset, no action is needed — your account is safe.</p>

  <p style="font-size: 13px; color: #999; margin-top: 24px;">Or paste this URL in your browser:<br>
    <a href="<?= htmlspecialchars($resetLink) ?>" style="color: <?= PRIMARY_COLOR ?>; word-break: break-all;"><?= htmlspecialchars($resetLink) ?></a>
  </p>
</div>
<?php
    $content = ob_get_clean();
    return sendEmail($email, $subject, email_layout($content, 'Reset your ' . STORE_NAME . ' password — link expires in 1 hour', $email));
}

/**
 * Send a "password updated" confirmation email
 *
 * @param string $email Recipient email
 * @param string $name Recipient name
 * @return bool True if email was sent successfully
 */
function sendPasswordChangedEmail($email, $name = '') {
    $subject = "Password Updated - " . STORE_NAME;

    ob_start(); ?>
<div class="email-hero" style="background: linear-gradient(135deg, #00A854 0%, #22C55E 100%);">
  <h1>Password Updated ✅</h1>
  <p>Your account password was successfully changed.</p>
</div>
<div class="email-body">
  <p>Hi <strong><?= htmlspecialchars($name ?: 'there') ?></strong>,</p>
  <p>This is a confirmation that the password for your <?= htmlspecialchars(STORE_NAME) ?> account has been updated successfully.</p>
  <p>If you did not make this change, please <a href="mailto:<?= htmlspecialchars(STORE_EMAIL) ?>" style="color: <?= PRIMARY_COLOR ?>; font-weight: 600;">contact support</a> immediately.</p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="<?= SITE_URL ?>login.php" class="btn-primary">Sign In →</a>
  </div>
</div>
<?php
    $content = ob_get_clean();
    return sendEmail($email, $subject, email_layout($content, 'Your password has been updated', $email));
}

/**
 * Notify the admin of a new contact form submission
 * (Avazonia "Contact Admin Notify" template)
 *
 * @param string $customerName Customer name
 * @param string $customerEmail Customer email
 * @param string $subjectLine Subject line
 * @param string $messageBody Message body
 * @param string $customerPhone Customer phone (optional)
 * @return bool True if email was sent successfully
 */
function sendContactAdminNotifyEmail($customerName, $customerEmail, $subjectLine, $messageBody, $customerPhone = '') {
    $subject = "New Contact Form Submission: " . $subjectLine;

    ob_start(); ?>
<div class="email-hero" style="background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 100%);">
  <h1>New Support Request 📩</h1>
  <p>A customer has submitted a message via the Contact Form.</p>
</div>
<div class="email-body">
  <p>Hey Admin,</p>
  <p>You have received a new message from the <strong><?= htmlspecialchars(STORE_NAME) ?></strong> contact form. Please review the details below:</p>

  <div class="info-block">
    👤 <strong>Name:</strong> <?= htmlspecialchars($customerName) ?><br>
    📧 <strong>Email:</strong> <?= htmlspecialchars($customerEmail) ?><?php if ($customerPhone !== ''): ?><br>
    📞 <strong>Phone:</strong> <?= htmlspecialchars($customerPhone) ?><?php endif; ?><br>
    📅 <strong>Date:</strong> <?= date('D, d M Y · H:i T') ?>
  </div>

  <h2>📝 Message Details</h2>
  <div style="background: #FAFAFA; border: 1px solid #EAEAEA; padding: 24px; border-radius: 12px; margin: 20px 0;">
    <p style="margin-bottom: 12px;"><strong>Subject:</strong> <?= htmlspecialchars($subjectLine) ?></p>
    <p style="margin-bottom: 0; white-space: pre-wrap; font-size: 15px; color: #333; line-height: 1.6;"><?= htmlspecialchars($messageBody) ?></p>
  </div>

  <div style="text-align: center; margin: 32px 0;">
    <a href="mailto:<?= htmlspecialchars($customerEmail) ?>?subject=Re: <?= urlencode($subjectLine) ?>" class="btn-primary">Reply to Customer →</a>
  </div>

  <p style="font-size: 13px; color: #999;">This is an automated notification from your website's contact form.</p>
</div>
<?php
    $content = ob_get_clean();
    return sendEmail(STORE_EMAIL, $subject, email_layout($content, "New Contact Form Submission: {$subjectLine}", STORE_EMAIL));
}

/**
 * Send a welcome email to a new newsletter subscriber
 * (Avazonia "Newsletter Welcome" template)
 *
 * @param string $toEmail Subscriber email
 * @return bool True if email was sent successfully
 */
function sendNewsletterWelcomeEmail($toEmail) {
    $subject = "Welcome to " . STORE_NAME . " — You're In!";

    ob_start(); ?>
<div class="email-hero" style="background: linear-gradient(135deg, #0A0A0A 0%, #1a1a2e 100%);">
  <h1>Welcome to the Family! 🎉</h1>
  <p>You're officially on the <?= htmlspecialchars(STORE_NAME) ?> insider list.</p>
</div>
<div class="email-body">
  <p>Hi there,</p>
  <p>Thanks for subscribing to the <strong><?= htmlspecialchars(STORE_NAME) ?></strong> newsletter! You'll now be the first to know about:</p>

  <div class="info-block">
    🔥 <strong>Exclusive Drops</strong> — New arrivals before anyone else<br>
    🏷️ <strong>Members-Only Deals</strong> — Special discounts just for subscribers<br>
    📦 <strong>Restock Alerts</strong> — Never miss your favorite items<br>
    🎁 <strong>Seasonal Promos</strong> — Holiday sales, flash deals &amp; more
  </div>

  <p>We keep it short, relevant, and spam-free. Expect updates only when it matters.</p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="<?= SITE_URL ?>shop.php" class="btn-primary">Start Shopping →</a>
  </div>

  <p style="font-size: 13px; color: #999;">You subscribed with <strong><?= htmlspecialchars($toEmail) ?></strong> on <?= date('D, d M Y · H:i') ?>.</p>
</div>
<?php
    $content = ob_get_clean();
    return sendEmail($toEmail, $subject, email_layout($content, "Welcome to " . STORE_NAME . " — you're in!", $toEmail));
}

/**
 * Notify the admin of a new newsletter subscriber
 * (Avazonia "Newsletter Admin Notify" template)
 *
 * @param string $subscriberEmail Subscriber email
 * @return bool True if email was sent successfully
 */
function sendNewsletterAdminNotifyEmail($subscriberEmail) {
    $subject = "New Subscriber: " . $subscriberEmail;

    ob_start(); ?>
<div class="email-hero" style="background: linear-gradient(135deg, #16A34A 0%, #22D3EE 100%);">
  <h1>New Subscriber! 📬</h1>
  <p>Someone just joined the <?= htmlspecialchars(STORE_NAME) ?> mailing list.</p>
</div>
<div class="email-body">
  <p>Hey Admin,</p>
  <p>A new visitor has subscribed to your newsletter. Here are the details:</p>

  <div class="info-block">
    📧 <strong>Email:</strong> <?= htmlspecialchars($subscriberEmail) ?><br>
    📅 <strong>Date:</strong> <?= date('D, d M Y · H:i T') ?><br>
    🌐 <strong>Source:</strong> Website newsletter
  </div>

  <p>Your mailing list is growing! You can view and manage all subscribers from your admin dashboard.</p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="<?= SITE_URL ?>admin/newsletter.php" class="btn-primary">View Subscribers →</a>
  </div>

  <p style="font-size: 13px; color: #999;">This is an automated notification from <?= htmlspecialchars(STORE_NAME) ?>.</p>
</div>
<?php
    $content = ob_get_clean();
    return sendEmail(STORE_EMAIL, $subject, email_layout($content, "New newsletter subscriber: {$subscriberEmail}", STORE_EMAIL));
}

// Utility function to check if email functionality is working
function testEmailConfiguration() {
    if (EMAIL_DEVELOPMENT_MODE) {
        return [
            'status' => 'development',
            'message' => 'Email system is in development mode. Emails are logged but not sent.',
            'working' => true
        ];
    }

    // Try to send a test email to the store email
    $test_result = sendEmail(STORE_EMAIL, 'Test Email Configuration', 'This is a test email to verify email configuration is working.');

    return [
        'status' => $test_result ? 'success' : 'error',
        'message' => $test_result ? 'Email configuration is working correctly.' : 'Email configuration has issues.',
        'working' => $test_result
    ];
}

?>
