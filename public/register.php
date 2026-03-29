<?php
$pageTitle = 'Register — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
if (isUserLoggedIn()) redirect(SITE_URL . '/index.php');

$errors = [];
$username = $email = $full_name = $address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (strlen($username) < 3)                       $errors[] = 'Username must be at least 3 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Please enter a valid email.';
    if (strlen($full_name) < 2)                      $errors[] = 'Full name is required.';
    if (strlen($password) < 6)                       $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)                       $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) $errors[] = 'Username or email already taken.';
        $stmt->close();
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $lat  = 55.6761 + (mt_rand(-150, 150) / 10000);
        $lng  = 12.5683 + (mt_rand(-150, 150) / 10000);

        $stmt = $conn->prepare("INSERT INTO users (username,email,password,full_name,address,latitude,longitude) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssdd', $username, $email, $hash, $full_name, $address, $lat, $lng);
        if ($stmt->execute()) {
            setFlash('success', 'Account created! Please log in.');
            redirect(SITE_URL . '/login.php');
        } else {
            $errors[] = 'Registration failed. Try again.';
        }
        $stmt->close();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="text-center mb-4">
                    <i class="bi bi-person-plus-fill text-success"></i> Create Account
                </h2>
                <p class="text-center text-muted small">Register to share furniture and chat with neighbours</p>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><ul class="mb-0">
                    <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                </ul></div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" value="<?= h($username) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?= h($email) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" value="<?= h($full_name) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address / Neighbourhood</label>
                        <input type="text" class="form-control" name="address" value="<?= h($address) ?>"
                               placeholder="e.g. Nørrebro, Copenhagen">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-circle"></i> Register
                    </button>
                </form>
                <p class="text-center mt-3 mb-0">Already have an account? <a href="<?= SITE_URL ?>/login.php">Log in</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>