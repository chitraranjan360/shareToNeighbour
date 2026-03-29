<?php
$pageTitle = 'Login — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (isUserLoggedIn()) redirect(SITE_URL . '/index.php');

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Please fill in both fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['user_type'] = 'user';

            setFlash('success', 'Welcome back, ' . $user['username'] . '!');

            // Redirect back to the page they wanted
            $go = $_SESSION['redirect_after_login'] ?? SITE_URL . '/index.php';
            unset($_SESSION['redirect_after_login']);
            redirect($go);
        } else {
            $errors[] = 'Invalid username/email or password.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="text-center mb-4">
                    <i class="bi bi-box-arrow-in-right text-success"></i> Local User Login
                </h2>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><ul class="mb-0">
                    <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                </ul></div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Username or Email</label>
                        <input type="text" class="form-control" name="username" value="<?= h($username) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-door-open"></i> Log In
                    </button>
                </form>
                <p class="text-center mt-3 mb-0">New here? <a href="<?= SITE_URL ?>/register.php">Create an account</a></p>
                <hr>
                <p class="text-center small text-muted mb-0">
                    Are you an admin? <a href="<?= ADMIN_URL ?>/login.php">Admin Login</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>