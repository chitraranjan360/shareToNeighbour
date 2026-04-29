<?php
require_once __DIR__ . '/../includes/config.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
if ($token === '') {
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Invalid Token</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"></head><body class="bg-light"><div class="container py-5"><div class="row justify-content-center"><div class="col-md-8 col-lg-6"><div class="alert alert-danger mb-0">Invalid token.</div></div></div></div></body></html>';
    exit;
}

$tokenHash = hash('sha256', $token);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Password Too Short</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"></head><body class="bg-light"><div class="container py-5"><div class="row justify-content-center"><div class="col-md-8 col-lg-6"><div class="alert alert-danger mb-0">Password too short.</div></div></div></div></body></html>';
        exit;
    }
    if ($password !== $confirm) {
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Password Mismatch</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"></head><body class="bg-light"><div class="container py-5"><div class="row justify-content-center"><div class="col-md-8 col-lg-6"><div class="alert alert-danger mb-0">Passwords do not match.</div></div></div></div></body></html>';
        exit;
    }

    $stmt = $conn->prepare("
        SELECT id, admin_id
        FROM admin_recovery_tokens
        WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Token Expired</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"></head><body class="bg-light"><div class="container py-5"><div class="row justify-content-center"><div class="col-md-8 col-lg-6"><div class="alert alert-danger mb-0">Token invalid or expired.</div></div></div></div></body></html>';
        exit;
    }

    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $adminId = (int)$row['admin_id'];

    $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ? LIMIT 1");
    $stmt->bind_param('si', $newHash, $adminId);
    $stmt->execute();
    $stmt->close();

    $tokenId = (int)$row['id'];
    $stmt = $conn->prepare("UPDATE admin_recovery_tokens SET used_at = NOW() WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $tokenId);
    $stmt->execute();
    $stmt->close();

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Password Reset Successful</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"></head><body class="bg-light"><div class="container py-5"><div class="row justify-content-center"><div class="col-md-8 col-lg-6"><div class="card border-0 shadow-sm"><div class="card-body p-4 text-center"><h1 class="h4 mb-3">Password Updated</h1><div class="alert alert-success mb-0">Password reset successful. You can now login.</div></div></div></div></div></div></body></html>';
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h4 mb-3">Reset Admin Password</h1>
                    <p class="text-secondary mb-4">Create a new secure password for the admin account.</p>
                    <form method="post" class="vstack gap-3">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <div>
                            <label for="password" class="form-label">New password</label>
                            <input id="password" class="form-control" type="password" name="password" placeholder="Minimum 8 characters" required minlength="8">
                        </div>
                        <div>
                            <label for="confirm_password" class="form-label">Confirm password</label>
                            <input id="confirm_password" class="form-control" type="password" name="confirm_password" placeholder="Re-enter password" required minlength="8">
                        </div>
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>