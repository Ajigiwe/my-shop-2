<?php
// Start output buffering to prevent headers already sent errors
ob_start();

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database and mailer
require_once 'includes/db.php';
require_once 'includes/mailer.php';

// Set content type to JSON
header('Content-Type: application/json');

// Function to sanitize input is included from db.php

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's a newsletter subscription request
    if (isset($_POST['action']) && $_POST['action'] === 'newsletter_subscribe') {
        $email = isset($_POST['email']) ? filter_var(sanitizeInput($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit;
        }
        
        try {
            // Check if already subscribed
            $check_stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
            $check_stmt->execute([$email]);
            if ($check_stmt->fetch()) {
                echo json_encode(['success' => true, 'message' => 'You are already subscribed! Thank you.']);
                exit;
            }
            
            // Insert subscriber
            $ins_stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, is_active) VALUES (?, 1)");
            $ins_stmt->execute([$email]);
            
            echo json_encode(['success' => true, 'message' => 'Thank you for subscribing! Check your email for deals.']);
            exit;
        } catch (PDOException $e) {
            error_log('Newsletter subscription DB error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again later.']);
            exit;
        }
    }

    try {
        // Get and sanitize form data
        $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
        $email = isset($_POST['email']) ? filter_var(sanitizeInput($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
        $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
        $subject = isset($_POST['subject']) ? sanitizeInput($_POST['subject']) : '';
        $message = isset($_POST['message']) ? sanitizeInput($_POST['message']) : '';
        $newsletter = isset($_POST['newsletter']) ? true : false;
        
        // Validate inputs
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = 'Name is required';
        }
        
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        }
        
        if (empty($subject)) {
            $errors['subject'] = 'Subject is required';
        }
        
        if (empty($message)) {
            $errors['message'] = 'Message is required';
        } elseif (strlen($message) < 10) {
            $errors['message'] = 'Message should be at least 10 characters long';
        }
        
        // If there are validation errors
        if (!empty($errors)) {
            $response['errors'] = $errors;
            $response['message'] = 'Please correct the errors below.';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        
        // Prepare data for email
        $emailData = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message,
            'newsletter' => $newsletter,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Save to database (optional)
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_submissions 
                (name, email, phone, subject, message, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->execute([
                $emailData['name'],
                $emailData['email'],
                $emailData['phone'],
                $emailData['subject'],
                $emailData['message'],
                $emailData['ip_address'],
                $emailData['user_agent']
            ]);
            
            // Send email
            $mailer = new Mailer();
            $mailSent = $mailer->sendContactForm([
                'name' => $emailData['name'],
                'email' => $emailData['email'],
                'phone' => $emailData['phone'],
                'subject' => $emailData['subject'],
                'message' => $emailData['message']
            ]);
            
            if ($mailSent === true) {
                // Set success flag in session
                $_SESSION['form_submitted'] = true;
                
                // Clear any previous output
                if (ob_get_length()) ob_clean();
            
                // Send success response
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Message sent successfully!'
                ]);
                exit;
            } else {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Message could not be sent. Please try again later.'
                ]);
                exit;
            }
            
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while saving your message. Please try again.'
            ]);
            exit;
        }
        
    } catch (Exception $e) {
        error_log("Error processing contact form: " . $e->getMessage());
        $response['message'] = 'An error occurred. Please try again later.';
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// If not a POST request, redirect to contact page
header('Location: contact.php');
exit;
