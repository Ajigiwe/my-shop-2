<?php
/**
 * Admin Notifications API
 * - action=list  : unread notifications + count
 * - action=read  : mark a notification read
 * - action=readall : mark all read
 */
require_once '../../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? 'list';

try {
    if ($action === 'read' || $action === 'readall') {
        if ($action === 'readall') {
            $pdo->exec("UPDATE admin_notifications SET is_read = 1");
        } else {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = ?");
            $stmt->execute([$id]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    $notifications = $pdo->query("SELECT * FROM admin_notifications WHERE is_read = 0 ORDER BY created_at DESC, id DESC LIMIT 20")->fetchAll();
    $unread = (int)$pdo->query("SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0")->fetchColumn();

    echo json_encode([
        'success' => true,
        'unread' => $unread,
        'notifications' => array_map(function($n) {
            $n['created_at'] = date('M j, H:i', strtotime($n['created_at']));
            return $n;
        }, $notifications)
    ]);
} catch (PDOException $e) {
    error_log("Notifications API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'unread' => 0, 'notifications' => []]);
}
