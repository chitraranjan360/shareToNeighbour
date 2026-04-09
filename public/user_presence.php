<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isUserLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = (int)($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid user_id']);
    exit;
}

$stmt = $conn->prepare("SELECT is_online, last_seen FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

echo json_encode([
    'user_id' => $userId,
    'is_online' => (int)$row['is_online'],
    'last_seen' => $row['last_seen']
]);