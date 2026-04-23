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

    // attach target_url + from_user_id for message notifications
    foreach ($rows as &$r) {
        $r['target_url'] = '';
        $r['from_user_id'] = null;

        if (($r['type'] ?? '') === 'message' && !empty($r['ref_id'])) {
            $mid = (int)$r['ref_id'];
            $mStmt = $conn->prepare("SELECT sender_id, receiver_id FROM messages WHERE id = ? LIMIT 1");
            $mStmt->bind_param('i', $mid);
            $mStmt->execute();
            $m = $mStmt->get_result()->fetch_assoc();
            $mStmt->close();

            if ($m) {
                $senderId = (int)$m['sender_id'];
                $receiverId = (int)$m['receiver_id'];

                if ($uid === $receiverId) {
                    $r['from_user_id'] = $senderId;
                    $r['target_url'] = SITE_URL . '/chat_thread.php?user=' . $senderId;
                } else {
                    $r['from_user_id'] = $receiverId;
                    $r['target_url'] = SITE_URL . '/chat_thread.php?user=' . $receiverId;
                }
            } else {
                $r['target_url'] = SITE_URL . '/messages.php';
            }
        }
    }
    unset($r);

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