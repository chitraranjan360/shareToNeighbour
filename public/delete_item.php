<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/my_listings.php');
}

$itemId = (int)($_POST['item_id'] ?? 0);
$uid = currentUserId();

if ($itemId <= 0) {
    setFlash('error', 'Invalid item.');
    redirect(SITE_URL . '/my_listings.php');
}

// Verify ownership + get status
$stmt = $conn->prepare("SELECT id, status FROM furniture_items WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param('ii', $itemId, $uid);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    setFlash('error', 'Item not found or not allowed.');
    redirect(SITE_URL . '/my_listings.php');
}

// If taken => soft delete (keeps reviews/history for admin)
if ($item['status'] === 'taken') {
    $stmt = $conn->prepare("UPDATE furniture_items SET is_deleted = 1, deleted_at = NOW() WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $itemId);
    $stmt->execute();
    $stmt->close();

    setFlash('success', 'Listing removed from your view.');
    redirect(SITE_URL . '/my_listings.php');
}

// Otherwise => hard delete (optional; keeps your current behavior)
$stmt = $conn->prepare("DELETE FROM furniture_items WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $itemId);
$stmt->execute();
$stmt->close();

setFlash('success', 'Listing deleted.');
redirect(SITE_URL . '/my_listings.php');