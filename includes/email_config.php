<?php
/**
 * Email Configuration
 * - Settings for sending emails (invoices, notifications, etc.)
 * 
 * Uses PHPMailer with SMTP for reliable email delivery
 */

// Load Composer's autoloader
require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Store information
define('STORE_EMAIL', $_ENV['STORE_EMAIL'] ?? 'minatoflash82@gmail.com');
define('STORE_NAME', $_ENV['STORE_NAME'] ?? 'ASO Online Market');
if (!defined('SITE_URL')) {
    define('SITE_URL', rtrim($_ENV['SITE_URL'] ?? 'http://localhost/my-shop-2-main/', '/') . '/');
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

// Require Composer's autoloader
require __DIR__ . '/../vendor/autoload.php';

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

// Function to send invoice email
function sendInvoiceEmail($customer_email, $customer_name, $order_id, $order_details) {
    $subject = "Invoice for Order #$order_id - " . STORE_NAME;

    // Create HTML email template
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Invoice #$order_id</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: #f8f9fa; padding: 20px; text-align: center; border-bottom: 3px solid #007bff; }
            .invoice-details { margin: 20px 0; }
            .invoice-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .invoice-table th, .invoice-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
            .invoice-table th { background-color: #f8f9fa; font-weight: bold; }
            .total-row { background-color: #e9ecef; font-weight: bold; }
            .footer { margin-top: 30px; padding: 20px; background: #f8f9fa; border-top: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>" . STORE_NAME . "</h1>
            <h2>Invoice #$order_id</h2>
        </div>

        <div class='invoice-details'>
            <p><strong>Customer:</strong> " . htmlspecialchars($customer_name) . "</p>
            <p><strong>Order Date:</strong> " . date('F j, Y \a\t g:i A', strtotime($order_details['order_date'] ?? 'now')) . "</p>
            <p><strong>Status:</strong> " . ucfirst($order_details['order_status'] ?? $order_details['status'] ?? 'processing') . "</p>
            <p><strong>Payment Method:</strong> " . ucfirst(str_replace('_', ' ', $order_details['payment_method'] ?? 'not specified')) . "</p>
        </div>

        <table class='invoice-table'>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>";

    // Add order items
    if (!empty($order_details['items']) && is_array($order_details['items'])) {
        foreach ($order_details['items'] as $item) {
            $itemTotal = ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
            $message .= "
                <tr>
                    <td>" . htmlspecialchars($item['product_name'] ?? 'Product') . "</td>
                    <td>" . formatCurrency($item['product_price'] ?? $item['price'] ?? 0) . "</td>
                    <td>" . ($item['quantity'] ?? 1) . "</td>
                    <td>" . formatCurrency($itemTotal) . "</td>
                </tr>";
        }
    } else {
        $message .= "
                <tr>
                    <td colspan='4' class='text-center'>No items found in this order</td>
                </tr>";
    }
    $message .= "
            </tbody>
            <tfoot>
                <tr>
                    <td colspan='3' class='text-end'>Subtotal:</td>
                    <td>" . formatCurrency($order_details['subtotal'] ?? $order_details['total_amount'] ?? 0) . "</td>
                </tr>
                <tr>
                    <td colspan='3' class='text-end'>Shipping:</td>
                    <td>" . formatCurrency($order_details['shipping'] ?? 0) . "</td>
                </tr>
                <tr class='total-row'>
                    <td colspan='3' class='text-end'><strong>Total Amount:</strong></td>
                    <td><strong>" . formatCurrency($order_details['total'] ?? $order_details['total_amount'] ?? 0) . "</strong></td>
                </tr>
            </tfoot>
        </table>

        <div class='footer'>
            <p>Thank you for your business!</p>
            <p>For questions about this order, please contact us at <a href='mailto:" . STORE_EMAIL . "'>" . STORE_EMAIL . "</a></p>
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </body>
    </html>";

    // Prepare a logfile path (same file used by sendEmail)
    $log_file = dirname(__FILE__) . '/../logs/email_' . date('Y-m-d') . '.log';

    // Log the attempt before sending (file-based)
    $attempt_entry = "[" . date('Y-m-d H:i:s') . "] Status update email attempt: order={$order_number}, to={$customer_email}, status={$new_status} - sending...\n";
    file_put_contents($log_file, $attempt_entry, FILE_APPEND);

    // Attempt to send and log the result for debugging
    $result = sendEmail($customer_email, $subject, $message);

    $result_entry = "[" . date('Y-m-d H:i:s') . "] Status update email result: order={$order_number}, to={$customer_email}, status={$new_status}, result=" . ($result ? 'sent' : 'failed') . "\n";
    file_put_contents($log_file, $result_entry, FILE_APPEND);

    // Also log to PHP error log for immediate visibility
    error_log("Status update email attempt: order={$order_number}, to={$customer_email}, status={$new_status}, result=" . ($result ? 'sent' : 'failed'));

    return $result;
}

/**
 * Send order confirmation email to customer
 * 
 * @param string $customer_email Customer's email address
 * @param string $customer_name Customer's full name
 * @param int $order_id Order ID
 * @param array $order_details Order details (total, items, etc.)
 * @return bool True if email was sent successfully, false otherwise
 */
function sendOrderConfirmationEmail($customer_email, $customer_name, $order_id, $order_details) {
    $subject = "Order Confirmation #$order_id - " . STORE_NAME;

    // Format order items for the email
    $order_items_html = '';
    $subtotal = 0;
    
    if (!empty($order_details['items'])) {
        foreach ($order_details['items'] as $item) {
            $item_total = $item['price'] * $item['quantity'];
            $subtotal += $item_total;
            
            $order_items_html .= "<tr>";
            $order_items_html .= "<td>" . htmlspecialchars($item['name']) . "</td>";
            $order_items_html .= "<td class='text-right'>" . formatCurrency($item['price']) . "</td>";
            $order_items_html .= "<td class='text-center'>" . $item['quantity'] . "</td>";
            $order_items_html .= "<td class='text-right'>" . formatCurrency($item_total) . "</td>";
            $order_items_html .= "</tr>";
        }
    }
    
    // Calculate totals
    $shipping = $order_details['shipping'] ?? 0;
    $tax = $order_details['tax'] ?? 0;
    $total = $subtotal + $shipping + $tax;

    // Create HTML email template
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Order Confirmation #$order_id</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #1A1A1A; margin: 0; padding: 20px; background-color: #F9F9F9; }
            .container { max-width: 600px; margin: 0 auto; background: #FFFFFF; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
            .header { background: #0a4722; padding: 30px 20px; text-align: center; }
            .logo { max-width: 180px; height: auto; margin-bottom: 15px; display: block; margin-left: auto; margin-right: auto; }
            .header h2 { color: #FFFFFF; margin: 0; font-size: 24px; font-weight: 800; }
            .content { padding: 30px; }
            .content p { font-size: 15px; color: #444; }
            .order-summary { background-color: #F8F9FA; border-radius: 8px; padding: 20px; margin: 25px 0; border: 1px solid #EEEEEE; }
            .order-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
            .order-table th { padding: 12px 10px; border-bottom: 2px solid #DDDDDD; text-align: left; color: #888888; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
            .order-table td { padding: 12px 10px; border-bottom: 1px solid #EEEEEE; font-size: 14px; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .total-row td { padding-top: 15px; font-size: 16px; color: #1A1A1A; }
            .address-box { padding: 15px; background: #FFFFFF; border: 1px solid #EEEEEE; border-radius: 6px; margin-top: 10px; font-size: 14px; }
            .footer { padding: 20px; background: #F8F9FA; text-align: center; border-top: 1px solid #EEEEEE; }
            .footer p { font-size: 12px; color: #888888; margin: 5px 0; }
            .btn { display: inline-block; padding: 12px 24px; background-color: #0a4722; color: #FFFFFF !important; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <img src='cid:store_logo' alt='" . htmlspecialchars(STORE_NAME) . "' class='logo'>
                <h2>Order Confirmed!</h2>
            </div>

            <div class='content'>
                <p>Hi <strong>" . htmlspecialchars($customer_name) . "</strong>,</p>
                <p>Thank you for your purchase! We've received your order and it is currently being processed.</p>
                
                <div class='order-summary'>
                    <h3 style='margin-top: 0; font-size: 18px; color: #1A1A1A;'>Order Details</h3>
                    <p style='margin: 0 0 5px 0; font-size: 14px;'><strong>Order Number:</strong> #$order_id</p>
                    <p style='margin: 0 0 15px 0; font-size: 14px;'><strong>Order Date:</strong> " . date('F j, Y \a\t g:i A') . "</p>
                    
                    <table class='order-table'>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class='text-right'>Price</th>
                                <th class='text-center'>Qty</th>
                                <th class='text-right'>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            $order_items_html
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan='3' class='text-right' style='padding-top: 15px;'>Subtotal:</td>
                                <td class='text-right' style='padding-top: 15px;'>" . formatCurrency($subtotal) . "</td>
                            </tr>
                            <tr>
                                <td colspan='3' class='text-right'>Shipping:</td>
                                <td class='text-right'>" . formatCurrency($shipping) . "</td>
                            </tr>
                            <tr>
                                <td colspan='3' class='text-right'>Tax:</td>
                                <td class='text-right'>" . formatCurrency($tax) . "</td>
                            </tr>
                            <tr class='total-row'>
                                <td colspan='3' class='text-right'><strong>Total Amount:</strong></td>
                                <td class='text-right'><strong>" . formatCurrency($total) . "</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <table width='100%' cellpadding='0' cellspacing='0' border='0'>
                    <tr>
                        <td width='48%' valign='top'>
                            <h4 style='margin: 0 0 10px 0; color: #1A1A1A;'>Shipping Address</h4>
                            <div class='address-box'>
                                " . nl2br(htmlspecialchars($order_details['shipping_address'] ?? 'Not specified')) . "
                            </div>
                        </td>
                        <td width='4%'></td>
                        <td width='48%' valign='top'>
                            <h4 style='margin: 0 0 10px 0; color: #1A1A1A;'>Payment Method</h4>
                            <div class='address-box'>
                                " . ucwords(str_replace('_', ' ', $order_details['payment_method'] ?? 'Not specified')) . "
                            </div>
                        </td>
                    </tr>
                </table>
                
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='" . SITE_URL . "user/orders.php' class='btn'>View Your Order</a>
                </div>
            </div>

            <div class='footer'>
                <p>Thank you for shopping with us!</p>
                <p>If you have any questions, contact us at <a href='mailto:" . STORE_EMAIL . "' style='color: #0a4722;'>" . STORE_EMAIL . "</a>.</p>
                <p>&copy; " . date('Y') . " " . htmlspecialchars(STORE_NAME) . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";

    return sendEmail($customer_email, $subject, $message);
}

// Send an order status update notification to the customer
function sendStatusUpdateEmail($order_number, $new_status, $customer_email, $customer_name = 'Customer') {
    $status_display = ucfirst($new_status);
    $subject = "Order Status Update - {$status_display} (#{$order_number})";

    // Determine specific messaging based on status
    $status_message = "Your order status has been updated to <strong>{$status_display}</strong>.";
    $status_highlight = '#0a4722';
    if ($new_status === 'shipped') {
        $status_message = "Great news! Your order has been <strong>Shipped</strong> and is on its way to you.";
        $status_highlight = '#1d6fbd';
    } elseif ($new_status === 'delivered') {
        $status_message = "Your order has been <strong>Delivered</strong>! We hope you enjoy your purchase.";
        $status_highlight = '#15803d';
    } elseif ($new_status === 'cancelled') {
        $status_message = "Your order has been <strong>Cancelled</strong>. If you did not request this, please contact support immediately.";
        $status_highlight = '#dc2626';
    }

    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Order Status Update</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #1A1A1A; margin: 0; padding: 0; background-color: #F4F6F8; }
            .wrapper { width: 100%; padding: 20px 0; }
            .container { max-width: 640px; margin: 0 auto; background: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08); }
            .header { background: #0a4722; padding: 32px 24px; text-align: center; }
            .logo { max-width: 160px; height: auto; margin: 0 auto 12px; display: block; }
            .header h2 { color: #FFFFFF; margin: 0; font-size: 26px; letter-spacing: 0.03em; }
            .hero { padding: 32px 28px 20px; }
            .hero p { margin: 0 0 18px; font-size: 16px; color: #374151; }
            .notice { background-color: #F8FAFC; border-left: 5px solid {$status_highlight}; padding: 22px 20px; border-radius: 12px; }
            .notice p { margin: 0; color: #111827; font-size: 16px; }
            .details { margin-top: 24px; }
            .details .item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #E5E7EB; font-size: 14px; color: #4B5563; }
            .details .item:last-child { border-bottom: none; }
            .cta { text-align: center; margin-top: 28px; }
            .btn { display: inline-block; padding: 14px 28px; background: #0a4722; color: #FFFFFF !important; text-decoration: none; border-radius: 999px; font-weight: 700; font-size: 14px; }
            .footer { padding: 24px 28px 28px; background: #F8FAFC; color: #6B7280; font-size: 13px; text-align: center; }
            .footer a { color: #0a4722; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class='wrapper'>
            <div class='container'>
                <div class='header'>
                    <img src='cid:store_logo' alt='" . htmlspecialchars(STORE_NAME) . "' class='logo'>
                    <h2>Order Status Update</h2>
                </div>
                <div class='hero'>
                    <p>Hi <strong>" . htmlspecialchars($customer_name) . "</strong>,</p>
                    <p>We wanted to let you know that the status for your order <strong>#{$order_number}</strong> has changed.</p>
                    <div class='notice'>
                        <p>{$status_message}</p>
                    </div>
                    <div class='details'>
                        <div class='item'><span>Order Number</span><strong>#{$order_number}</strong></div>
                        <div class='item'><span>Current Status</span><strong>{$status_display}</strong></div>
                    </div>
                    <div class='cta'>
                        <a href='" . SITE_URL . "user/orders.php' class='btn'>View Your Order</a>
                    </div>
                </div>
                <div class='footer'>
                    <p>Thank you for shopping with us! If you need help, reply to this email or contact us at <a href='mailto:" . STORE_EMAIL . "'>" . STORE_EMAIL . "</a>.</p>
                    <p>&copy; " . date('Y') . " " . htmlspecialchars(STORE_NAME) . ". All rights reserved.</p>
                </div>
            </div>
        </div>
    </body>
    </html>";

    return sendEmail($customer_email, $subject, $message);
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
