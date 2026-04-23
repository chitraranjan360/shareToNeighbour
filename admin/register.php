<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// If already logged in as admin, go to dashboard
if (isAdminLoggedIn()) {
    redirect(ADMIN_URL . '/dashboard.php');
}

$errors = [];
$username = $full_name = '';
$registration_key = '';

// Change this value (or store it in config.php)
define('ADMIN_REGISTRATION_KEY', '360');

function adminCount(mysqli $conn): int {
    $res = $conn->query("SELECT COUNT(*) AS c FROM admins WHERE is_active = 1");
    $row = $res ? $res->fetch_assoc() : ['c' => 0];
    return (int)($row['c'] ?? 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $registration_key = trim($_POST['registration_key'] ?? '');

    // 1) Enforce max 5 admins
    if (adminCount($conn) >= 5) {
        $errors[] = 'Admin limit reached (max 5 admins). Please contact the platform owner.';
    }

    // 2) Check registration key
    if ($registration_key !== ADMIN_REGISTRATION_KEY) {
        $errors[] = 'Invalid admin registration key.';
    }

    // 3) Basic validation
    if (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
    if (strlen($full_name) < 2) $errors[] = 'Full name is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    // 4) Uniqueness
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($exists) {
            $errors[] = 'Admin username already exists.';
        }
    }

    // 5) Create admin
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO admins (username, password, full_name, registration_key, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param('ssss', $username, $hash, $full_name, $registration_key);

        if ($stmt->execute()) {
            setFlash('success', 'Admin account created. Please login.');
            redirect(ADMIN_URL . '/login.php');
        } else {
            $errors[] = 'Failed to create admin account. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register — <?= h(SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/admin.css">
</head>
<body class="admin-auth-page d-flex align-items-center min-vh-100 py-4">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6 col-xl-5">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-warning-subtle text-dark text-center py-4 border-0">
                    <h4 class="mb-1"><i class="bi bi-shield-lock-fill me-2"></i>Admin Registration</h4>
                    <small class="text-body-secondary">Maximum 5 admins allowed</small>
                </div>

                <div class="card-body p-4">

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger border-0 shadow-sm">
                            <ul class="mb-0">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= h($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-info small border-0 shadow-sm">
                        Active admins: <strong><?= adminCount($conn) ?></strong> / 5
                    </div>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Admin Registration Key</label>
                            <input type="text" class="form-control form-control-lg" name="registration_key" value="<?= h($registration_key) ?>" required>
                            <div class="form-text">Only approved admins should have this key.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Admin Username</label>
                            <input type="text" class="form-control form-control-lg" name="username" value="<?= h($username) ?>" required minlength="3">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control form-control-lg" name="full_name" value="<?= h($full_name) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control form-control-lg" name="password" required minlength="6">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control form-control-lg" name="confirm_password" required>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 fw-semibold py-2">
                            <i class="bi bi-person-plus-fill"></i> Create Admin Account
                        </button>
                    </form>

                    <hr class="my-4">
                    <p class="text-center small text-body-secondary mb-0">
                        Already an admin? <a href="<?= ADMIN_URL ?>/login.php" class="text-warning">Admin Login</a><br>
                        Back to site: <a href="<?= SITE_URL ?>/index.php" class="text-muted">Homepage</a>
                    </p>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>