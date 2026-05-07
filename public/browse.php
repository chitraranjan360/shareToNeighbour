<?php
$pageTitle = 'Find Furniture — ShareToNeighbour';
require_once __DIR__ . '/../includes/header.php';

$search    = trim($_GET['q'] ?? '');
$category  = $_GET['category'] ?? '';
$condition = $_GET['condition'] ?? '';

// Check whether the visitor is logged in so distance filtering can work.
$isLoggedIn = isUserLoggedIn();
$uid = $isLoggedIn ? currentUserId() : null;

/**
 * Distance slider values (km)
 * 0 = any distance (disabled)
 */
$allowedRadius = [0, 1, 5, 10, 15, 20];
$radiusKm = isset($_GET['radius_km']) ? (int)$_GET['radius_km'] : 0;
if (!in_array($radiusKm, $allowedRadius, true)) {
    // Ignore any radius value that is not in the allowed list.
    $radiusKm = 0;
}

// Guests must log in before using the distance filter.
if (!$isLoggedIn && $radiusKm > 0) {
    redirect(SITE_URL . '/login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
}

// Load the user's saved location so we can calculate nearby items.
$userLat = $userLng = null;
if ($isLoggedIn) {
    // Read latitude and longitude from the logged-in user's profile.
    $stmt = $conn->prepare("SELECT latitude, longitude FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $loc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($loc && $loc['latitude'] !== null && $loc['longitude'] !== null) {
        $userLat = (float)$loc['latitude'];
        $userLng = (float)$loc['longitude'];
    }
}

/**
 * Build the search query.
 * Guests see the normal listing order, while logged-in users with a saved
 * location can also sort and filter by distance.
 */
$params = [];
$types  = '';

if ($isLoggedIn && $userLat !== null && $userLng !== null) {
    // Add a distance column so nearby items can be sorted and filtered.
    $sql = "
SELECT fi.*, u.username,
(6371 * acos(
    cos(radians(?)) * cos(radians(u.latitude))
    * cos(radians(u.longitude) - radians(?))
    + sin(radians(?)) * sin(radians(u.latitude))
)) AS distance_km
FROM furniture_items fi
JOIN users u ON fi.user_id = u.id
WHERE fi.is_deleted = 0 AND
 fi.status IN ('available','requested','taken')
  AND fi.user_id <> ?
  AND u.latitude IS NOT NULL AND u.longitude IS NOT NULL
";
    $params = [$userLat, $userLng, $userLat, $uid];
    $types  = 'dddi';
} else {
    // Fall back to a simpler query when distance cannot be calculated.
    $sql = "
SELECT fi.*, u.username, NULL AS distance_km
FROM furniture_items fi
JOIN users u ON fi.user_id = u.id
WHERE fi.is_deleted = 0 AND
 fi.status IN ('available','requested','taken')
";
}

// Apply text search to title and description.
if ($search !== '') {
    $sql .= " AND (fi.title LIKE ? OR fi.description LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

// Apply category filter only when the value is one of the allowed categories.
if ($category !== '' && in_array($category, ['sofa', 'table', 'chair', 'bed', 'shelf', 'desk', 'wardrobe', 'other'], true)) {
    $sql .= " AND fi.category = ?";
    $params[] = $category;
    $types .= 's';
}

// Apply condition filter only when the value is one of the allowed states.
if ($condition !== '' && in_array($condition, ['like_new', 'good', 'fair', 'needs_repair'], true)) {
    $sql .= " AND fi.condition_level = ?";
    $params[] = $condition;
    $types .= 's';
}

// Only filter by radius when distance data is available.
if ($radiusKm > 0 && $userLat !== null && $userLng !== null) {
    $sql .= " HAVING distance_km <= ?";
    $params[] = (float)$radiusKm;
    $types .= 'd';
}

// Sort by distance when the distance filter is active; otherwise use newest first.
if ($userLat !== null && $userLng !== null && $radiusKm > 0) {
    $sql .= " ORDER BY distance_km ASC";
} else {
    $sql .= " ORDER BY fi.created_at DESC";
}

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    // Bind the collected filter values into the query.
    $stmt->bind_param($types, ...$params);
}

// Run the query and load all matching items.
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Turn a distance number into the text shown above the slider.
function radiusLabel(int $v): string {
    return match ($v) {
        0 => 'All items',
        1 => 'Within 1 km',
        5 => 'Within 5 km',
        10 => 'Within 10 km',
        15 => 'Within 15 km',
        20 => 'Within 20 km',
        default => 'All items'
    };
}
?>

<h2 class="mb-4"><i class="bi bi-search"></i> Find Furniture Near You</h2>

<?php if ($isLoggedIn && ($userLat === null || $userLng === null)): ?>
    <div class="alert alert-info">
        <!-- Tell users why distance features are unavailable. -->
        Set your address in <a class="alert-link" href="<?= SITE_URL ?>/edit_profile.php">Edit Profile</a>
        to see accurate distance and use distance filter.
    </div>
<?php endif; ?>

<!-- Search and filter form. -->
<form id="browseFiltersForm" method="GET" class="card shadow-sm p-3 mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">Search</label>
            <input type="text" name="q" class="form-control" placeholder="e.g. sofa, desk…" value="<?= h($search) ?>">
        </div>

        <div class="col-md-2">
            <label class="form-label small">Category</label>
            <select name="category" class="form-select">
                <option value="">All</option>
                <?php foreach (['sofa', 'table', 'chair', 'bed', 'shelf', 'desk', 'wardrobe', 'other'] as $c): ?>
                    <option value="<?= $c ?>" <?= $category === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small">Condition</label>
            <select name="condition" class="form-select">
                <option value="">All</option>
                <option value="like_new" <?= $condition === 'like_new' ? 'selected' : '' ?>>Like New</option>
                <option value="good" <?= $condition === 'good' ? 'selected' : '' ?>>Good</option>
                <option value="fair" <?= $condition === 'fair' ? 'selected' : '' ?>>Fair</option>
                <option value="needs_repair" <?= $condition === 'needs_repair' ? 'selected' : '' ?>>Needs Repair</option>
            </select>
        </div>

        <div class="col-md-3 border rounded-3 p-3">
            <?php if ($isLoggedIn): ?>
                <!-- Slider is only useful when the user has a saved location. -->
                <label for="radius_km" class="form-label small d-flex justify-content-between">
                    <span><i class="bi bi-geo-alt"></i> Distance</span>
                    <strong id="radiusLabel"><?= h(radiusLabel($radiusKm)) ?></strong>
                </label>

                <input
                    type="range"
                    id="radius_km"
                    name="radius_km"
                    class="form-range"
                    min="0"
                    max="5"
                    step="1"
                    value="<?= (int)array_search($radiusKm, $allowedRadius, true) ?>"
                    <?= ($userLat === null || $userLng === null) ? 'disabled' : '' ?>
                >

                <div class="d-flex justify-content-between small text-muted">
                    <span>All</span><span>1 km</span><span>5 km</span><span>10 km</span><span>15 km</span><span>20 km</span>
                </div>
            <?php else: ?>
                <div class="mt-4">
                    <!-- Guests are asked to log in before using distance search. -->
                    <a href="<?= SITE_URL ?>/login.php?next=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                       class="text-decoration-none text-muted">
                        <i class="bi bi-geo-alt"></i> Distance filter (login required)
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-2 d-flex gap-2">
            
            <button type="submit" id="applyFiltersBtn" class="btn btn-success flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
            <a id="clearFiltersBtn" href="<?= SITE_URL ?>/browse.php" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>

    <?php if ($isLoggedIn): ?>
        <!-- Store the current radius so JavaScript can keep the slider in sync. -->
        <input type="hidden" id="radius_km_hidden_value" value="<?= (int)$radiusKm ?>">
    <?php endif; ?>
</form>

<!-- Show how many items matched the filters. -->
<p class="text-muted"><?= count($items) ?> item(s) found</p>

<div class="row g-4">
    <?php if (empty($items)): ?>
        <div class="col-12 text-center py-5">
            <!-- Empty state when no items match the current filters. -->
            <i class="bi bi-inbox display-1 text-muted"></i>
            <p class="text-muted mt-3">No furniture found.</p>
        </div>
    <?php else: foreach ($items as $item): ?>
        <div class="col-md-4 col-lg-3">
            <!-- Each card links to the full item details page. -->
            <a href="<?= SITE_URL ?>/item.php?id=<?= (int)$item['id'] ?>" class="text-decoration-none text-dark">
                <div class="card h-100 shadow-sm item-card rounded-4 border-0">
                    <img
                        src="<?= UPLOAD_URL . '/' . h($item['photo'] ?: 'placeholder.jpg') ?>"
                        class="card-img-top rounded-4"
                        alt="<?= h($item['title']) ?>"
                        style="height:200px;object-fit:cover;"
                    >

                    <div class="card-body d-flex flex-column">
                        <div class="d-flex gap-1 mb-2 flex-wrap">
                            <!-- Category, condition, and status badges help users scan items quickly. -->
                            <span class="badge bg-success"><?= h(ucfirst($item['category'])) ?></span>
                            <span class="badge bg-secondary"><?= h(ucfirst(str_replace('_', ' ', $item['condition_level']))) ?></span>

                            <?php
                            $status = $item['status'] ?? 'available';
                            $statusClass = match ($status) {
                                'available' => 'bg-primary',
                                'requested' => 'bg-warning text-dark',
                                'taken'     => 'bg-danger text-white',
                                default     => 'bg-light text-dark'
                            };
                            ?>
                            <span class="badge <?= $statusClass ?>"><?= h(ucfirst($status)) ?></span>
                        </div>

                        <h6 class="card-title"><?= h($item['title']) ?></h6>
                        <p class="card-text text-muted small flex-grow-1">
                            <?= h(mb_strimwidth($item['description'], 0, 80, '…')) ?>
                        </p>

                        <div class="d-flex justify-content-between align-items-center">
                            <?php if ($isLoggedIn && $item['distance_km'] !== null): ?>
                                <small class="text-muted">
                                    <i class="bi bi-geo-alt"></i>
                                    <?php
                                    $km = (float)$item['distance_km'];
                                    if ($km < 1) {
                                        echo (int)round($km * 1000) . " m";
                                    } else {
                                        echo number_format($km, 1) . " km";
                                    }
                                    ?>
                                </small>
                            <?php elseif ($isLoggedIn): ?>
                                <small class="text-muted">
                                    <i class="bi bi-geo-alt"></i> Distance unavailable
                                </small>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <span></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; endif; ?>
</div>

<script src="<?= SITE_URL ?>/js/browse-filters.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>