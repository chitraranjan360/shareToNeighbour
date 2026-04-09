<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/profile.php');
}

$itemId  = (int)($_POST['item_id'] ?? 0);
$newStatus = $_POST['status'] ?? '';
$ownerId = currentUserId();

if ($itemId <= 0 || !in_array($newStatus, ['available','requested','taken'], true)) {
    setFlash('error', 'Invalid status update.');
    redirect(SITE_URL . '/profile.php');
}

// Verify owner
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
    setFlash('error', 'You cannot update this item.');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
}

/**
 * ✅ LOCK RULE:
 * Once status is TAKEN, it becomes final and cannot be changed again.
 */
if (($item['status'] ?? '') === 'taken') {
    setFlash('error', 'This item is already marked as TAKEN and cannot be changed again.');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
    ?> 
    <button></button>
    <?php
}

$conn->begin_transaction();

try {
    // If owner sets to AVAILABLE: decline accepted requests (so system is consistent)
    if ($newStatus === 'available') {

        // Decline all pending/accepted requests because item is reopened
        $stmt = $conn->prepare("
            UPDATE requests
            SET status = 'declined'
            WHERE item_id = ? AND owner_id = ? AND status IN ('pending','accepted')
        ");
        $stmt->bind_param('ii', $itemId, $ownerId);
        $stmt->execute();
        $stmt->close();

        // Notify all affected requesters
        $stmt = $conn->prepare("
            SELECT requester_id
            FROM requests
            WHERE item_id = ? AND owner_id = ? AND status = 'declined'
        ");
        $stmt->bind_param('ii', $itemId, $ownerId);
        $stmt->execute();
        $reqUsers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($reqUsers as $ru) {
            $subject = 'Item reopened: ' . $item['title'];
            $body    = "Hello,\n\nThe owner reopened the item \"" . $item['title'] . "\" and set it back to AVAILABLE.\n"
                     . "You may request it again if you still want it.";

            $stmt = $conn->prepare("
                INSERT INTO messages (sender_id, receiver_id, item_id, subject, body)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('iiiss', $ownerId, $ru['requester_id'], $itemId, $subject, $body);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Update item status
    $stmt = $conn->prepare("UPDATE furniture_items SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $newStatus, $itemId);
    $stmt->execute();
    $stmt->close();

    // If owner sets to TAKEN: notify the accepted requester (if any)
    if ($newStatus === 'taken') {
        $stmt = $conn->prepare("
            SELECT requester_id
            FROM requests
            WHERE item_id = ? AND owner_id = ? AND status = 'accepted'
            ORDER BY updated_at DESC
            LIMIT 1
        ");
        $stmt->bind_param('ii', $itemId, $ownerId);
        $stmt->execute();
        $acc = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($acc) {
            $subject = 'Item marked as taken: ' . $item['title'];
            $body = "Hello,\n\nThe owner marked \"" . $item['title'] . "\" as TAKEN.\n\nThanks for using ShareToNeighbour!";

            $stmt = $conn->prepare("
                INSERT INTO messages (sender_id, receiver_id, item_id, subject, body)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('iiiss', $ownerId, $acc['requester_id'], $itemId, $subject, $body);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conn->commit();
    setFlash('success', 'Item status updated to ' . strtoupper($newStatus) . '.');

} catch (Throwable $e) {
    $conn->rollback();
    setFlash('error', 'Failed to update status: ' . $e->getMessage());
}

redirect(SITE_URL . '/item.php?id=' . $itemId);