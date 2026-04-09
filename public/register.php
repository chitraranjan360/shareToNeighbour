<?php
$pageTitle = 'Register — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Api_Handler.php'; // for DAWA proxy endpoints

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
    $username      = trim($_POST['username'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $full_name     = trim($_POST['full_name'] ?? '');
    $postal_code   = trim($_POST['postal_code'] ?? '');
    $street        = trim($_POST['street'] ?? '');
    $house_number  = trim($_POST['house_number'] ?? ''); // single field: house OR apartment
    $municipality  = trim($_POST['municipality'] ?? '');
    $dawa_id       = trim($_POST['dawa_id'] ?? '');

    $password      = $_POST['password'] ?? '';
    $confirm       = $_POST['confirm_password'] ?? '';

    $latitude      = $_POST['latitude'] ?? '';
    $longitude     = $_POST['longitude'] ?? '';
    $lat = ($latitude !== '' && is_numeric($latitude)) ? (float)$latitude : null;
    $lng = ($longitude !== '' && is_numeric($longitude)) ? (float)$longitude : null;

    if (strlen($username) < 3)                      $errors[] = 'Username must be at least 3 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
    if (strlen($full_name) < 2)                     $errors[] = 'Full name is required.';

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

    // Build address string for DB (no DB change needed)
    $address = trim($street . ' ' . $house_number);
    if ($municipality !== '') $address .= ', ' . $municipality;

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) $errors[] = 'Username or email already taken.';
        $stmt->close();
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
                            <label class="form-label">Username</label>
                            <div class="input-icon">
                                <i class="bi bi-at"></i>
                                <input type="text" class="form-control form-control-lg" name="username" value="<?= h($username) ?>" placeholder="chitraranjan" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <div class="input-icon">
                                <i class="bi bi-envelope"></i>
                                <input type="email" class="form-control form-control-lg" name="email" value="<?= h($email) ?>" placeholder="example12@gmail.com" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Full Name</label>
                            <div class="input-icon">
                                <i class="bi bi-person"></i>
                                <input type="text" class="form-control form-control-lg" name="full_name" value="<?= h($full_name) ?>" placeholder="Chitraranjan Yadav" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Search Address <span class="text-danger">*</span></label>
                            <div class="input-icon">
                                <i class="bi bi-geo-alt"></i>
                                <input type="text"
                                    class="form-control form-control-lg"
                                    id="address_search"
                                    placeholder="e.g. Rådhuspladsen 1, 1550 København">
                            </div>

                            <!-- Suggestions -->
                            <div id="addressSuggestions" class="list-group dawa-suggest mt-2"
                                style="display:none; max-height:240px; overflow:auto;"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Postal Code (Denmark) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="postal_code" name="postal_code"
                               value="<?= h($postal_code) ?>" required readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Municipality <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="municipality" name="municipality"
                                value="<?= h($municipality) ?>" required readonly>
                        </div>

                        <div class="col-md-7">
                            <label class="form-label">Street <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="street" name="street"
                                value="<?= h($street) ?>" required readonly>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">House/Apartment No. <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="house_number" name="house_number"
                                value="<?= h($house_number) ?>" required readonly>
                        </div>

                        <input type="hidden" id="dawa_id" name="dawa_id" value="<?= h($dawa_id) ?>">
                        <input type="hidden" id="latitude" name="latitude" value="<?= h($lat ?? '') ?>">
                        <input type="hidden" id="longitude" name="longitude" value="<?= h($lng ?? '') ?>">

                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <div class="input-icon">
                                <i class="bi bi-shield-lock"></i>
                                <input type="password" class="form-control form-control-lg" name="password" required minlength="6">
                            </div>
                            <div class="form-text">Minimum 6 characters.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <div class="input-icon">
                                <i class="bi bi-check2-circle"></i>
                                <input type="password" class="form-control form-control-lg" name="confirm_password" required>
                            </div>
                        </div>

                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-success btn-lg w-100 auth-submit">
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
<script>
    const searchInput = document.getElementById('address_search');
    const suggestionsBox = document.getElementById('addressSuggestions');

    const postalInput = document.getElementById('postal_code');
    const streetInput = document.getElementById('street');
    const houseInput = document.getElementById('house_number');
    const municipalityInput = document.getElementById('municipality');

    const dawaIdInput = document.getElementById('dawa_id');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');

    let debounceTimer = null;

    function clearSuggestions() {
        suggestionsBox.innerHTML = '';
        suggestionsBox.style.display = 'none';
    }

    function resetAddressFields() {
        postalInput.value = '';
        streetInput.value = '';
        houseInput.value = '';
        municipalityInput.value = '';
        dawaIdInput.value = '';
        latInput.value = '';
        lngInput.value = '';
    }

    searchInput.addEventListener('input', () => {
        resetAddressFields();
        const q = searchInput.value.trim();
        if (q.length < 3) return clearSuggestions();

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            try {
                const res = await fetch(`?api=dawa_autocomplete&q=${encodeURIComponent(q)}`);
                const data = await res.json();

                suggestionsBox.innerHTML = '';
                if (!Array.isArray(data) || data.length === 0) return clearSuggestions();

                data.forEach(item => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'list-group-item list-group-item-action';
                    btn.textContent = item.tekst || 'Unknown address';

                    btn.onclick = async () => {
                        try {
                            const href = item?.adresse?.href || '';
                            if (!href) return;

                            const dres = await fetch(`?api=dawa_address&href=${encodeURIComponent(href)}`);
                            const detail = await dres.json();

                            if (!dres.ok) {
                                console.error('dawa_address error:', detail);
                                resetAddressFields();
                                return;
                            }

                            streetInput.value = detail.street || '';
                            postalInput.value = detail.postal_code || '';
                            municipalityInput.value = detail.municipality || '';
                            dawaIdInput.value = detail.id || '';

                            // single field rule: house_number OR apartment
                            const h = (detail.house_number || '').trim();
                            const a = (detail.apartment || '').trim();
                            houseInput.value = h !== '' ? h : a;

                            latInput.value = (detail.lat ?? '');
                            lngInput.value = (detail.lng ?? '');

                            searchInput.value = item.tekst || '';
                            clearSuggestions();
                        } catch (err) {
                            console.error('Detail fetch failed:', err);
                            resetAddressFields();
                        }
                    };

                    suggestionsBox.appendChild(btn);
                });

                suggestionsBox.style.display = 'block';
            } catch (err) {
                console.error('Autocomplete failed:', err);
                clearSuggestions();
            }
        }, 250);
    });

    document.addEventListener('click', (e) => {
        if (!suggestionsBox.contains(e.target) && e.target !== searchInput) {
            clearSuggestions();
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>