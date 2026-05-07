<?php
require_once __DIR__ . '/../includes/config.php';

require_once __DIR__ . '/../includes/auth.php';
// Stop here if the user is not logged in.
requireUserLogin();

// Tell the browser this endpoint returns JSON.
header('Content-Type: application/json');
// Get the current user id from the session.
$uid = currentUserId();
// Read the requested action, or use "list" by default.
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    // Get the latest 30 notifications for this user.
    $stmt = $conn->prepare("
        SELECT id, type, ref_id, title, body, is_seen, created_at
        FROM notifications
        WHERE user_id=?
        ORDER BY created_at DESC
        LIMIT 30
    ");
    // Bind the current user id to the query.
    $stmt->bind_param('i', $uid);
    // Run the query.
    $stmt->execute();
    // Read all rows into an array.
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    // Close the database statement.
    $stmt->close();

    // Add extra link data for message notifications.
    foreach ($rows as &$r) {
        // Default values for non-message notifications.
        $r['target_url'] = '';
        $r['from_user_id'] = null;

        // Only message notifications need the chat target.
        if (($r['type'] ?? '') === 'message' && !empty($r['ref_id'])) {
            // Get the message row linked to this notification.
            $mid = (int)$r['ref_id'];
            $mStmt = $conn->prepare("SELECT sender_id, receiver_id FROM messages WHERE id = ? LIMIT 1");
            // Use the message id in the lookup.
            $mStmt->bind_param('i', $mid);
            // Run the lookup.
            $mStmt->execute();
            // Read the message row.
            $m = $mStmt->get_result()->fetch_assoc();
            // Close the message query.
            $mStmt->close();

            if ($m) {
                // Find who sent the message and who received it.
                $senderId = (int)$m['sender_id'];
                $receiverId = (int)$m['receiver_id'];

                // Point the notification to the right chat thread.
                if ($uid === $receiverId) {
                    $r['from_user_id'] = $senderId;
                    $r['target_url'] = SITE_URL . '/chat_thread.php?user=' . $senderId;
                } else {
                    $r['from_user_id'] = $receiverId;
                    $r['target_url'] = SITE_URL . '/chat_thread.php?user=' . $receiverId;
                }
            } else {
                // Fall back to the general messages page if the message no longer exists.
                $r['target_url'] = SITE_URL . '/messages.php';
            }
        }
    }
    // Break the reference so later code is not affected.
    unset($r);

    // Send the notification list back as JSON.
    echo json_encode(['ok' => true, 'data' => $rows]);
    exit;
}

if ($action === 'mark_seen') {
    // Mark all unseen notifications for this user as seen.
    $stmt = $conn->prepare("UPDATE notifications SET is_seen=1 WHERE user_id=? AND is_seen=0");
    // Bind the current user id.
    $stmt->bind_param('i', $uid);
    // Run the update.
    $stmt->execute();
    // Close the statement.
    $stmt->close();

    // Confirm success with JSON.
    echo json_encode(['ok' => true]);
    exit;
}

// Return an error if the action name is not known.
echo json_encode(['ok' => false, 'error' => 'Invalid action']);