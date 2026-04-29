<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/super_admin_auth.php';
requireSuperAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . Super_admin_URL . '/dashboard.php');
    exit;
}

$adminId = (int)($_POST['admin_id'] ?? 0);
$setActive = (int)($_POST['set_active'] ?? 0);
if ($adminId <= 0 || !in_array($setActive, [0,1], true)) {
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Invalid Input</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"></head><body class="bg-light"><div class="container py-5"><div class="row justify-content-center"><div class="col-md-8 col-lg-6"><div class="alert alert-danger">Invalid input for admin status update.</div><a class="btn btn-primary" href="' . Super_admin_URL . '/dashboard.php">Back to Dashboard</a></div></div></div></body></html>';
    exit;
}

$stmt = $conn->prepare("UPDATE admins SET is_active = ? WHERE id = ? LIMIT 1");
$stmt->bind_param('ii', $setActive, $adminId);
$stmt->execute();
$stmt->close();

superAdminAudit($setActive ? 'ENABLE_ADMIN' : 'DISABLE_ADMIN', $adminId);

header('Location: ' . Super_admin_URL . '/dashboard.php');
exit;