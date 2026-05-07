<?php
// Saves the chat partner the user is currently viewing.
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireUserLogin();
// Tell the browser this response is JSON.
header('Content-Type: application/json');

// Get the current logged-in user id.
$uid = currentUserId();
// Get the user id of the chat partner being viewed.
$withUserId = (int)($_POST['with_user_id'] ?? 0);

// Reject empty or self-targeted requests.
if ($withUserId <= 0 || $withUserId === $uid) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid user']);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO user_chat_presence (user_id, with_user_id, updated_at)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE
      with_user_id = VALUES(with_user_id),
      updated_at = NOW()
");
$stmt->bind_param('ii', $uid, $withUserId);
$ok = $stmt->execute();
$stmt->close();

// Return success or failure to the client.
echo json_encode(['ok' => $ok]);