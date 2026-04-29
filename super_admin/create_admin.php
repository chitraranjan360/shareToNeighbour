<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/super_admin_auth.php';
requireSuperAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . Super_admin_URL . '/dashboard.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$fullName = trim($_POST['full_name'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $fullName === '' || strlen($password) < 8) {
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Invalid Input</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"></head><body class="bg-light"><div class="container py-5"><div class="row justify-content-center"><div class="col-md-8 col-lg-6"><div class="alert alert-danger">Invalid input. Please ensure all fields are valid and password is at least 8 characters.</div><a class="btn btn-primary" href="' . Super_admin_URL . '/dashboard.php">Back to Dashboard</a></div></div></div></body></html>';
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$registrationKey = null;

$stmt = $conn->prepare("INSERT INTO admins (username, password, full_name, registration_key, is_active) VALUES (?, ?, ?, ?, 1)");
$stmt->bind_param('ssss', $username, $hash, $fullName, $registrationKey);
$stmt->execute();
$newAdminId = (int)$stmt->insert_id;
$stmt->close();

superAdminAudit('CREATE_ADMIN', $newAdminId, ['username' => $username]);

header('Location: ' . Super_admin_URL . '/dashboard.php');
exit;