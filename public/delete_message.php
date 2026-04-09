<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/messages.php?tab=inbox');
}

$uid = currentUserId();
$messageId = (int)($_POST['message_id'] ?? 0);
$returnTab = preg_replace('/[^a-z]/', '', $_POST['return_tab'] ?? 'inbox');
if (!in_array($returnTab, ['inbox', 'sent'], true)) {
    $returnTab = 'inbox';
}

if ($messageId <= 0) {
    setFlash('error', 'Invalid message.');
    redirect(SITE_URL . '/messages.php?tab=' . $returnTab);
}

// Check ownership (sender or receiver can delete)
$stmt = $conn->prepare("SELECT id, sender_id, receiver_id FROM messages WHERE id = ?");
$stmt->bind_param('i', $messageId);
$stmt->execute();
$msg = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$msg) {
    setFlash('error', 'Message not found.');
    redirect(SITE_URL . '/messages.php?tab=' . $returnTab);
}

if ((int)$msg['sender_id'] !== $uid && (int)$msg['receiver_id'] !== $uid) {
    setFlash('error', 'You are not allowed to delete this message.');
    redirect(SITE_URL . '/messages.php?tab=' . $returnTab);
}

// Delete
$stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
$stmt->bind_param('i', $messageId);
if ($stmt->execute()) {
    setFlash('success', 'Message deleted.');
} else {
    setFlash('error', 'Failed to delete message.');
}
$stmt->close();

redirect(SITE_URL . '/messages.php?tab=' . $returnTab);