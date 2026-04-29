<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (isAdminLoggedIn()) redirect(ADMIN_URL . '/dashboard.php');

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Please fill in both fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, full_name FROM admins WHERE username = ?");
        $stmt->bind_param('s', $username); $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc(); $stmt->close();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']       = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name']     = $admin['full_name'];
            $_SESSION['user_type']      = 'admin';

            redirect(ADMIN_URL . '/dashboard.php');
        } else {
            $errors[] = 'Invalid admin credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/admin.css">
</head>
<body class="admin-auth-page d-flex align-items-center min-vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-warning-subtle text-dark text-center py-4 border-0">
                    <h4 class="mb-1"><i class="bi bi-shield-lock-fill me-2"></i>Admin Login</h4>
                    <small class="text-body-secondary"><?= SITE_NAME ?> Administration</small>
                </div>
                <div class="card-body p-4">

                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-0 shadow-sm"><ul class="mb-0">
                        <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                    </ul></div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Admin Username</label>
                            <input type="text" class="form-control form-control-lg" name="username" value="<?= h($username) ?>" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control form-control-lg" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 fw-semibold py-2">
                            <i class="bi bi-shield-check"></i>Login
                        </button>
                    </form>

                    <hr class="my-4">
                    <p class="text-center small text-body-secondary mb-0">
                        <a href="<?= SITE_URL ?>/index.php">← Back to main site</a>
                    </p>    
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>