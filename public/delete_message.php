<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/messages.php?tab=inbox');
}

$uid = currentUserId();
$returnTab = preg_replace('/[^a-z]/', '', $_POST['return_tab'] ?? 'inbox');
if (!in_array($returnTab, ['inbox', 'sent'], true)) {
    $returnTab = 'inbox';
}

// Accept either a single message_id OR a direct other_user_id
$messageId  = (int)($_POST['message_id']  ?? 0);
$otherUserId = (int)($_POST['other_user_id'] ?? 0);

// If we only got a message_id, resolve the other participant from that message
if ($otherUserId <= 0 && $messageId > 0) {
    $stmt = $conn->prepare("SELECT sender_id, receiver_id FROM messages WHERE id = ?");
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    $msg = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$msg) {
        setFlash('error', 'Message not found.');
        redirect(SITE_URL . '/messages.php?tab=' . $returnTab);
    }

    if ((int)$msg['sender_id'] !== $uid && (int)$msg['receiver_id'] !== $uid) {
        setFlash('error', 'You are not allowed to delete this conversation.');
        redirect(SITE_URL . '/messages.php?tab=' . $returnTab);
    }

    // Identify the other party
    $otherUserId = ((int)$msg['sender_id'] === $uid)
        ? (int)$msg['receiver_id']
        : (int)$msg['sender_id'];
}

if ($otherUserId <= 0) {
    setFlash('error', 'Invalid conversation.');
    redirect(SITE_URL . '/messages.php?tab=' . $returnTab);
}

// Delete every message in the conversation between $uid and $otherUserId
$stmt = $conn->prepare("
    DELETE FROM messages
    WHERE (sender_id = ? AND receiver_id = ?)
       OR (sender_id = ? AND receiver_id = ?)
");
$stmt->bind_param('iiii', $uid, $otherUserId, $otherUserId, $uid);

if ($stmt->execute()) {
    $deleted = $stmt->affected_rows;
    setFlash('success', "Chat deleted ({$deleted} messages removed).");
} else {
    setFlash('error', 'Failed to delete conversation.');
}
$stmt->close();

redirect(SITE_URL . '/messages.php?tab=' . $returnTab);