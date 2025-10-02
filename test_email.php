<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the mailer class
require_once __DIR__ . '/includes/mailer.php';

// Test email configuration
$test_email = 'minatoflash82@gmail.com'; // Replace with your email address

// Create a test message
$subject = 'Test Email from ASO Online Market';
$message = 'This is a test email from the ASO Online Market contact form.';

try {
    // Initialize the mailer
    $mailer = new Mailer();
    
    // Send test email using the contact form method
    $result = $mailer->sendContactForm([
        'name' => 'Test User',
        'email' => $test_email,
        'subject' => $subject,
        'message' => $message,
        'phone' => '1234567890'
    ]);
    
    if ($result) {
        echo "<div style='color: green; font-weight: bold;'>Test email sent successfully to $test_email</div>";
    } else {
        echo "<div style='color: red; font-weight: bold;'>Failed to send test email. Check your mail configuration and error logs.</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>Error: " . $e->getMessage() . "</div>";
    echo "<div>Stack trace: <pre>" . $e->getTraceAsString() . "</pre></div>";
}

// Display current mail configuration (without passwords)
echo "<h3>Current Mail Configuration:</h3>";
$config = include __DIR__ . '/includes/config/mail_config.php';
$config_display = $config;
if (isset($config_display['smtp']['password'])) {
    $config_display['smtp']['password'] = '********';
}
echo "<pre>" . print_r($config_display, true) . "</pre>";
?>

<h3>Next Steps:</h3>
<ol>
    <li>Check your email inbox (and spam folder) for the test email.</li>
    <li>If you don't receive the email, check your server's error logs for any error messages.</li>
    <li>Verify your SMTP settings in <code>includes/config/mail_config.php</code>.</li>
    <li>Test the contact form directly at <a href='contact.php'>contact.php</a>.</li>
</ol>

<p>If you encounter any issues, please check the following:</p>
<ul>
    <li>SMTP server is accessible from your hosting environment</li>
    <li>Port number is correct (587 for TLS, 465 for SSL)</li>
    <li>Authentication credentials are correct</li>
    <li>Your email service allows SMTP access (may need to enable "Less secure app access" for Gmail)</li>
</ul>
