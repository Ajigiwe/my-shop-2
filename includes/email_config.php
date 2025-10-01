<?php
/**
 * Email Configuration
 * - Settings for sending emails (invoices, notifications, etc.)
 *
 * CONFIGURATION OPTIONS:
 * 1. For Development: Set EMAIL_DEVELOPMENT_MODE to true (emails are logged, not sent)
 * 2. For Production with SMTP: Set EMAIL_DEVELOPMENT_MODE to false and configure SMTP settings below
 * 3. For Production with external service: Use PHPMailer or similar library
 */

// Email configuration
define('SMTP_HOST', 'localhost'); // Change to your SMTP server (e.g., 'smtp.gmail.com')
define('SMTP_PORT', 25); // Common ports: 25, 465 (SSL), 587 (TLS)
define('SMTP_USERNAME', ''); // Your email username
define('SMTP_PASSWORD', ''); // Your email password
define('SMTP_ENCRYPTION', ''); // 'tls' or 'ssl'

// Store email settings
define('STORE_EMAIL', 'orders@yourstore.com'); // Change to your store's email
define('STORE_NAME', 'ASO Online Market');

// Development mode - Set to false for production
define('EMAIL_DEVELOPMENT_MODE', true);

// Create logs directory if it doesn't exist
if (!file_exists(dirname(__FILE__) . '/../logs')) {
    mkdir(dirname(__FILE__) . '/../logs', 0755, true);
}

// Function to send email
function sendEmail($to, $subject, $message, $headers = '') {
    // Basic email headers
    $default_headers = "From: " . STORE_NAME . " <" . STORE_EMAIL . ">\r\n";
    $default_headers .= "Reply-To: " . STORE_EMAIL . "\r\n";
    $default_headers .= "MIME-Version: 1.0\r\n";
    $default_headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Use custom headers if provided
    if (!empty($headers)) {
        $default_headers = $headers;
    }

    // For development/testing, you can use PHP's built-in mail() function
    // For production, consider using PHPMailer or similar library

    if (EMAIL_DEVELOPMENT_MODE) {
        // In development, log the email instead of sending it
        $log_message = "DEVELOPMENT MODE - Email not sent:\n";
        $log_message .= "To: $to\n";
        $log_message .= "Subject: $subject\n";
        $log_message .= "Headers: $default_headers\n";
        $log_message .= "--- Message ---\n$message\n";
        $log_message .= "---------------\n";

        // Log to a file for development debugging
        error_log($log_message, 3, dirname(__FILE__) . '/../logs/email.log');

        // Return true to simulate successful sending
        return true;
    }

    // Production: Try to send email with error handling
    try {
        $result = mail($to, $subject, $message, $default_headers);
        if (!$result) {
            error_log("Failed to send email to: $to with subject: $subject");
        }
        return $result;
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
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
            <p><strong>Order Date:</strong> " . date('F j, Y \a\t g:i A', strtotime($order_details['order_date'])) . "</p>
            <p><strong>Status:</strong> " . ucfirst($order_details['status']) . "</p>
            <p><strong>Payment Method:</strong> " . ucfirst(str_replace('_', ' ', $order_details['payment_method'])) . "</p>
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

    // Add order items (you would need to fetch these from the database)
    // For now, this is a placeholder structure
    $message .= "
                <tr>
                    <td>Order Items</td>
                    <td>View attached invoice for details</td>
                    <td>-</td>
                    <td>-</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class='total-row'>
                    <td colspan='3'>Total Amount</td>
                    <td>" . formatCurrency($order_details['total_amount']) . "</td>
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

// Function to get email log (for development debugging)
function getEmailLog($lines = 10) {
    $log_file = dirname(__FILE__) . '/../logs/email.log';

    if (!file_exists($log_file)) {
        return 'No email log file found.';
    }

    $log_content = file_get_contents($log_file);
    $log_lines = explode("\n", $log_content);

    // Return last N lines
    $recent_lines = array_slice($log_lines, -$lines);
    return implode("\n", $recent_lines);
}
?>
