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

// Verify ownership
$stmt = $conn->prepare("SELECT id FROM furniture_items WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param('ii', $itemId, $uid);
$stmt->execute();
$ok = $stmt->get_result()->num_rows > 0;
$stmt->close();

if (!$ok) {
    setFlash('error', 'Item not found or not allowed.');
    redirect(SITE_URL . '/my_listings.php');
}

// Delete item
$stmt = $conn->prepare("DELETE FROM furniture_items WHERE id = ?");
$stmt->bind_param('i', $itemId);
$stmt->execute();
$stmt->close();

setFlash('success', 'Listing deleted.');
redirect(SITE_URL . '/my_listings.php');