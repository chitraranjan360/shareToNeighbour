<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

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

// Allow reopen from requested OR taken (your choice)
if (!in_array($item['status'], ['requested', 'taken'], true)) {
    setFlash('error', 'Only REQUESTED/TAKEN items can be reopened.');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
}

$conn->begin_transaction();

try {
    // 1) Set item back to available
    $stmt = $conn->prepare("UPDATE furniture_items SET status = 'available' WHERE id = ?");
    $stmt->bind_param('i', $itemId);
    $stmt->execute();
    $stmt->close();

    // 2) Optional: set accepted request -> declined (keeps state consistent)
    $stmt = $conn->prepare("
        UPDATE requests
        SET status = 'declined'
        WHERE item_id = ? AND owner_id = ? AND status = 'accepted'
    ");
    $stmt->bind_param('ii', $itemId, $ownerId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    setFlash('success', 'Item reopened and set to AVAILABLE.');

} catch (Throwable $e) {
    $conn->rollback();
    setFlash('error', 'Failed to reopen item: ' . $e->getMessage());
}

redirect(SITE_URL . '/item.php?id=' . $itemId);