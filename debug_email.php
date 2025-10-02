<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include email configuration
require_once __DIR__ . '/includes/email_config.php';

// Test email configuration
$to = 'minatoflash82@gmail.com';
$subject = 'Test Email from ' . STORE_NAME;
$message = '<h1>Test Email</h1><p>This is a test email from ' . STORE_NAME . '</p>';

// Debug information
function debug_info() {
    $info = [
        'PHP Version' => phpversion(),
        'PHP OpenSSL' => extension_loaded('openssl') ? 'Enabled' : 'Not enabled',
        'PHP Allow URL Fopen' => ini_get('allow_url_fopen') ? 'Enabled' : 'Disabled',
        'PHPMailer Version' => PHPMailer::VERSION,
        'SMTP Debug' => 2, // SMTP::DEBUG_SERVER
        'SMTP Host' => SMTP_HOST,
        'SMTP Port' => SMTP_PORT,
        'SMTP Secure' => SMTP_SECURE,
        'SMTP Username' => SMTP_USERNAME,
        'SMTP Password' => SMTP_PASSWORD ? '***' : 'Not set',
        'From Email' => STORE_EMAIL,
        'From Name' => STORE_NAME,
        'Site URL' => SITE_URL,
        'Development Mode' => EMAIL_DEVELOPMENT_MODE ? 'ON' : 'OFF'
    ];
    
    echo "<h2>Debug Information</h2>";
    echo "<pre>";
    print_r($info);
    echo "</pre>";
}

// Test SMTP connection
function test_smtp_connection() {
    $timeout = 10; // seconds
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    
    echo "<h3>Testing SMTP Connection to $host:$port</h3>";
    
    // Test basic connection
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($socket) {
        echo "<p>✅ Successfully connected to $host:$port</p>";
        fclose($socket);
        return true;
    } else {
        echo "<p class='error'>❌ Failed to connect to $host:$port</p>";
        echo "<p>Error $errno: $errstr</p>";
        
        // Try common SMTP ports if default fails
        $ports = [587, 465, 25];
        foreach ($ports as $testPort) {
            if ($testPort != $port) {
                $testSocket = @fsockopen($host, $testPort, $errno, $errstr, $timeout);
                if ($testSocket) {
                    echo "<p>✅ Found alternative port: $host:$testPort is open</p>";
                    fclose($testSocket);
                    echo "<p>Try updating SMTP_PORT in your .env file to $testPort</p>";
                    return false;
                }
            }
        }
        
        // Check DNS
        $ip = gethostbyname($host);
        if ($ip === $host) {
            echo "<p class='error'>❌ Could not resolve hostname: $host</p>";
            echo "<p>Possible solutions:</p>";
            echo "<ul>";
            echo "<li>Check your internet connection</li>";
            echo "<li>Verify the SMTP hostname is correct</li>";
            echo "<li>Try using an IP address instead of a hostname</li>";
            echo "<li>Check your DNS settings or try using Google's DNS (8.8.8.8)</li>";
            echo "</ul>";
        } else {
            echo "<p>Resolved $host to IP: $ip</p>";
            echo "<p>But connection failed. Possible firewall or network issue.</p>";
        }
        
        return false;
    }
}

// Test email sending
function test_email($to, $subject, $message) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->SMTPDebug = 2; // Enable verbose debug output
        
        // Recipients
        $mail->setFrom(SMTP_USERNAME, STORE_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "<h3>Error Details:</h3>";
        echo "<pre>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</pre>";
        return false;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Test - <?php echo STORE_NAME; ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f4f4f4; padding: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Email Test - <?php echo STORE_NAME; ?></h1>
    
    <?php
    // Display debug info
    debug_info();
    
    // Test connection first
    if (!test_smtp_connection()) {
        echo "<div class='error'>Cannot send email: SMTP connection failed</div>";
        return;
    }
    
    // Only try to send if not in development mode
    if (!EMAIL_DEVELOPMENT_MODE) {
        echo "<h2>Sending Test Email...</h2>";
        if (test_email($to, $subject, $message)) {
            echo "<p class='success'>Test email sent successfully to $to</p>";
            echo "<p>Please check your inbox (and spam folder) for the test email.</p>";
        } else {
            echo "<p class='error'>Failed to send test email. See error details above.</p>";
        }
    } else {
        echo "<div class='error'>Email sending is disabled in development mode.</div>";
        echo "<p>Set <code>EMAIL_DEVELOPMENT_MODE=false</code> in your .env file to enable sending emails.</p>";
    }
    ?>
    
    <h3>Common Issues:</h3>
    <ul>
        <li>Check that your SMTP server is accessible from your hosting environment</li>
        <li>Verify the port number is correct (587 for TLS, 465 for SSL)</li>
        <li>Ensure your email service allows SMTP access</li>
        <li>For Gmail, you may need to enable "Less secure app access" or use an App Password</li>
        <li>Check your server's error logs for more detailed error messages</li>
    </ul>
    
    <p><a href="javascript:location.reload()">Run Test Again</a></p>
</body>
</html>
