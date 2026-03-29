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
</head>
<body class="bg-dark d-flex align-items-center min-vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-lg border-warning">
                <div class="card-header bg-warning text-dark text-center py-3">
                    <h4 class="mb-0"><i class="bi bi-shield-lock-fill"></i> Admin Login</h4>
                    <small><?= SITE_NAME ?> Administration</small>
                </div>
                <div class="card-body p-4">

                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger"><ul class="mb-0">
                        <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                    </ul></div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Admin Username</label>
                            <input type="text" class="form-control" name="username" value="<?= h($username) ?>" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 fw-bold">
                            <i class="bi bi-shield-check"></i> Admin Login
                        </button>
                    </form>

                    <hr>
                    <p class="text-center small text-muted mb-0">
                        <a href="<?= SITE_URL ?>/index.php">← Back to main site</a>
                    </p>
                    <hr>
                    <p class="text-center small text-muted mb-0">
                       <a href="register.php">Register as Admin</a> 
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>