<?php
require_once __DIR__ . '/config/mail_config.php';

// Include PHPMailer classes manually
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mail;
    private $config;
    
    public function __construct() {
        $this->config = include(__DIR__ . '/config/mail_config.php');
        $this->mail = new PHPMailer(true);
        
        // Server settings
        // Disable output from PHPMailer to prevent headers already sent errors
        $this->mail->SMTPDebug = 0; // Set to 0 to disable debug output
        $this->mail->Debugoutput = function($str, $level) {
            // Log debug output to error log instead of outputting it
            error_log("PHPMailer ($level): $str");
        };
        $this->mail->isSMTP();
        $this->mail->Host = $this->config['smtp']['host'];
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $this->config['smtp']['username'];
        $this->mail->Password = $this->config['smtp']['password'];
        $this->mail->SMTPSecure = $this->config['smtp']['encryption'];
        $this->mail->Port = $this->config['smtp']['port'];
        
        // Additional settings for Gmail
        $this->mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        $this->mail->CharSet = 'UTF-8';
        
        // Sender info
        $this->mail->setFrom(
            $this->config['smtp']['from_email'],
            $this->config['smtp']['from_name']
        );
        
        $this->mail->isHTML(true);
    }
    
    public function sendContactForm($data) {
        try {
            // Recipients
            $this->mail->addAddress($this->config['smtp']['admin_email']);
            
            // If you want to send a copy to the user
            if (isset($data['email'])) {
                $this->mail->addReplyTo($data['email'], $data['name'] ?? '');
            }
            
            // Content
            $this->mail->Subject = 'New Contact Form Submission: ' . ($data['subject'] ?? 'No Subject');
            
            // Email body in HTML
            $this->mail->Body = $this->getEmailTemplate('contact_admin', $data);
            
            // Plain text version
            $this->mail->AltBody = $this->getPlainTextEmail($data);
            
            $this->mail->send();
            
            // Send confirmation to user if email is provided
            if (!empty($data['email'])) {
                $this->sendConfirmationEmail($data);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }
    
    private function sendConfirmationEmail($data) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($data['email'], $data['name'] ?? '');
            $this->mail->Subject = 'Thank you for contacting us';
            $this->mail->Body = $this->getEmailTemplate('contact_confirmation', $data);
            $this->mail->AltBody = "Thank you for contacting us. We'll get back to you soon!";
            
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Confirmation email could not be sent. Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }
    
    private function getEmailTemplate($template, $data) {
        $templatePath = __DIR__ . "/../emails/{$template}.php";
        
        if (!file_exists($templatePath)) {
            throw new Exception("Email template not found: {$template}");
        }
        
        // Extract variables for the template
        extract($data);
        
        // Start output buffering
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }
    
    private function getPlainTextEmail($data) {
        $message = "You have received a new contact form submission:\n\n";
        $message .= "Name: " . ($data['name'] ?? '') . "\n";
        $message .= "Email: " . ($data['email'] ?? '') . "\n";
        $message .= "Phone: " . ($data['phone'] ?? 'Not provided') . "\n";
        $message .= "Subject: " . ($data['subject'] ?? 'No Subject') . "\n";
        $message .= "Message: " . ($data['message'] ?? '') . "\n";
        
        return $message;
    }
}
