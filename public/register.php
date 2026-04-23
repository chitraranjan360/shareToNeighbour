<?php
$pageTitle = 'Register — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Api_Handler.php'; // for DAWA proxy endpoints
require_once __DIR__ . '/../includes/fun.php';

if (isUserLoggedIn()) redirect(SITE_URL . '/index.php');

$errors = [];
$username = $email = $full_name = '';
$postal_code = '';
$street = '';
$house_number = '';
$municipality = '';
$lat = $lng = null;
$dawa_id = '';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username      = htmlspecialchars(trim($_POST['username'] ?? ''));
    $email         = htmlspecialchars(trim($_POST['email'] ?? ''));
    $full_name     = htmlspecialchars(trim($_POST['full_name'] ?? ''));
    $postal_code   = htmlspecialchars(trim($_POST['postal_code'] ?? ''));
    $street        = htmlspecialchars(trim($_POST['street'] ?? ''));
    $house_number  = htmlspecialchars(trim($_POST['house_number'] ?? ''));
    $municipality  = htmlspecialchars(trim($_POST['municipality'] ?? ''));
    $dawa_id       = trim($_POST['dawa_id'] ?? '');

    $password      = $_POST['password'] ?? '';
    $confirm       = $_POST['confirm_password'] ?? '';

    $latitude      = $_POST['latitude'] ?? '';
    $longitude     = $_POST['longitude'] ?? '';
    $lat = ($latitude !== '' && is_numeric($latitude)) ? (float)$latitude : null;
    $lng = ($longitude !== '' && is_numeric($longitude)) ? (float)$longitude : null;

    
    // Must come from DAWA selection
    if ($dawa_id === '') $errors[] = 'Please select an address from DAWA suggestions.';

    // 0000 allowed for old users only, not for new registration
    if ($postal_code === '' || !preg_match('/^\d{4}$/', $postal_code) || $postal_code === '0000') {
        $errors[] = 'Postal code must be a valid 4-digit Danish code (0000 not allowed).';
    }

    if ($street === '') $errors[] = 'Street is required.';
    if ($house_number === '') $errors[] = 'House number / apartment number is required.';
    if ($municipality === '') $errors[] = 'Municipality is required.';
    if ($lat === null || $lng === null) $errors[] = 'Address coordinates missing. Please select a DAWA suggestion again.';

    if (strlen($password) < 6)   $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)  $errors[] = 'Passwords do not match.';

    // Build address string for DB 
    $address = trim($street . ' ' . $house_number);
    if ($municipality !== '') $address .= ', ' . $municipality;

    if (empty($errors)) {
        // Check if email already exists
        $existing = checkUserByEmail($conn, $email);
        if ($existing) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password, full_name, postal_code, address, latitude, longitude)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $stmt->bind_param('ssssssdd', $username, $email, $hash, $full_name, $postal_code, $address, $lat, $lng);

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
<link rel="stylesheet" href="css/register.css">
<div class="row justify-content-center">
    <div class="col-md-9 col-lg-7 col-xl-6">
        <div class="auth-card shadow-lg">
            <div class="auth-card-header">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <div>
                        <p class="auth-eyebrow mb-1">Create your account</p>
                        <h2 class="auth-title mb-1">
                            <i class="bi bi-person-plus-fill me-2"></i> Join <?= SITE_NAME ?>
                        </h2>
                        <p class="auth-subtitle mb-0">Register to share furniture and chat with neighbours.</p>
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
                                <div class="fw-semibold mb-1">Please fix the following:</div>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" novalidate autocomplete="off" class="auth-form">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <div class="input-icon">
                                <i class="bi bi-at"></i>
                                <input id="username" type="text" class="form-control form-control-md" name="username" value="<?= h($username) ?>" placeholder="chitraranjan" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <div class="input-icon">
                                <i class="bi bi-envelope"></i>
                                <input id="email" type="email" class="form-control form-control-md" name="email" value="<?= h($email) ?>" placeholder="example12@gmail.com" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <div class="input-icon">
                                <i class="bi bi-person"></i>
                                <input id="full_name" type="text" class="form-control form-control-md" name="full_name" value="<?= h($full_name) ?>" placeholder="Chitraranjan Yadav" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Search Address <span class="text-danger">*</span></label>
                            <div class="input-icon">
                                <i class="bi bi-geo-alt"></i>
                                <input type="text"
                                    class="form-control form-control-md"
                                    id="address_search"
                                    placeholder="e.g. Rådhuspladsen 1, 1550 København">
                            </div>


                            <div id="addressSuggestions" class="list-group mt-1" style="display:none; max-height:200px; overflow-y:auto;">

                            </div>



                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Postal Code (Denmark) </label>
                            <input type="text" class="form-control form-control-md" id="postal_code" name="postal_code"
                                value="<?= h($postal_code) ?>" disabled>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Municipality </label>
                            <input type="text" class="form-control form-control-md" id="municipality" name="municipality"
                                value="<?= h($municipality) ?>" required disabled>
                        </div>

                        <div class="col-md-7">
                            <label class="form-label">Street </label>
                            <input type="text" class="form-control form-control-md" id="street" name="street"
                                value="<?= h($street) ?>" required disabled>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">House/Apartment No. </label>
                            <input type="text" class="form-control form-control-md" id="house_number" name="house_number"
                                value="<?= h($house_number) ?>" required disabled>
                        </div>

                        <input type="hidden" id="dawa_id" name="dawa_id" value="<?= h($dawa_id) ?>">
                        <input type="hidden" id="latitude" name="latitude" value="<?= h($lat ?? '') ?>">
                        <input type="hidden" id="longitude" name="longitude" value="<?= h($lng ?? '') ?>">

                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-icon">
                                <i class="bi bi-shield-lock"></i>
                                <input type="password" class="form-control form-control-md"
                                    id="password" name="password" required minlength="6"
                                    pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{6,}"
                                    placeholder="chitRa1234"
                                    >
                            </div>
                            <small class="form-text text-muted form-text-sm">
                                At least 6 characters, including uppercase, lowercase, and a number.
                            </small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <div class="input-icon">
                                <i class="bi bi-check2-circle"></i>
                                <input type="password" class="form-control form-control-md"
                                    id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>

                        <div class="col-12 mt-2">
                            <button type="submit" id="registerBtn" class="btn btn-success btn-md w-100 auth-submit">
                                <i class="bi bi-check-circle"></i> Create Account
                            </button>
                        </div>
                    </div>
                </form>

                <p class="text-center mt-4 mb-0 text-muted">
                    Already have an account?
                    <a class="fw-semibold text-success text-decoration-none" href="<?= SITE_URL ?>/login.php">Log in</a>
                </p>
            </div>
        </div>
    </div>
</div>
<script src="<?= SITE_URL ?>/js/register.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>