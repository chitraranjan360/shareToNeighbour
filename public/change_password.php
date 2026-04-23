<?php
$pageTitle = 'Change Password — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireUserLogin();
$uid = currentUserId();

$errors = [];

if (empty($_SESSION['csrf_change_password_token'])) {
    $_SESSION['csrf_change_password_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword = $_POST['oldPassword'] ?? '';
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['password2'] ?? '';

    if (!hash_equals($_SESSION['csrf_change_password_token'], $_POST['csrf_change_password_token'] ?? '')) {
        $errors[] = 'Security check failed.';
    }

    if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $errors[] = 'Please fill in all fields.';
    }

    if (strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    }

    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New password and confirmation do not match.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($oldPassword, $row['password'])) {
            $errors[] = 'Old password is incorrect.';
        } elseif (password_verify($newPassword, $row['password'])) {
            $errors[] = 'New password must be different from old password.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

            $stmt = $conn->prepare('UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?');
            $stmt->bind_param('si', $newHash, $uid);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                $_SESSION['csrf_change_password_token'] = bin2hex(random_bytes(32));
                setFlash('success', 'Password updated successfully.');
                redirect(SITE_URL . '/profile.php');
            }

            $errors[] = 'Failed to update password. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

</style>
<link rel="stylesheet" href="css/register.css">

<div class="row justify-content-center">
    <div class="col-md-9 col-lg-6 col-xl-5">
        <div class="auth-card shadow-lg">
            <div class="auth-card-header">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <div>
                        <p class="auth-eyebrow mb-1">Security settings</p>
                        <h2 class="auth-title mb-1">
                            <i class="bi bi-shield-lock me-2"></i> Change Password
                        </h2>
                        <p class="auth-subtitle mb-0">Use your current password to set a new one.</p>
                    </div>
                    <div class="auth-mark">
                        <i class="bi bi-key-fill"></i>
                    </div>
                </div>
            </div>

            <div class="auth-card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-0 auth-alert">
                        <div class="d-flex gap-2">
                            <div class="pt-1"><i class="bi bi-exclamation-triangle-fill"></i></div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold mb-1">Password update failed</div>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form" id="change_password_form" autocomplete="off" novalidate>
                    <input type="hidden" name="csrf_change_password_token" value="<?= h($_SESSION['csrf_change_password_token']) ?>">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="oldPassword">Old password</label>
                            <div class="input-icon">
                                <i class="bi bi-lock"></i>
                                <input
                                    type="password"
                                    id="oldPassword"
                                    class="form-control form-control-md"
                                    name="oldPassword"
                                    autocomplete="current-password"
                                    aria-describedby="oldPassword_feedback"
                                    required>
                                <div id="oldPassword_feedback" class="invalid-feedback">
                                    Please fill in this field
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="newPassword">New password</label>
                            <div class="input-icon">
                                <i class="bi bi-key"></i>
                                <input
                                    type="password"
                                    id="newPassword"
                                    class="form-control form-control-md"
                                    name="password"
                                    placeholder="••••••••"
                                    autocomplete="new-password"
                                    aria-describedby="newPassword_feedback passwordHelp"
                                    required>
                                <div id="newPassword_feedback" class="invalid-feedback">
                                    Please enter a stronger password
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
                            <label class="form-label" for="confirmPassword">Confirm new password</label>
                            <div class="input-icon">
                                <i class="bi bi-check2-circle"></i>
                                <input
                                    type="password"
                                    id="confirmPassword"
                                    class="form-control form-control-md"
                                    name="password2"
                                    placeholder="••••••••"
                                    autocomplete="new-password"
                                    aria-describedby="confirmPassword_feedback"
                                    required>
                                <div id="confirmPassword_feedback" class="invalid-feedback">
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
                    <a class="small text-decoration-none" href="<?= SITE_URL ?>/profile.php">Back to profile</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="js/change-password.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>