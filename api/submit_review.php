<?php
/**
 * API: Submit Product Review
 */
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$product_id = (int)($_POST['product_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = sanitizeInput($_POST['comment'] ?? '');
$user_id = $_SESSION['user_id'];

if ($product_id <= 0 || $rating < 1 || $rating > 5) {
    header("Location: ../product.php?id=$product_id&error=invalid_input");
    exit();
}

try {
    // Check if user already reviewed this product
    $stmt = $pdo->prepare("SELECT review_id FROM reviews WHERE product_id = ? AND user_id = ?");
    $stmt->execute([$product_id, $user_id]);
    
    if ($stmt->fetch()) {
        // Update existing review
        $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ?, updated_at = CURRENT_TIMESTAMP WHERE product_id = ? AND user_id = ?");
        $stmt->execute([$rating, $comment, $product_id, $user_id]);
    } else {
        // Insert new review
        $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$product_id, $user_id, $rating, $comment]);
    }
    
    header("Location: ../product.php?id=$product_id&success=review_submitted");
} catch(PDOException $e) {
    error_log("Error submitting review: " . $e->getMessage());
    header("Location: ../product.php?id=$product_id&error=db_error");
}
exit();
