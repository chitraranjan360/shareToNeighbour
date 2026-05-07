<?php
$pageTitle = 'Login — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (isUserLoggedIn()) redirect(SITE_URL . '/index.php');

$errors = [];
$username = '';

//CSFR token generation
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

//Rate limiting to prevent brute force attacks - max 5 attempts per session
if (!isset($_SESSION['login_attempts'])) {
  $_SESSION['login_attempts'] = 0;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
  $password = $_POST['password'] ?? '';

  if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    $errors[] = 'Security check failed.';
  } else if ($_SESSION['login_attempts'] >= 5) {
    $errors[] = 'Too many login attempts. Please try again later.';
  } else {

    $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param('ss', $username, $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
      $errors[] = 'Invalid username or email.';
      $_SESSION['login_attempts']++;
    } else {
      if (!password_verify($password, $user['password_hash'])) {
        $errors[] = 'Incorrect password.';
        $_SESSION['login_attempts']++;
      } else {
        // Reset login attempts on successful login
        $_SESSION['login_attempts'] = 0;

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_type'] = 'user';

        setFlash('success', 'Welcome back, ' . $user['username'] . '!');

        // Redirect back to the page they wanted
        $go = $_SESSION['redirect_after_login'] ?? SITE_URL . '/index.php';
        unset($_SESSION['redirect_after_login']);
        redirect($go);
      }
    }
  }
}

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="css/register.css"><!-- reuse same premium auth styles -->

<div class="row justify-content-center">
  <div class="col-md-9 col-lg-6 col-xl-5">
    <div class="auth-card shadow-lg">
      <div class="auth-card-header">
        <div class="d-flex align-items-center justify-content-between gap-3">
          <div>
            <p class="auth-eyebrow mb-1">Welcome back</p>
            <h2 class="auth-title mb-1">
              <i class="bi bi-box-arrow-in-right me-2"></i> Local User Login
            </h2>
          </div>
          <div class="auth-mark">
            <i class="bi bi-house-heart-fill"></i>
          </div>
        </div>
      </div>

      <div class="auth-card-body">
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger border-0 auth-alert">
            <div class="d-flex gap-2">
              <div class="pt-1"><i class="bi bi-exclamation-triangle-fill"></i></div>
              <div class="flex-grow-1">
                <div class="fw-semibold mb-1">Login failed</div>
                <div><?php echo max(0, 5 - ($_SESSION['login_attempts'] ?? 0)); ?> attempts remaining</div>
                <ul class="mb-0">
                  <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                </ul>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <form method="POST" novalidate id="login_form" autocomplete="off">
          <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Username/Email</label>
              <div class="input-icon">
                <i class="bi bi-person-badge"></i>
                <input
                  type="text"
                  class="form-control form-control-md  "
                  id="login_username"
                  name="username"
                  value="<?= h($username) ?>"
                  placeholder="username or email"
                  required
                  autofocus>

              </div>
              <div class="invalid-feedback">please fill in this field</div>

            </div>

            <div class="col-12">
              <label class="form-label">Password</label>
              <div class="input-icon">
                <i class="bi bi-shield-lock"></i>
                <input
                  type="password"
                  class="form-control form-control-md "
                  id="login_password"
                  name="password"
                  placeholder="••••••••"
                  required>

              </div>
              <div class="invalid-feedback">please fill in this field</div>


              <div class="col-12 mt-2">
                <button type="submit" class="btn btn-success btn-md w-100 auth-submit">
                  <i class="bi bi-door-open"></i> Log In
                </button>
              </div>
            </div>
        </form>

        <div class="text-center mt-4">
          <p class="mb-2 text-muted">
            New here?
            <a class="fw-semibold text-success text-decoration-none" href="<?= SITE_URL ?>/register.php">Create an account</a>
          </p>
          <div class="mb-2 text-muted">
            <a class="small text-decoration-none" href="<?= SITE_URL ?>/forgot_password.php">Forgot password?</a>
          </div>

          <hr class="my-3">

          <p class="small text-muted mb-0">
            Are you an admin?
            <a class="fw-semibold text-success text-decoration-none" href="<?= ADMIN_URL ?>/login.php">Admin Login</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="js/login.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>