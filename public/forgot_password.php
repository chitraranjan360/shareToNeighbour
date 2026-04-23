<?php
$pageTitle = 'Forgot Password — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';
if (isUserLoggedIn()) {
  redirect(SITE_URL . '/index.php');
}

$errors = [];

if (empty($_SESSION['csrf_forgot_token'])) {
  $_SESSION['csrf_forgot_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');

  $genericMsg = "we sent a password reset link. Please check your inbox (and spam folder).";

  if (!hash_equals($_SESSION['csrf_forgot_token'], $_POST['csrf_forgot_token'] ?? '')) {
    $errors[] = 'Security check failed.';
  } elseif ($email === '') {
    $errors[] = 'Email is required.';
  } else {
    $stmt = $conn->prepare("SELECT id, email, full_name, username FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($u) {
      $token = bin2hex(random_bytes(32));
      $hash  = hash('sha256', $token);
      $exp   = date('Y-m-d H:i:s', time() + 3600);

      $stmt = $conn->prepare("UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ? LIMIT 1");
      $stmt->bind_param('ssi', $hash, $exp, $u['id']);
      $stmt->execute();
      $stmt->close();

      $link = SITE_URL . '/reset_password.php?email=' . urlencode($email) . '&token=' . urlencode($token);

      $toName = $u['full_name'] ?: ($u['username'] ?: 'User');
      $subject = 'Reset your ShareToNeighbour password';
      $bodyText =
        "Hi {$toName},\n\n" .
        "We received a request to reset your password.\n\n" .
        "Reset link (valid for 1 hour):\n{$link}\n\n" .
        "If you did not request this, you can ignore this email.\n\n" .
        "— ShareToNeighbour";

      $sent = sendEmailAlert($u['email'], $toName, $subject, $bodyText);
      if (!$sent) {
        error_log("Forgot password email failed for: " . $u['email']);
      }
    }

    $_SESSION['csrf_forgot_token'] = bin2hex(random_bytes(32));

    setFlash('success', $genericMsg);
    redirect(SITE_URL . '/login.php');
  }
}

// Determine input state for PHP-side validation feedback
$emailPosted  = isset($_POST['email']);
$emailValue   = h($_POST['email'] ?? '');
$emailInvalid = $emailPosted && !empty($errors);

require_once __DIR__ . '/../includes/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="css/register.css">

<div class="row justify-content-center">
  <div class="col-md-9 col-lg-6 col-xl-5">
    <div class="auth-card shadow-lg">
      <div class="auth-card-header">
        <div class="d-flex align-items-center justify-content-between gap-3">
          <div>
            <p class="auth-eyebrow mb-1">Account recovery</p>
            <h2 class="auth-title mb-1">
              <i class="bi bi-shield-lock me-2"></i> Forgot Password
            </h2>
            <p class="auth-subtitle mb-0">We'll email you a reset link.</p>
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
                <div class="fw-semibold mb-1">Request failed</div>
                <ul class="mb-0">
                  <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                </ul>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <form method="POST" class="auth-form" autocomplete="off" id="email_form" novalidate>
          <input type="hidden" name="csrf_forgot_token" value="<?= h($_SESSION['csrf_forgot_token']) ?>">

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Email</label>
              <div class="input-icon">
                <i class="bi bi-envelope"></i>
                <input
                  type="email"
                  class="form-control form-control-md <?= $emailInvalid ? 'is-invalid' : '' ?>"
                  name="email"
                  id="emailInput"
                  placeholder="you@example.com"
                  value="<?= $emailValue ?>"
                  required
                  autofocus>
              </div>
              <div class="invalid-feedback">
                Please enter a valid email address.
              </div>
            </div>

            <div class="col-12 mt-2">
              <button type="submit" class="btn btn-success btn-md w-100 auth-submit">
                <i class="bi bi-send"></i> Send reset link
              </button>
            </div>
          </div>
        </form>

        <div class="text-center mt-4">
          <p class="small text-muted mb-0">
            Remembered your password?
            <a class="fw-semibold text-success text-decoration-none" href="<?= SITE_URL ?>/login.php">Back to login</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="js/app.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>