<?php
require_once __DIR__ . '/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  redirect(ADMIN_URL . '/manage_users.php');
}

$userId = (int)($_POST['user_id'] ?? 0);
$action = $_POST['action'] ?? '';
$reason = trim($_POST['reason'] ?? '');

if ($userId <= 0) {
  redirect(ADMIN_URL . '/manage_users.php');
}

if ($action === 'disable') {
  if ($reason === '') {
    setFlash('error', 'Reason is required to disable an account.');
    redirect(ADMIN_URL . '/user_details.php?id=' . $userId);
  }

  $isActive = 0;
  $stmt = $conn->prepare("UPDATE users SET is_active = 0, disabled_at = NOW(), disabled_reason = ? WHERE id = ? LIMIT 1");
  $stmt->bind_param('si', $reason, $userId);
  $stmt->execute();
  $stmt->close();

  setFlash('success', 'User disabled successfully.');
  redirect(ADMIN_URL . '/user_details.php?id=' . $userId);
}

if ($action === 'enable') {
  $stmt = $conn->prepare("UPDATE users SET is_active = 1, disabled_at = NULL, disabled_reason = NULL WHERE id = ? LIMIT 1");
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $stmt->close();

  setFlash('success', 'User enabled successfully.');
  redirect(ADMIN_URL . '/user_details.php?id=' . $userId);
}

setFlash('error', 'Invalid action.');
redirect(ADMIN_URL . '/manage_users.php');