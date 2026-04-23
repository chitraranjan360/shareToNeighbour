<?php
$pageTitle = 'Edit Profile — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Api_Handler.php'; // for DAWA proxy endpoints

requireUserLogin();
$uid = currentUserId();


// Load user
$stmt = $conn->prepare("SELECT full_name, email, address, postal_code, latitude, longitude FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    setFlash('error', 'User not found.');
    redirect(SITE_URL . '/profile.php');
}

$full_name   = $user['full_name'] ?? '';
$email       = $user['email'] ?? '';
$postal_code = $user['postal_code'] ?? '';
$addressDb   = $user['address'] ?? '';
$latDb       = $user['latitude'] ?? null;
$lngDb       = $user['longitude'] ?? null;

$street = '';
$house_number = '';
$municipality = '';
$dawa_id = '';

// Parse existing DB address format: "Street 12A, Municipality"
$parts = array_map('trim', explode(',', $addressDb, 2));
$streetHouse = $parts[0] ?? '';
$municipality = $parts[1] ?? '';

if ($streetHouse !== '') {
    $pos = strrpos($streetHouse, ' ');
    if ($pos !== false) {
        $street = trim(substr($streetHouse, 0, $pos));
        $house_number = trim(substr($streetHouse, $pos + 1));
    } else {
        $street = $streetHouse;
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name    = trim($_POST['full_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $postal_code  = trim($_POST['postal_code'] ?? '');
    $street       = trim($_POST['street'] ?? '');
    $house_number = trim($_POST['house_number'] ?? ''); // single field: house OR apartment
    $municipality = trim($_POST['municipality'] ?? '');
    $dawa_id      = trim($_POST['dawa_id'] ?? '');

    $latitude  = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $lat = ($latitude !== '' && is_numeric($latitude)) ? (float)$latitude : null;
    $lng = ($longitude !== '' && is_numeric($longitude)) ? (float)$longitude : null;

    if ($full_name === '') $errors[] = 'Full name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';

    // Existing users may still have 0000 until they update; here we enforce valid DK on save
    if ($postal_code === '' || !preg_match('/^\d{4}$/', $postal_code) || $postal_code === '0000') {
        $errors[] = 'Postal code must be a valid 4-digit Danish code (0000 not allowed when saving).';
    }

    if ($street === '') $errors[] = 'Street is required.';
    if ($house_number === '') $errors[] = 'House number / apartment number is required.';
    if ($municipality === '') $errors[] = 'Municipality is required.';
    if ($dawa_id === '') $errors[] = 'Please select an address from DAWA suggestions.';
    if ($lat === null || $lng === null) $errors[] = 'Please select address from DAWA suggestions so we can calculate distance.';

    // Build address for DB: "Street Number, Municipality"
    $address = trim($street . ' ' . $house_number);
    if ($municipality !== '') $address .= ', ' . $municipality;

    // Check email uniqueness except current user
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
        $stmt->bind_param('si', $email, $uid);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Email is already used by another account.';
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE users
            SET full_name = ?, email = ?, address = ?, postal_code = ?, latitude = ?, longitude = ?
            WHERE id = ?
        ");
        $stmt->bind_param('ssssddi', $full_name, $email, $address, $postal_code, $lat, $lng, $uid);

        if ($stmt->execute()) {
            $stmt->close();
            setFlash('success', 'Profile updated successfully.');
            redirect(SITE_URL . '/profile.php');
        }
        $stmt->close();
        $errors[] = 'Profile update failed.';
    }

    $latDb = $lat;
    $lngDb = $lng;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="mb-4"><i class="bi bi-pencil-square text-success"></i> Edit Profile</h2>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input class="form-control" name="full_name" value="<?= h($full_name) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input class="form-control" name="email" value="<?= h($email) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Search Address *</label>
                        <input type="text" class="form-control" id="address_search"
                               placeholder="Start typing full address, e.g. Rådhuspladsen 1, 1550 København">
                        <div id="addressSuggestions" class="list-group mt-1"
                             style="display:none; max-height:220px; overflow:auto;"></div>
                        <div class="form-text">Select the suggested address to auto-fill locked address fields.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Postal Code (Denmark) *</label>
                        <input type="text" class="form-control" id="postal_code" name="postal_code"
                               value="<?= h($postal_code) ?>" required readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Street *</label>
                        <input type="text" class="form-control" id="street" name="street"
                               value="<?= h($street) ?>" required readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">House Number / Apartment Number *</label>
                        <input type="text" class="form-control" id="house_number" name="house_number"
                               value="<?= h($house_number) ?>" required readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Municipality *</label>
                        <input type="text" class="form-control" id="municipality" name="municipality"
                               value="<?= h($municipality) ?>" required readonly>
                    </div>

                    <input type="hidden" id="dawa_id" name="dawa_id" value="<?= h($dawa_id) ?>">
                    <input type="hidden" id="latitude" name="latitude" value="<?= h($latDb ?? '') ?>">
                    <input type="hidden" id="longitude" name="longitude" value="<?= h($lngDb ?? '') ?>">

                    <div class="d-flex gap-2">
                        <button class="btn btn-success flex-grow-1" type="submit">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                        <a href="<?= SITE_URL ?>/profile.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('address_search');
    const suggestionsBox = document.getElementById('addressSuggestions');

    const postalInput = document.getElementById('postal_code');
    const streetInput = document.getElementById('street');
    const houseInput = document.getElementById('house_number');
    const municipalityInput = document.getElementById('municipality');

    const dawaIdInput = document.getElementById('dawa_id');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');

    if (!searchInput || !suggestionsBox) return;

    let debounceTimer = null;

    function clearSuggestions() {
        suggestionsBox.innerHTML = '';
        suggestionsBox.style.display = 'none';
    }

    function resetAddressFields() {
        if (postalInput) postalInput.value = '';
        if (streetInput) streetInput.value = '';
        if (houseInput) houseInput.value = '';
        if (municipalityInput) municipalityInput.value = '';
        if (dawaIdInput) dawaIdInput.value = '';
        if (latInput) latInput.value = '';
        if (lngInput) lngInput.value = '';
    }

    searchInput.addEventListener('input', function () {
        resetAddressFields();
        const q = searchInput.value.trim();
        if (q.length < 3) {
            clearSuggestions();
            return;
        }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async function () {
            try {
                const res = await fetch('?api=dawa_autocomplete&q=' + encodeURIComponent(q));
                const data = await res.json();

                suggestionsBox.innerHTML = '';
                if (!Array.isArray(data) || data.length === 0) {
                    clearSuggestions();
                    return;
                }

                data.forEach(function (item) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'list-group-item list-group-item-action';
                    btn.textContent = item.tekst || 'Unknown address';

                    btn.onclick = async function () {
                        try {
                            const href = item && item.adresse ? (item.adresse.href || '') : '';
                            if (!href) return;

                            const dres = await fetch('?api=dawa_address&href=' + encodeURIComponent(href));
                            const detail = await dres.json();

                            if (!dres.ok) {
                                console.error('dawa_address error:', detail);
                                resetAddressFields();
                                return;
                            }

                            if (streetInput) streetInput.value = detail.street || '';
                            if (postalInput) postalInput.value = detail.postal_code || '';
                            if (municipalityInput) municipalityInput.value = detail.municipality || '';
                            if (dawaIdInput) dawaIdInput.value = detail.id || '';

                            const h = (detail.house_number || '').trim();
                            const a = (detail.apartment || '').trim();
                            if (houseInput) houseInput.value = h !== '' ? h : a;

                            if (latInput) latInput.value = detail.lat ?? '';
                            if (lngInput) lngInput.value = detail.lng ?? '';

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

    document.addEventListener('click', function (e) {
        if (!suggestionsBox.contains(e.target) && e.target !== searchInput) {
            clearSuggestions();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>