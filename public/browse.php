<?php
$pageTitle = 'Find Furniture — ShareToNeighbour';
require_once __DIR__ . '/../includes/header.php';

$search    = trim($_GET['q'] ?? '');
$category  = $_GET['category'] ?? '';
$condition = $_GET['condition'] ?? '';
$nearby    = isset($_GET['nearby']) && $_GET['nearby'] === '1';

// User location (or Copenhagen centre default)
$userLat = 55.6761;
$userLng = 12.5683;
if (isUserLoggedIn()) {
    $stmt = $conn->prepare("SELECT latitude, longitude FROM users WHERE id = ?");
    $uid  = currentUserId();
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $loc = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($loc) {
        $userLat = (float)$loc['latitude'];
        $userLng = (float)$loc['longitude'];
    }
}

$sql = "SELECT fi.*, u.username,
        (6371 * acos(cos(radians(?)) * cos(radians(fi.latitude))
        * cos(radians(fi.longitude) - radians(?))
        + sin(radians(?)) * sin(radians(fi.latitude)))) AS distance_km
        FROM furniture_items fi
        JOIN users u ON fi.user_id = u.id
        WHERE fi.status IN ('available','requested','taken')";
$params = [$userLat, $userLng, $userLat];
$types  = 'ddd';

if ($search !== '') {
    $sql .= " AND (fi.title LIKE ? OR fi.description LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}
if ($category !== '' && in_array($category, ['sofa', 'table', 'chair', 'bed', 'shelf', 'desk', 'wardrobe', 'other'])) {
    $sql .= " AND fi.category = ?";
    $params[] = $category;
    $types .= 's';
}
if ($condition !== '' && in_array($condition, ['like_new', 'good', 'fair', 'needs_repair'])) {
    $sql .= " AND fi.condition_level = ?";
    $params[] = $condition;
    $types .= 's';
}
if ($nearby) {
    $sql .= " HAVING distance_km <= 1.0";
}
$sql .= " ORDER BY fi.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<h2 class="mb-4"><i class="bi bi-search"></i> Find Furniture Near You</h2>

<!-- FILTERS -->
<form method="GET" class="card shadow-sm p-3 mb-4">
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
        <div class="col-md-2">
            <div class="form-check mt-4">
                <input type="checkbox" class="form-check-input" name="nearby" value="1" id="nearby" <?= $nearby ? 'checked' : '' ?>>
                <label class="form-check-label" for="nearby"><i class="bi bi-geo-alt"></i> Within 1 km</label>
            </div>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-success flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
            <a href="<?= SITE_URL ?>/browse.php" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
</form>

<p class="text-muted"><?= count($items) ?> item(s) found</p>

<div class="row g-4">
    <?php if (empty($items)): ?>
        <div class="col-12 text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <p class="text-muted mt-3">No furniture found.</p>
        </div>
        <?php else: foreach ($items as $item): ?>
            <div class="col-md-4 col-lg-3">
                <div class="card h-100 shadow-sm item-card">
                    <img src="<?= UPLOAD_URL . '/' . h($item['photo'] ?: 'placeholder.jpg') ?>"
                        class="card-img-top" alt="<?= h($item['title']) ?>" style="height:200px;object-fit:cover;">
                    <div class="card-body d-flex flex-column">
                        
                        <div class="d-flex gap-1 mb-2 flex-wrap">
                            <!-- Category -->
                            <span class="badge bg-success">
                                <?= h(ucfirst($item['category'])) ?>
                            </span>

                            <!-- Condition -->
                            <span class="badge bg-secondary">
                                <?= h(ucfirst(str_replace('_', ' ', $item['condition_level']))) ?>
                            </span>

                            <!-- Status -->
                            <?php
                            $status = $item['status'] ?? 'available';
                            $statusClass = match ($status) {
                                'available' => 'bg-primary',
                                'requested' => 'bg-warning text-dark',
                                'taken'     => 'bg-dark',
                                default     => 'bg-light text-dark'
                            };
                            ?>
                            <span class="badge <?= $statusClass ?>">
                                <?= h(ucfirst($status)) ?>
                            </span>
                        </div>
                        <h6 class="card-title"><?= h($item['title']) ?></h6>
                        <p class="card-text text-muted small flex-grow-1"><?= h(mb_strimwidth($item['description'], 0, 80, '…')) ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= number_format((float)$item['distance_km'], 1) ?> km</small>
                            <a href="<?= SITE_URL ?>/item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-success">View</a>
                        </div>
                    </div>
                </div>
            </div>
    <?php endforeach;
    endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>