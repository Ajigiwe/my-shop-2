<?php
/**
 * Contact Form AJAX Handler
 * Processes contact form submissions via AJAX
 */

header('Content-Type: application/json');

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $newsletter = isset($_POST['newsletter']);

    // Validate required fields
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Name is required']);
        exit();
    }

    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit();
    }

    if (empty($subject)) {
        echo json_encode(['success' => false, 'message' => 'Subject is required']);
        exit();
    }

    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit();
    }

    // Basic email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit();
    }

    // Basic phone validation (if provided)
    if (!empty($phone) && !preg_match('/^\+?[\d\s\-\(\)]{10,}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number']);
        exit();
    }

    // Database connection (using mysqli like the rest of the app)
    $conn = mysqli_connect('localhost', 'root', '', 'ecommerce_db');

    if (!$conn) {
        error_log('Contact form: Database connection failed');
        echo json_encode(['success' => false, 'message' => 'Service temporarily unavailable. Please try again later.']);
        exit();
    }

    mysqli_set_charset($conn, 'utf8mb4');

    // Insert contact message
    $query = "INSERT INTO contact_messages (name, email, phone, subject, message, newsletter_subscribe, created_at)
              VALUES (?, ?, ?, ?, ?, ?, NOW())";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'sssssi', $name, $email, $phone, $subject, $message, $newsletter);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        // If user wants newsletter, add to newsletter table
        if ($newsletter) {
            $newsletter_query = "INSERT IGNORE INTO newsletter_subscribers (email, name, subscribed_at)
                               VALUES (?, ?, NOW())";
            $newsletter_stmt = mysqli_prepare($conn, $newsletter_query);
            mysqli_stmt_bind_param($newsletter_stmt, 'ss', $email, $name);
            mysqli_stmt_execute($newsletter_stmt);
            mysqli_stmt_close($newsletter_stmt);
        }

        mysqli_close($conn);

        // Log success
        error_log("Contact form submitted successfully: $name ($email)");

        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your message! We\'ll get back to you within 24 hours.'
        ]);
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        error_log('Contact form database error: ' . mysqli_error($conn));
        echo json_encode(['success' => false, 'message' => 'Error saving message. Please try again.']);
    }

} catch (Exception $e) {
    error_log('Contact form exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
}
?>
