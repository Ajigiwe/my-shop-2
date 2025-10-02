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
define('SITE_URL', $_ENV['SITE_URL'] ?? 'http://localhost/My%20Shop2/');

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
            <p><strong>Status:</strong> " . ucfirst($order_details['status'] ?? 'processing') . "</p>
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

    return sendEmail($customer_email, $subject, $message);
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
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
            .header { background: #f8f9fa; padding: 20px; text-align: center; border-bottom: 3px solid #28a745; }
            .order-details { margin: 20px 0; padding: 0 20px; }
            .order-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .order-table th, .order-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
            .order-table th { background-color: #f8f9fa; font-weight: bold; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .total-row { background-color: #e9ecef; font-weight: bold; }
            .footer { margin-top: 30px; padding: 20px; background: #f8f9fa; border-top: 1px solid #ddd; font-size: 0.9em; color: #6c757d; }
            .btn { display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 4px; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h2 style='color: #28a745;'>Order Confirmed!</h2>
            <p>Thank you for your order, " . htmlspecialchars($customer_name) . "!</p>
        </div>

        <div class='order-details'>
            <p>Your order has been received and is being processed. Here are your order details:</p>
            
            <p><strong>Order Number:</strong> #$order_id</p>
            <p><strong>Order Date:</strong> " . date('F j, Y \a\t g:i A') . "</p>
            <p><strong>Status:</strong> Processing</p>
            
            <h3>Order Summary</h3>
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
                        <td colspan='3' class='text-right'><strong>Subtotal:</strong></td>
                        <td class='text-right'>" . formatCurrency($subtotal) . "</td>
                    </tr>
                    <tr>
                        <td colspan='3' class='text-right'><strong>Shipping:</strong></td>
                        <td class='text-right'>" . formatCurrency($shipping) . "</td>
                    </tr>
                    <tr>
                        <td colspan='3' class='text-right'><strong>Tax:</strong></td>
                        <td class='text-right'>" . formatCurrency($tax) . "</td>
                    </tr>
                    <tr class='total-row'>
                        <td colspan='3' class='text-right'><strong>Total:</strong></td>
                        <td class='text-right'><strong>" . formatCurrency($total) . "</strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <div style='margin-top: 20px;'>
                <p><strong>Shipping Address:</strong><br>
                " . nl2br(htmlspecialchars($order_details['shipping_address'] ?? 'Not specified')) . "</p>
                
                <p><strong>Billing Address:</strong><br>
                " . nl2br(htmlspecialchars($order_details['billing_address'] ?? 'Same as shipping')) . "</p>
                
                <p><strong>Payment Method:</strong> " . 
                ucwords(str_replace('_', ' ', $order_details['payment_method'] ?? 'Not specified')) . "</p>
            </div>
            
            <div style='margin: 30px 0; text-align: center;'>
                <a href='" . SITE_URL . "user/orders.php' class='btn'>View Your Order</a>
            </div>
        </div>

        <div class='footer'>
            <p>Thank you for shopping with us! If you have any questions about your order, please don't hesitate to contact our customer service team at <a href='mailto:" . STORE_EMAIL . "'>" . STORE_EMAIL . "</a>.</p>
            <p>This is an automated message. Please do not reply to this email.</p>
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
