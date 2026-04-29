<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/super_admin_auth.php';

if (superAdminIsLoggedIn()) {
    header('Location: ' . Super_admin_URL . '/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, username, password, is_active FROM super_admins WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && (int)$row['is_active'] === 1 && password_verify($password, $row['password'])) {
        $_SESSION['super_admin_id'] = (int)$row['id'];
        $_SESSION['super_admin_username'] = $row['username'];

        $stmt = $conn->prepare("UPDATE super_admins SET last_login_at = NOW() WHERE id = ? LIMIT 1");
        $sid = (int)$row['id'];
        $stmt->bind_param('i', $sid);
        $stmt->execute();
        $stmt->close();

        header('Location: ' . Super_admin_URL . '/dashboard.php');
        exit;
    }

    $error = 'Invalid credentials.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Super Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/admin.css">
</head>
<body class="admin-auth-page d-flex align-items-center min-vh-100">
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h4 mb-2 text-center">Super Admin Login</h1>
                    <p class="text-secondary text-center mb-4">Sign in to manage administrators</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="post" class="vstack gap-3">
                        <div>
                            <label for="username" class="form-label">Username</label>
                            <input id="username" class="form-control" name="username" placeholder="Enter username" required>
                        </div>
                        <div>
                            <label for="password" class="form-label">Password</label>
                            <input id="password" class="form-control" name="password" type="password" placeholder="Enter password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>