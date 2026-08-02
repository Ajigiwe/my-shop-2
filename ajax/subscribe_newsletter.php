<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

try {
    $check_stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
    $check_stmt->execute([$email]);
    if ($check_stmt->fetch()) {
        echo json_encode(['success' => true, 'message' => 'You are already subscribed! Thank you.']);
        exit;
    }

    $ins_stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, is_active) VALUES (?, 1)");
    $ins_stmt->execute([$email]);

    createAdminNotification('newsletter', "New newsletter subscriber: {$email}", '../admin/newsletter.php');

    echo json_encode(['success' => true, 'message' => 'Thank you for subscribing! Check your email for deals.']);
} catch (PDOException $e) {
    error_log('Newsletter subscription DB error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again later.']);
}
