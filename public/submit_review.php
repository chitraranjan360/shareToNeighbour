<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/browse.php');
}

$uid = currentUserId();

$itemId     = (int)($_POST['item_id'] ?? 0);
$requestId  = (int)($_POST['request_id'] ?? 0);
$revieweeId = (int)($_POST['reviewee_id'] ?? 0);
$rating     = (int)($_POST['rating'] ?? 0);
$comment    = trim($_POST['comment'] ?? '');

if ($itemId <= 0 || $requestId <= 0 || $revieweeId <= 0) {
    setFlash('error', 'Invalid review request.');
    redirect(SITE_URL . '/browse.php');
}

if ($rating < 1 || $rating > 5) {
    setFlash('error', 'Please select a rating between 1 and 5.');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
}

// Ensure item is taken
$stmt = $conn->prepare("SELECT id, user_id, status FROM furniture_items WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item || $item['status'] !== 'taken') {
    setFlash('error', 'Review is only allowed after item is marked as TAKEN.');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
}

// Ensure reviewee is the owner
if ((int)$item['user_id'] !== $revieweeId) {
    setFlash('error', 'Invalid review target.');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
}

// Ensure this request belongs to this item, this owner, and this reviewer is the requester AND accepted
$stmt = $conn->prepare("
    SELECT id
    FROM requests
    WHERE id = ? AND item_id = ? AND owner_id = ? AND requester_id = ? AND status = 'accepted'
    LIMIT 1
");
$stmt->bind_param('iiii', $requestId, $itemId, $revieweeId, $uid);
$stmt->execute();
$ok = $stmt->get_result()->num_rows > 0;
$stmt->close();

if (!$ok) {
    setFlash('error', 'You are not allowed to review this transaction.');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
}

// Insert review (unique constraint prevents duplicates)
$stmt = $conn->prepare("
    INSERT INTO reviews (item_id, request_id, reviewer_id, reviewee_id, rating, comment)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('iiiiis', $itemId, $requestId, $uid, $revieweeId, $rating, $comment);

if ($stmt->execute()) {
    setFlash('success', 'Thanks! Your review was submitted.');
} else {
    // If duplicate key, treat as already reviewed
    setFlash('success', 'Review already submitted.');
}
$stmt->close();

redirect(SITE_URL . '/item.php?id=' . $itemId);