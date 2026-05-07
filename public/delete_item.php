<?php
// Removes a listing that belongs to the signed-in user.
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

// Only allow deletion through a form submit.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/my_listings.php');
}

// Read the listing id from the form and the current user id from the session.
$itemId = (int)($_POST['item_id'] ?? 0);
$uid = currentUserId();

if ($itemId <= 0) {
    setFlash('error', 'Invalid item.');
    redirect(SITE_URL . '/my_listings.php');
}

// Check that the listing belongs to this user and get its current status.
$stmt = $conn->prepare("SELECT id, status FROM furniture_items WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param('ii', $itemId, $uid);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    setFlash('error', 'Item not found or not allowed.');
    redirect(SITE_URL . '/my_listings.php');
}

// Keep taken items in the database, but hide them from the owner.
if ($item['status'] === 'taken') {
    $stmt = $conn->prepare("UPDATE furniture_items SET is_deleted = 1, deleted_at = NOW() WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $itemId);
    $stmt->execute();
    $stmt->close();

    setFlash('success', 'Listing removed from your view.');
    redirect(SITE_URL . '/my_listings.php');
}

// Delete the listing completely when it is not marked as taken.
$stmt = $conn->prepare("DELETE FROM furniture_items WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $itemId);
$stmt->execute();
$stmt->close();

setFlash('success', 'Listing deleted.');
redirect(SITE_URL . '/my_listings.php');