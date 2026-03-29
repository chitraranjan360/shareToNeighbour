<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(ADMIN_URL . '/manage_items.php');
$itemId = (int)($_POST['item_id'] ?? 0);
if ($itemId <= 0) { setFlash('error','Invalid item.'); redirect(ADMIN_URL.'/manage_items.php'); }

$stmt = $conn->prepare("SELECT title, photo FROM furniture_items WHERE id = ?");
$stmt->bind_param('i', $itemId); $stmt->execute();
$item = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$item) { setFlash('error','Item not found.'); redirect(ADMIN_URL.'/manage_items.php'); }

if ($item['photo'] && file_exists(UPLOAD_DIR . $item['photo'])) unlink(UPLOAD_DIR . $item['photo']);

$stmt = $conn->prepare("DELETE FROM furniture_items WHERE id = ?");
$stmt->bind_param('i', $itemId);
if ($stmt->execute()) setFlash('success', '"' . $item['title'] . '" deleted.');
else setFlash('error', 'Delete failed.');
$stmt->close();
redirect(ADMIN_URL . '/manage_items.php');