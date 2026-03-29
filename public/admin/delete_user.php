<?php
/**
 * Admin: Delete a user and all their associated data.
 * ON DELETE CASCADE in the schema handles furniture_items, messages, and requests.
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/manage_users.php');
}

$userId = (int)($_POST['user_id'] ?? 0);

// Safety checks
if ($userId <= 0) {
    setFlash('error', 'Invalid user ID.');
    redirect(SITE_URL . '/admin/manage_users.php');
}

// Cannot delete yourself
if ($userId === currentUserId()) {
    setFlash('error', 'You cannot delete your own admin account.');
    redirect(SITE_URL . '/admin/manage_users.php');
}

// Fetch user for confirmation message
$stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    setFlash('error', 'User not found.');
    redirect(SITE_URL . '/admin/manage_users.php');
}

// Optional: delete their uploaded photos from disk
$stmt = $conn->prepare("SELECT photo FROM furniture_items WHERE user_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$photos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($photos as $p) {
    if ($p['photo'] && file_exists(UPLOAD_DIR . $p['photo'])) {
        unlink(UPLOAD_DIR . $p['photo']);
    }
}

// Delete the user (CASCADE handles related rows)
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);

if ($stmt->execute()) {
    setFlash('success', 'User "' . $user['username'] . '" and all their data have been deleted.');
} else {
    setFlash('error', 'Failed to delete user. Please try again.');
}
$stmt->close();

redirect(SITE_URL . '/admin/manage_users.php');