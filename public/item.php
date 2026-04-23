<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$itemId = (int)($_GET['id'] ?? 0);
if ($itemId <= 0) {
    setFlash('error', 'Item not found.');
    redirect(SITE_URL . '/browse.php');
}
$stmt = $conn->prepare("
  SELECT
    fi.*,
    u.username,
    u.full_name,
    u.latitude  AS owner_lat,
    u.longitude AS owner_lng
  FROM furniture_items fi
  JOIN users u ON fi.user_id = u.id
  WHERE fi.id = ?
");
$stmt->bind_param('i', $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    setFlash('error', 'Item not found.');
    redirect(SITE_URL . '/browse.php');
}

// Load gallery images (1–3)
$stmt = $conn->prepare("
    SELECT filename
    FROM furniture_item_images
    WHERE item_id = ?
    ORDER BY sort_order ASC, id ASC
");
$stmt->bind_param('i', $itemId);
$stmt->execute();
$images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fallback: if table empty, use the legacy single photo column
if (empty($images) && !empty($item['photo'])) {
    $images = [['filename' => $item['photo']]];
}

$alreadyRequested = false;
if (isUserLoggedIn()) {
    $stmt = $conn->prepare("
        SELECT id
        FROM requests
        WHERE item_id = ? AND requester_id = ? AND status IN ('pending','accepted')
        LIMIT 1
    ");
    $uid = currentUserId();
    $stmt->bind_param('ii', $itemId, $uid);
    $stmt->execute();
    $alreadyRequested = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

/**
 * ✅ Distance display
 * - Only if user logged in AND both sides have coordinates.
 * - If < 1km, show meters.
 */
$distanceText = null;

// do not show distance to owner on owner's own item page
$isOwner = isUserLoggedIn() && currentUserId() === (int)$item['user_id'];

if (isUserLoggedIn() && !$isOwner) {
    $uid = currentUserId();

    // viewer coords
    $stmt = $conn->prepare("SELECT latitude, longitude FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $me = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $meLat = $me['latitude'] ?? null;
    $meLng = $me['longitude'] ?? null;

    // owner coords (from the joined users table)
    $ownerLat = $item['owner_lat'] ?? null;
    $ownerLng = $item['owner_lng'] ?? null;

    if (is_numeric($meLat) && is_numeric($meLng) && is_numeric($ownerLat) && is_numeric($ownerLng)) {
        $km = haversineKm((float)$meLat, (float)$meLng, (float)$ownerLat, (float)$ownerLng);

        if ($km < 1) {
            $meters = (int)round($km * 1000);
            if ($meters < 1 && $km > 0) $meters = 1; // avoid "0 m" for tiny non-zero
            $distanceText = $meters . " m away";
        } else {
            $distanceText = number_format($km, 1) . " km away";
        }
    }
}

/**
 * ✅ Direction section (clickable, exact coordinates from DB)
 */
// after you already fetched $meLat,$meLng and $ownerLat,$ownerLng
$directionUrl = null;
$directionEmbedUrl = null;
$hasExactLocation = false;

if (
    is_numeric($meLat ?? null) && is_numeric($meLng ?? null) &&
    is_numeric($ownerLat ?? null) && is_numeric($ownerLng ?? null)
) {
    $oLat = (string)$meLat;      // viewer saved coords (DB)
    $oLng = (string)$meLng;
    $dLat = (string)$ownerLat;   // owner saved coords (DB)
    $dLng = (string)$ownerLng;

    // Click opens exact route from saved->saved
    $directionUrl = "https://www.google.com/maps/dir/?api=1"
        . "&origin=" . rawurlencode($oLat . "," . $oLng)
        . "&destination=" . rawurlencode($dLat . "," . $dLng)
        . "&travelmode=driving";

    // Embed without API key
    $directionEmbedUrl = "https://www.google.com/maps?q="
        . rawurlencode($dLat . "," . $dLng)
        . "&z=16&output=embed";

    $hasExactLocation = true;
}
/**
 * ✅ Owner review stats
 */
$ownerId = (int)$item['user_id'];
$stmt = $conn->prepare("
    SELECT COUNT(*) AS review_count, AVG(rating) AS avg_rating
    FROM reviews
    WHERE reviewee_id = ?
");
$stmt->bind_param('i', $ownerId);
$stmt->execute();
$ratingRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

$reviewCount = (int)($ratingRow['review_count'] ?? 0);
$avgRating = ($ratingRow['avg_rating'] !== null) ? (float)$ratingRow['avg_rating'] : null;

$pageTitle = h($item['title']) . ' — ShareToNeighbour';
require_once __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/browse.php">Browse</a></li>
        <li class="breadcrumb-item active"><?= h($item['title']) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-md-6">
        <?php if (!empty($images)): ?>
            <div id="itemGallery" class="carousel slide mb-3" data-bs-ride="carousel">
                <div class="carousel-inner rounded shadow-sm overflow-hidden" style="max-height:400px;">
                    <?php foreach ($images as $idx => $img): ?>
                        <div class="carousel-item  <?= $idx === 0 ? 'active' : '' ?>">
                            <a href="<?= UPLOAD_URL . '/' . h($img['filename']) ?>" target="_blank" title="Open image">
                                <img src="<?= UPLOAD_URL . '/' . h($img['filename']) ?>"
                                    class="d-block w-100"
                                    alt="<?= h($item['title']) ?> image <?= $idx + 1 ?>"
                                    style="height:400px;object-fit:cover;">
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($images) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#itemGallery" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#itemGallery" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                <?php endif; ?>
            </div>

            <?php if (count($images) > 1): ?>
                <div class="d-inline-flex gap-2 flex-wrap">
                    <?php foreach ($images as $idx => $img): ?>
                        <button type="button"
                            class="p-0 border-0 bg-transparent"
                            data-bs-target="#itemGallery"
                            data-bs-slide-to="<?= $idx ?>"
                            aria-label="Go to image <?= $idx + 1 ?>">
                            <img src="<?= UPLOAD_URL . '/' . h($img['filename']) ?>"
                                class="rounded border"
                                alt="Thumbnail <?= $idx + 1 ?>"
                                style="width:92px;height:70px;object-fit:cover;">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <img src="<?= UPLOAD_URL . '/placeholder.jpg' ?>"
                class="img-fluid rounded shadow-sm w-100"
                alt="<?= h($item['title']) ?>" style="max-height:500px;object-fit:cover;">
        <?php endif; ?>
    </div>

    <div class="col-md-6">
        
        <h2><?= h($item['title']) ?></h2>
        <div class="d-flex gap-2 mb-3">
            <span class="badge bg-success fs-6"><?= ucfirst($item['category']) ?></span>
            <span class="badge bg-secondary fs-6"><?= ucfirst(str_replace('_', ' ', $item['condition_level'])) ?></span>
            <span class="badge bg-<?= $item['status'] === 'available' ? 'primary' : 'warning' ?> fs-6"><?= ucfirst($item['status']) ?></span>
            <?php if ($distanceText): ?>
                <span class="badge bg-secondary fs-6 bi bi-geo-alt"><?= h($distanceText) ?></span>
            <?php endif; ?>
        </div>

        <p class="lead"><?= nl2br(h($item['description'])) ?></p>
        <hr>

        <div class="row">
        <div class="col-md-6">

        <p class="mb-1"><i class="bi bi-person-circle"></i> <strong><?= h($item['full_name']) ?></strong> (@<?= h($item['username']) ?>)</p>

        <?php if ($reviewCount > 0): ?>
            <div class="d-flex align-items-center gap-2 mb-2">
                <div><?= renderStars($avgRating) ?></div>
                <div class="text-muted small">
                    <?= number_format($avgRating, 1) ?> / 5 (<?= (int)$reviewCount ?> reviews)
                </div>
            </div>
        <?php else: ?>
            <div class="text-muted small mb-2 p-2">No reviews yet</div>
        <?php endif; ?>

        <p class="text-muted small mb-2"><i class="bi bi-clock"></i> Posted <?= date('M j, Y H:i', strtotime($item['created_at'])) ?></p>

         </div>

        <div class="col-md-6 m-0">
        <?php if ((isUserLoggedIn() && currentUserId() !== (int)$item['user_id']) && $hasExactLocation): ?>
            
            <a href="<?= h($directionUrl) ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden p-2" title="Open directions in Google Maps">
                    <div class="p-2 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <div class="fw-semibold text-dark">
                            <i class="bi bi-sign-turn-right me-1"></i> Directions
                        </div>
                    </div>
                    <iframe
                        src="<?= h($directionEmbedUrl) ?>"
                        width="100%"
                        height="auto"
                        style="border:0;pointer-events:none;"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Pickup location map">
                    </iframe>
                </div>
            </a>
        <?php endif; ?>
         </div>
         </div>
        <hr>

        <?php if (isUserLoggedIn() && currentUserId() === (int)$item['user_id']): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> This is your listing.

                <div class="mt-3">
                    <form action="<?= SITE_URL ?>/update_item_status.php" method="POST" class="row g-2 align-items-end">
                        <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">

                        <div class="col-sm-6">
                            <label class="form-label mb-1"><strong>Update Status</strong></label>
                            <select class="form-select" name="status">
                                <option value="available" <?= $item['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                                <option value="requested" <?= $item['status'] === 'requested' ? 'selected' : '' ?>>Requested (Reserved)</option>
                                <option value="taken" <?= $item['status'] === 'taken' ? 'selected' : '' ?>>Taken</option>
                            </select>
                        </div>

                        <div class="col-sm-6 d-flex gap-2">
                            <?php if ($item['status'] === 'taken'): ?>
                                <button type="submit" class="btn btn-primary w-100" disabled>No Longer Change Available</button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-primary w-100"
                                    onclick="
                           const form = this.form;
                           const status = form.status.value;
 
                         if (status === 'taken') {
                         return confirm('Marking this item as Taken means it is no longer available. Are you sure?');
                         }

                         else {
                        return confirm('Are you sure, you want to chnange status? ');
                         }                   
                                             ">
                              <i class="bi bi-save"></i> Save
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif (isUserLoggedIn() && currentUserId() !== (int)$item['user_id']): ?>

            <?php if ($item['status'] === 'available'): ?>

                <?php if ($alreadyRequested): ?>
                    <button class="btn btn-warning btn-lg w-100 mb-2" disabled>
                        <i class="bi bi-hourglass-split"></i> Request Already Sent
                    </button>
                <?php else: ?>
                    <form action="<?= SITE_URL ?>/request_item.php" method="POST" class="mb-2">
                        <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="bi bi-hand-index-thumb"></i> Request This Item
                        </button>
                    </form>
                <?php endif; ?>

                <a href="<?= SITE_URL ?>/send_message.php?to=<?= (int)$item['user_id'] ?>&item=<?= (int)$item['id'] ?>"
                    class="btn btn-outline-primary w-100">
                    <i class="bi bi-chat-dots"></i> Chat with Owner
                </a>

            <?php else: ?>
                <button class="btn btn-secondary btn-lg w-100" disabled>
                    <i class="bi bi-x-circle"></i> No Longer Available (<?= h(ucfirst($item['status'])) ?>)
                </button>
            <?php endif; ?>

        <?php else: ?>

            <div class="alert alert-warning">
                <i class="bi bi-lock"></i> <strong>Login required</strong>
                <p class="mb-2">You must be logged in to request items or chat with the owner.</p>
                <a href="<?= SITE_URL ?>/login.php" class="btn btn-success me-2">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </a>
                <a href="<?= SITE_URL ?>/register.php" class="btn btn-outline-success">
                    <i class="bi bi-person-plus"></i> Register
                </a>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>