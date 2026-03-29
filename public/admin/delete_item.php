<?php
/**
 * Admin: Delete a furniture item.
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/manage_items.php');
}

$itemId = (int)($_POST['item_id'] ?? 0);

if ($itemId <= 0) {
    setFlash('error', 'Invalid item ID.');
    redirect(SITE_URL . '/admin/manage_items.php');
}

// Fetch item to get photo path and title
$stmt = $conn->prepare("SELECT title, photo FROM furniture_items WHERE id = ?");
$stmt->bind_param('i', $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    setFlash('error', 'Item not found.');
    redirect(SITE_URL . '/admin/manage_items.php');
}

// Delete photo file
if ($item['photo'] && file_exists(UPLOAD_DIR . $item['photo'])) {
    unlink(UPLOAD_DIR . $item['photo']);
}

// Delete from database (CASCADE handles requests/messages referencing this item)
$stmt = $conn->prepare("DELETE FROM furniture_items WHERE id = ?");
$stmt->bind_param('i', $itemId);

if ($stmt->execute()) {
    setFlash('success', 'Item "' . $item['title'] . '" has been deleted.');
} else {
    setFlash('error', 'Failed to delete item.');
}
$stmt->close();

redirect(SITE_URL . '/admin/manage_items.php');