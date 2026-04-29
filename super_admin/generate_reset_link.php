<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/super_admin_auth.php';
requireSuperAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/super_admin/dashboard.php');
    exit;
}

$adminId = (int)($_POST['admin_id'] ?? 0);
if ($adminId <= 0) {
    echo '<!doctype html>
    <html lang="en">
    <head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invalid Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    </head>
    <body class="bg-light"><div class="container py-5"><div class="row justify-content-center"><div class="col-md-8 col-lg-6"><div class="alert alert-danger mb-0">Invalid admin.</div></div></div></div>
    </body>
    </html>';
    exit;
}

// invalidate previous unused tokens
$stmt = $conn->prepare("UPDATE admin_recovery_tokens SET used_at = NOW() WHERE admin_id = ? AND used_at IS NULL");
$stmt->bind_param('i', $adminId);
$stmt->execute();
$stmt->close();

$rawToken = bin2hex(random_bytes(32));
$hash = hash('sha256', $rawToken);

$issuer = currentSuperAdminId();
$stmt = $conn->prepare("
    INSERT INTO admin_recovery_tokens (admin_id, token_hash, issued_by_super_admin_id, expires_at)
    VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))
");
$stmt->bind_param('isi', $adminId, $hash, $issuer);
$stmt->execute();
$stmt->close();

superAdminAudit('GENERATE_ADMIN_RESET_LINK', $adminId);

$resetUrl = Super_admin_URL . '/reset_password.php?token=' . urlencode($rawToken);

echo '<!doctype html><html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset Link Generated</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-light">
<div class="container py-5">
<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card border-0 shadow-sm">
<div class="card-body p-4">
<h1 class="h4 mb-3">Reset Link Generated</h1>
<div class="alert alert-success">This reset link is valid for 30 minutes.</div><label class="form-label" for="resetUrl">Reset URL</label><input id="resetUrl" type="text" class="form-control mb-3" readonly value="' . htmlspecialchars($resetUrl) . '"><a class="btn btn-primary" href="' . htmlspecialchars($resetUrl) . '">Open Reset Link</a> <a class="btn btn-outline-secondary ms-2" href="' . Super_admin_URL . '/dashboard.php">Back to Dashboard</a></div></div></div></div></div>
</body></html>';
