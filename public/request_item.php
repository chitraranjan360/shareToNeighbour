<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/browse.php');
}

$itemId  = (int)($_POST['item_id'] ?? 0);
$message = trim($_POST['message'] ?? '');
$uid     = currentUserId();

if ($itemId <= 0) {
    setFlash('error', 'Invalid item.');
    redirect(SITE_URL . '/browse.php');
}

/**
 * 1) Fetch item (IMPORTANT: you were missing this)
 *    Do NOT filter by status in SQL; validate in PHP so you can debug easily.
 */
$stmt = $conn->prepare("SELECT id, user_id, title, status FROM furniture_items WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    setFlash('error', 'Item not found.');
    redirect(SITE_URL . '/browse.php');
}

if ((int)$item['user_id'] === $uid) {
    setFlash('error', 'Cannot request your own item.');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
}

if ($item['status'] !== 'available') {
    setFlash('error', 'Item is unavailable (current status: ' . $item['status'] . ').');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
}

/**
 * 2) Duplicate request prevention:
 *    Only block if pending/accepted exists.
 *    (Remove your old second duplicate-check entirely.)
 */
$stmt = $conn->prepare("
    SELECT id
    FROM requests
    WHERE item_id = ? AND requester_id = ? AND status IN ('pending','accepted')
    LIMIT 1
");
$stmt->bind_param('ii', $itemId, $uid);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    setFlash('error', 'You have already requested this item.');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
}
$stmt->close();

/**
 * 3) Insert request
 */
$ownerId = (int)$item['user_id'];
$stmt = $conn->prepare("INSERT INTO requests (item_id, requester_id, owner_id, message) VALUES (?,?,?,?)");
$stmt->bind_param('iiis', $itemId, $uid, $ownerId, $message);
$stmt->execute();
$requestId = (int)$stmt->insert_id; // NEW
$stmt->close();

/**
 * 3.1) Insert notification row (NEW)
 */
$nType  = 'request';
$nRefId = (int)$requestId;
$nTitle = 'New item request';
$nBody  = 'Someone requested your item: ' . $item['title'];

if ((int)$ownerId > 0 && $nRefId > 0) {
    $nStmt = $conn->prepare("
        INSERT INTO notifications (user_id, type, ref_id, title, body, is_seen)
        VALUES (?, ?, ?, ?, ?, 0)
    ");
    if ($nStmt) {
        $nStmt->bind_param('isiss', $ownerId, $nType, $nRefId, $nTitle, $nBody);
        if (!$nStmt->execute()) {
            error_log('Notification insert failed (request_item.php): ' . $nStmt->error);
        }
        $nStmt->close();
    } else {
        error_log('Notification prepare failed (request_item.php): ' . $conn->error);
    }
}
/**
 * 4) Send notification message to owner
 */
$subj = 'Item Request: ' . $item['title'];
$body = currentUserName() . ' wants your item "' . $item['title'] . '".'
      . ($message !== '' ? "\n\nMessage: " . $message : '');

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, item_id, subject, body) VALUES (?,?,?,?,?)");
$stmt->bind_param('iiiss', $uid, $ownerId, $itemId, $subj, $body);
$stmt->execute();
$stmt->close();

setFlash('success', 'Request sent to the owner!');


// 5) Send email notification to owner (if enabled and owner has opted in)
$ownerInfo = getUserInfo($conn, $ownerId);
$requesterInfo = getUserInfo($conn, $uid);

if ($ownerInfo && $requesterInfo) {
    $emailSubject = "New item request: " . $item['title'];
    $emailBody = "Hello " . $ownerInfo['name'] . ",\n\n"
        . "You received a new request on ShareToNeighbour.\n\n"
        . "Item: " . $item['title'] . "\n"
        . "Requester: @" . $requesterInfo['username'] . "\n\n"
        . "Login to accept/decline:\n"
        . SITE_URL . "/messages.php?tab=requests\n\n"
        . "- ShareToNeighbour";

    sendEmailToUser($conn, $ownerId, $emailSubject, $emailBody);
}
redirect(SITE_URL . '/item.php?id=' . $itemId);