<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(ADMIN_URL . '/manage_users.php');

$userId = (int)($_POST['user_id'] ?? 0);
if ($userId <= 0) { setFlash('error','Invalid user.'); redirect(ADMIN_URL.'/manage_users.php'); }

// Get user info
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param('i', $userId); $stmt->execute();
$user = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$user) { setFlash('error','User not found.'); redirect(ADMIN_URL.'/manage_users.php'); }

// Delete uploaded photos from disk
$stmt = $conn->prepare("SELECT photo FROM furniture_items WHERE user_id = ?");
$stmt->bind_param('i', $userId); $stmt->execute();
$photos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
foreach ($photos as $p) {
    if ($p['photo'] && file_exists(UPLOAD_DIR . $p['photo'])) unlink(UPLOAD_DIR . $p['photo']);
}

// Delete user (CASCADE deletes their items, messages, requests)
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
if ($stmt->execute()) setFlash('success', 'User "' . $user['username'] . '" deleted with all their data.');
else setFlash('error', 'Delete failed.');
$stmt->close();

redirect(ADMIN_URL . '/manage_users.php');