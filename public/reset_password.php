<?php
$pageTitle = 'Reset Password — ShareToNeighbour';

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// If already logged in, send to home
if (isUserLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$errors = [];

// Read token/email from GET (first visit) or POST (submit)
$email = trim($_GET['email'] ?? ($_POST['email'] ?? ''));
$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));

if ($email === '' || $token === '') {
    setFlash('error', 'Invalid or incomplete reset link.');
    redirect(SITE_URL . '/forgot_password.php');
}

// CSRF for this form
if (empty($_SESSION['csrf_reset_token'])) {
    $_SESSION['csrf_reset_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($_SESSION['csrf_reset_token']) ||
        !hash_equals($_SESSION['csrf_reset_token'], $_POST['csrf_reset_token'] ?? '')
    ) {
        $errors[] = 'Security check failed. Please try again.';
    } else {
        $password  = (string)($_POST['password'] ?? '');
        $password2 = (string)($_POST['password2'] ?? '');

        $hasMinLength = strlen($password) >= 8;
        $hasUpper     = preg_match('/[A-Z]/', $password);
        $hasLower     = preg_match('/[a-z]/', $password);
        $hasDigit     = preg_match('/\d/', $password);
        $hasSpecial   = preg_match('/[^A-Za-z0-9]/', $password);

        if ($password === '' || $password2 === '') {
            $errors[] = 'Please fill in both password fields.';
        } elseif (!$hasMinLength || !$hasUpper || !$hasLower || !$hasDigit || !$hasSpecial) {
            $errors[] = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
        } elseif ($password !== $password2) {
            $errors[] = 'Passwords do not match.';
        } else {
            $tokenHash = hash('sha256', $token);

            $stmt = $conn->prepare("
                SELECT id
                FROM users
                WHERE email = ?
                  AND reset_token_hash = ?
                  AND reset_token_expires_at IS NOT NULL
                  AND reset_token_expires_at > NOW()
                LIMIT 1
            ");
            $stmt->bind_param('ss', $email, $tokenHash);
            $stmt->execute();
            $u = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$u) {
                $errors[] = 'This reset link is invalid or has expired. Please request a new one.';
            } else {
                $newHash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("
                    UPDATE users
                    SET password = ?,
                        reset_token_hash = NULL,
                        reset_token_expires_at = NULL
                    WHERE id = ?
                    LIMIT 1
                ");
                $stmt->bind_param('si', $newHash, $u['id']);
                $stmt->execute();
                $stmt->close();

                unset($_SESSION['csrf_reset_token']);

                setFlash('success', 'Password updated successfully. Please log in.');
                redirect(SITE_URL . '/login.php');
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="css/register.css">


<div class="row justify-content-center">
    <div class="col-md-9 col-lg-6 col-xl-5">
        <div class="auth-card shadow-lg">
            <div class="auth-card-header">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <div>
                        <p class="auth-eyebrow mb-1">Security</p>
                        <h2 class="auth-title mb-1">
                            <i class="bi bi-shield-lock me-2"></i> Reset Password
                        </h2>
                        <p class="auth-subtitle mb-0">Choose a new password for your account.</p>
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
                                <div class="fw-semibold mb-1">Reset failed</div>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $e): ?>
                                        <li><?= h($e) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" id="reset_password_form" autocomplete="off" novalidate>
                    <input type="hidden" name="csrf_reset_token" value="<?= h($_SESSION['csrf_reset_token']) ?>">
                    <input type="hidden" name="email" value="<?= h($email) ?>">
                    <input type="hidden" name="token" value="<?= h($token) ?>">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="resetNewPassword">New password</label>
                            <div class="input-icon">
                                <i class="bi bi-key"></i>
                                <input
                                    type="password"
                                    class="form-control form-control-md"
                                    id="resetNewPassword"
                                    name="password"
                                    placeholder="••••••••"
                                    autocomplete="new-password"
                                    aria-describedby="resetNewPassword_feedback passwordHelp"
                                    required>
                                <div id="resetNewPassword_feedback" class="invalid-feedback">
                                    Password must meet all required rules
                                </div>
                            </div>

                            <div id="passwordHelp" class="form-text">Your password must satisfy all rules below.</div>

                            <div class="password-strength mt-2">
                                <div class="progress" style="height: 8px;">
                                    <div id="passwordStrengthBar" class="progress-bar" role="progressbar" style="width: 0%;"></div>
                                </div>
                                <small id="passwordStrengthText" class="form-text d-block mt-1">Too weak</small>
                            </div>

                            <ul id="passwordRules" class="list-unstyled small mt-2 mb-0">
                                <li data-rule="len">✗ At least 8 characters</li>
                                <li data-rule="upper">✗ One uppercase letter (A–Z)</li>
                                <li data-rule="lower">✗ One lowercase letter (a–z)</li>
                                <li data-rule="digit">✗ One number (0–9)</li>
                                <li data-rule="spec">✗ One special character (!@#$…)</li>
                            </ul>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="resetConfirmPassword">Confirm new password</label>
                            <div class="input-icon">
                                <i class="bi bi-check2-circle"></i>
                                <input
                                    type="password"
                                    class="form-control form-control-md"
                                    id="resetConfirmPassword"
                                    name="password2"
                                    placeholder="••••••••"
                                    autocomplete="new-password"
                                    aria-describedby="resetConfirmPassword_feedback"
                                    required>
                                <div id="resetConfirmPassword_feedback" class="invalid-feedback">
                                    Passwords do not match
                                </div>
                            </div>
                        </div>

                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-success btn-md w-100 auth-submit">
                                <i class="bi bi-save2"></i> Update password
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

<script src="js/reset-password.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>