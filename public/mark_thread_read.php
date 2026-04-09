<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

$uid = currentUserId();
$other = (int)($_GET['user'] ?? 0);
if ($other > 0) {
    $stmt = $conn->prepare("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=? AND is_read=0");
    $stmt->bind_param('ii', $other, $uid);
    $stmt->execute();
    $stmt->close();
}
http_response_code(204);