<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireUserLogin();
header('Content-Type: application/json');

$uid = currentUserId();
$withUserId = (int)($_POST['with_user_id'] ?? 0);

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

echo json_encode(['ok' => $ok]);