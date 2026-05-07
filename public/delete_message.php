<?php
// Deletes a whole chat conversation for the signed-in user.
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

// Only allow this action from a form submit.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/messages.php?tab=inbox');
}

// Read the current user id and the tab we should return to afterwards.
$uid = currentUserId();
$returnTab = preg_replace('/[^a-z]/', '', $_POST['return_tab'] ?? 'inbox');
if (!in_array($returnTab, ['inbox', 'sent'], true)) {
    $returnTab = 'inbox';
}

// The form may send a message id or the other user's id.
$messageId  = (int)($_POST['message_id']  ?? 0);
$otherUserId = (int)($_POST['other_user_id'] ?? 0);

// If only a message id was sent, find the other person in that chat.
if ($otherUserId <= 0 && $messageId > 0) {
    // Look up the sender and receiver for that message.
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

    // Figure out which user is the other side of the chat.
    $otherUserId = ((int)$msg['sender_id'] === $uid)
        ? (int)$msg['receiver_id']
        : (int)$msg['sender_id'];
}

if ($otherUserId <= 0) {
    setFlash('error', 'Invalid conversation.');
    redirect(SITE_URL . '/messages.php?tab=' . $returnTab);
}

// Delete all messages between these two users.
$stmt = $conn->prepare("
    DELETE FROM messages
    WHERE (sender_id = ? AND receiver_id = ?)
       OR (sender_id = ? AND receiver_id = ?)
");
$stmt->bind_param('iiii', $uid, $otherUserId, $otherUserId, $uid);

if ($stmt->execute()) {
    // Show how many rows were removed.
    $deleted = $stmt->affected_rows;
    setFlash('success', "Chat deleted ({$deleted} messages removed).");
} else {
    setFlash('error', 'Failed to delete conversation.');
}
$stmt->close();

redirect(SITE_URL . '/messages.php?tab=' . $returnTab);