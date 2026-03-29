<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php'; // ✅ Step 8 email alert

requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/profile.php');
}

$itemId  = (int)($_POST['item_id'] ?? 0);
$ownerId = currentUserId();

if ($itemId <= 0) {
    setFlash('error', 'Invalid item.');
    redirect(SITE_URL . '/profile.php');
}

// Load item and verify ownership
$stmt = $conn->prepare("SELECT id, user_id, title, status FROM furniture_items WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    setFlash('error', 'Item not found.');
    redirect(SITE_URL . '/profile.php');
}

if ((int)$item['user_id'] !== $ownerId) {
    setFlash('error', 'You do not have permission to update this item.');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
}

if ($item['status'] !== 'requested') {
    setFlash('error', 'Only REQUESTED items can be marked as TAKEN.');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
}

$conn->begin_transaction();

try {
    // 1) Mark item as taken
    $stmt = $conn->prepare("UPDATE furniture_items SET status = 'taken' WHERE id = ?");
    $stmt->bind_param('i', $itemId);
    $stmt->execute();
    $stmt->close();

    // 2) Find the accepted request (notify that requester)
    $stmt = $conn->prepare("
        SELECT requester_id
        FROM requests
        WHERE item_id = ? AND owner_id = ? AND status = 'accepted'
        ORDER BY updated_at DESC
        LIMIT 1
    ");
    $stmt->bind_param('ii', $itemId, $ownerId);
    $stmt->execute();
    $accepted = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($accepted) {
        $requesterId = (int)$accepted['requester_id'];

        // In-app message
        $subject = 'Item marked as taken: ' . $item['title'];
        $body    = "Hello,\n\nThe owner has marked \"" . $item['title'] . "\" as TAKEN.\n\n"
                 . "Thanks for using ShareToNeighbour and supporting reuse!";

        $stmt = $conn->prepare("
            INSERT INTO messages (sender_id, receiver_id, item_id, subject, body)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iiiss', $ownerId, $requesterId, $itemId, $subject, $body);
        $stmt->execute();
        $stmt->close();

        // ✅ Step 8: Email notification
        $emailSubject = "Item marked as taken: " . $item['title'];
        $emailBody = "Hello,\n\n"
            . "The owner has marked \"" . $item['title'] . "\" as TAKEN.\n\n"
            . "You can view messages here:\n"
            . SITE_URL . "/messages.php?tab=inbox\n\n"
            . "- ShareToNeighbour";

        sendEmailToUser($conn, $requesterId, $emailSubject, $emailBody);
    }

    $conn->commit();
    setFlash('success', 'Item marked as TAKEN.');

} catch (Throwable $e) {
    $conn->rollback();
    setFlash('error', 'Failed to mark as taken: ' . $e->getMessage());
}

redirect(SITE_URL . '/item.php?id=' . $itemId);