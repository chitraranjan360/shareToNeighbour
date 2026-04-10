<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

header('Content-Type: application/json');
$uid = currentUserId();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $stmt = $conn->prepare("
        SELECT id, type, ref_id, title, body, is_seen, created_at
        FROM notifications
        WHERE user_id=?
        ORDER BY created_at DESC
        LIMIT 30
    ");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['ok' => true, 'data' => $rows]);
    exit;
}

if ($action === 'mark_seen') {
    $stmt = $conn->prepare("UPDATE notifications SET is_seen=1 WHERE user_id=? AND is_seen=0");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Invalid action']);