<?php
/**
 * Email Configuration
 * - Settings for sending emails (invoices, notifications, etc.)
 * 
 * For production use, configure your server's mail settings or use an SMTP service.
 * For development, emails will be logged to the logs directory instead of being sent.
 */

// Store information
define('STORE_EMAIL', 'orders@yourdomain.com'); // Your store's email
define('STORE_NAME', 'ASO Online Market');

// Development mode - Set to false in production to send real emails
define('EMAIL_DEVELOPMENT_MODE', true);

// Create logs directory if it doesn't exist
if (!file_exists(dirname(__FILE__) . '/../logs')) {
    mkdir(dirname(__FILE__) . '/../logs', 0755, true);
}

/**
 * Send an email with HTML support
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message HTML email content
 * @param string $headers Optional custom headers
 * @return bool True if email was sent successfully, false otherwise
 */
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

    if (EMAIL_DEVELOPMENT_MODE) {
        // In development, log the email instead of sending it
        $log_message = "DEVELOPMENT MODE - Email not sent:\n";
        $log_message .= "To: $to\n";
        $log_message .= "Subject: $subject\n";
        $log_message .= "--- Message ---\n$message\n";
        
        $log_file = dirname(__FILE__) . '/../logs/email_' . date('Y-m-d') . '.log';
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "]\n" . $log_message . "\n\n", FILE_APPEND);
        return true;
    } else {
        // In production, send the actual email
        $result = @mail($to, $subject, $message, $default_headers);
        
        // Log the email attempt
        $log_message = $result ? "Email sent successfully" : "Failed to send email";
        $log_message .= "\nTo: $to\nSubject: $subject\n";
        
        $log_file = dirname(__FILE__) . '/../logs/email_' . date('Y-m-d') . '.log';
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] " . $log_message . "\n\n", FILE_APPEND);
        
        return $result;
    }
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

?>
