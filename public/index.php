<?php
$pageTitle = 'ShareToNeighbour — Share Furniture with Your Neighbours';
require_once __DIR__ . '/../includes/header.php';
// Check if user has pending review to write (after accepted request + taken item, but no review yet)
$forceReview = false;
$pendingReview = null;

if (isUserLoggedIn()) {
    $uid = currentUserId();

    $stmt = $conn->prepare("
        SELECT
            r.id AS request_id,
            r.item_id,
            fi.title,
            fi.user_id AS owner_id
        FROM requests r
        JOIN furniture_items fi ON fi.id = r.item_id
        LEFT JOIN reviews rev
            ON rev.request_id = r.id AND rev.reviewer_id = ?
        WHERE r.requester_id = ?
          AND r.status = 'accepted'
          AND fi.status = 'taken'
          AND fi.is_deleted = 0
          AND rev.id IS NULL
        ORDER BY fi.taken_at DESC, fi.id DESC
        LIMIT 1
    ");
    $stmt->bind_param('ii', $uid, $uid);
    $stmt->execute();
    $pendingReview = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $forceReview = !empty($pendingReview);
}

// Load logged-in user location (if any)
$userLat = $userLng = null;

if (isUserLoggedIn()) {
    $uid = currentUserId();
    $stmt = $conn->prepare("SELECT latitude, longitude FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $loc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $userLat = isset($loc['latitude']) ? (float)$loc['latitude'] : null;
    $userLng = isset($loc['longitude']) ? (float)$loc['longitude'] : null;
}

// Latest items (homepage default: within 1 km when we can calculate distance)
if ($userLat !== null && $userLng !== null) {
    $uid = currentUserId();

    $stmt = $conn->prepare("
    SELECT
        fi.*,
        u.username,
        (
          6371 * acos(
            cos(radians(?)) * cos(radians(u.latitude)) *
            cos(radians(u.longitude) - radians(?)) +
            sin(radians(?)) * sin(radians(u.latitude))
          )
        ) AS distance_km
    FROM furniture_items fi
    JOIN users u ON fi.user_id = u.id
    WHERE fi.is_deleted = 0 AND
    fi.status IN ('available','requested','taken')
      AND fi.user_id <> ?
      AND u.latitude IS NOT NULL AND u.longitude IS NOT NULL
    HAVING distance_km <= 1
    ORDER BY fi.created_at DESC
    LIMIT 9
");
    $stmt->bind_param('dddi', $userLat, $userLng, $userLat, $uid);
} else {
    // Fallback: not logged in OR no location saved yet
    $stmt = $conn->prepare("
        SELECT fi.*, u.username, NULL AS distance_km
        FROM furniture_items fi
        JOIN users u ON fi.user_id = u.id
        WHERE fi.is_deleted = 0 AND 
        fi.status IN ('available','requested','taken')
        ORDER BY fi.created_at DESC
        LIMIT 9
    ");
}

$stmt->execute();
$latest = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<section class="hero-premium text-white rounded-4 p-5 p-lg-6 mb-5 shadow-sm">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <p class="text-uppercase small fw-semibold text-white-50 mb-2">Neighbour-to-Neighbour Sharing</p>
            <h1 class="display-5 fw-bold mb-3">
                <i class="bi bi-house-heart-fill"></i> Share Furniture, Build Community
            </h1>
            <p class="lead text-white-75 mb-4">
                Pass on what you don’t need. Discover what you do. Keep it local, circular, and friendly.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <span class="pill-soft pill-primary"><i class="bi bi-geo-alt"></i> Copenhagen locals</span>
                <span class="pill-soft pill-success"><i class="bi bi-recycle"></i> Circular living</span>
                <span class="pill-soft pill-secondary"><i class="bi bi-chat-dots"></i> Built-in chat</span>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="glass-card h-100">
                <h5 class="mb-3 text-white-75">Why ShareToNeighbour?</h5>
                <ul class="list-unstyled text-white-75 mb-0">
                    <li class="d-flex align-items-start gap-2 mb-2">
                        <i class="bi bi-check2-circle text-success"></i>
                        <span>Free to use—no fees.</span>
                    </li>
                    <li class="d-flex align-items-start gap-2 mb-2">
                        <i class="bi bi-check2-circle text-success"></i>
                        <span>Stay within 1 km of your home.</span>
                    </li>
                    <li class="d-flex align-items-start gap-2 mb-2">
                        <i class="bi bi-check2-circle text-success"></i>
                        <span>Request items or chat instantly.</span>
                    </li>
                </ul>
                <?php if (!isUserLoggedIn()): ?>
                    <div class="d-flex gap-2 mt-3">
                        <a href="<?= SITE_URL ?>/register.php" class="btn btn-success flex-fill">
                            <i class="bi bi-person-plus"></i> Join now
                        </a>
                        <a href="<?= SITE_URL ?>/browse.php" class="btn btn-outline-light flex-fill">
                            <i class="bi bi-search"></i> Browse items
                        </a>
                    </div>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>/upload.php" class="btn btn-success w-100 mt-3">
                        <i class="bi bi-plus-circle"></i> Share an item
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if (!isUserLoggedIn()): ?>
    <section class="mb-5">
        <h2 class="text-center mb-4 text-dark"><i class="bi bi-arrow-repeat"></i> How It Works</h2>
        <div class="row g-4 text-center">
            <?php
            $steps = [
                ['icon' => 'person-plus', 'title' => 'Register & Login', 'desc' => 'Create your free account to start sharing.'],
                ['icon' => 'camera', 'title' => 'Share or Browse', 'desc' => 'Upload furniture to give away or explore nearby finds.'],
                ['icon' => 'chat-heart', 'title' => 'Chat & Collect', 'desc' => 'Message the owner, agree on pick-up, give it a second life.'],
            ];
            foreach ($steps as $step): ?>
                <div class="col-md-4">
                    <div class="glass-card h-100 text-dark">
                        <div class="display-5 text-success mb-3"><i class="bi bi-<?= $step['icon'] ?>"></i></div>
                        <h5><?= $step['title'] ?></h5>
                        <p class="text-muted mb-0"><?= $step['desc'] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="bi bi-clock-history"></i> Latest Shared Items</h2>
        <a href="<?= SITE_URL ?>/browse.php" class="btn btn-outline-success btn-sm">View All &rarr;</a>
    </div>

    <?php if (isUserLoggedIn() && ($userLat === null || $userLng === null)): ?>
        <div class="alert alert-warning">
            To see items within <strong>1 km</strong> and accurate distance, please set your address in
            <a href="<?= SITE_URL ?>/edit_profile.php" class="alert-link">Edit Profile</a>.
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if (empty($latest)): ?>
            <p class="text-muted text-center">
                <?php if ($userLat !== null && $userLng !== null): ?>
                    No items found within 1 km yet. Try <a href="<?= SITE_URL ?>/browse.php">View All</a>.
                <?php else: ?>
                    No furniture shared yet. Be the first!
                <?php endif; ?>
            </p>
        <?php else: ?>
            <?php foreach ($latest as $item):
                $status = $item['status'] ?? 'available';
                $statusClass = match ($status) {
                    'available' => 'bg-primary',
                    'requested' => 'bg-warning',
                    'taken'     => 'bg-danger',
                    default     => 'bg-secondary'
                };
            ?>
                <div class="col-md-4">
                    <a class="card h-100 shadow-sm item-card-link text-decoration-none" href="<?= SITE_URL ?>/item.php?id=<?= (int)$item['id'] ?>">
                        <div class="item-card-img rounded-4 overflow-hidden">
                            <img src="<?= UPLOAD_URL . '/' . h($item['photo'] ?: 'placeholder.jpg') ?>"
                                class="w-100 h-100 object-fit-cover" alt="<?= h($item['title']) ?>">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex gap-2 mb-2 flex-wrap">
                                <span class="badge bg-success"><?= h(ucfirst($item['category'])) ?></span>
                                <span class="badge bg-secondary"><?= h(ucfirst(str_replace('_', ' ', $item['condition_level']))) ?></span>
                                <span class="badge <?= $statusClass ?>"><?= h(ucfirst($status)) ?></span>

                                <?php if ($item['distance_km'] !== null): ?>
                                    <span class="badge bg-primary">
                                        <i class="bi bi-geo-alt"></i>
                                        <?php
                                        $km = (float)$item['distance_km'];
                                        if ($km < 1) {
                                            echo (int)round($km * 1000) . " m";
                                        } else {
                                            echo number_format($km, 1) . " km";
                                        }
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h5 class="card-title text-dark mb-1"><?= h($item['title']) ?></h5>
                            <p class="card-text text-muted small flex-grow-1 mb-2">
                                <?= h(mb_strimwidth($item['description'], 0, 110, '…')) ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center text-muted small">
                                <span><i class="bi bi-person"></i> <?= h($item['username']) ?></span>

                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<section class="glass-card p-5 mb-5">
    <div class="row align-items-center g-4">
        <div class="col-md-7">
            <h2 class="mb-3"><i class="bi bi-heart-fill text-danger"></i> About ShareToNeighbour</h2>
            <p class="text-muted mb-3">We help Copenhagen residents share unused furniture with neighbours within walking distance. Reduce waste, save money, and build community connections.</p>
            <ul class="list-unstyled mb-0 text-muted">
                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> 100% free — no fees ever</li>
                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Local — within 1 km of your home</li>
                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Sustainable — reuse instead of landfill</li>
                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Register to share or chat</li>
            </ul>
        </div>
        <div class="col-md-5 text-center">
            <div class="display-1 text-success"><i class="bi bi-recycle"></i></div>
        </div>
    </div>
</section>
<!--  review modal (if needed) -->
<?php if ($forceReview && $pendingReview): ?>
    <style>
        .review-stars .btn-check { display: none; }
        .review-star-btn {
            width: 44px; height: 44px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0; border-width: 1px; font-size: 1rem;
            transition: transform .12s ease, box-shadow .12s ease;
        }
        .review-star-btn .bi { color: #ffc107; opacity: .28; font-size: 1.05rem; }
        .review-stars .btn-check:checked + .review-star-btn .bi { opacity: 1; transform: scale(1.06); }
        .review-star-btn:hover { transform: scale(1.05); box-shadow: 0 2px 6px rgba(0,0,0,.08); }
        .review-stars { gap: .5rem; }
    </style>

    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="<?= SITE_URL ?>/submit_review.php" enctype="multipart/form-data" class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Review your transaction</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p class="small text-muted mb-2">We’d appreciate a brief rating to help other neighbours make informed choices.</p>
                    <div class="small fw-semibold mb-3"><?= h($pendingReview['title']) ?></div>

                    <input type="hidden" name="item_id" value="<?= (int)$pendingReview['item_id'] ?>">
                    <input type="hidden" name="request_id" value="<?= (int)$pendingReview['request_id'] ?>">
                    <input type="hidden" name="reviewee_id" value="<?= (int)$pendingReview['owner_id'] ?>">

                    <div class="mb-3">
                        <label class="form-label small mb-2 d-block">Your rating *</label>
                        <div class="review-stars d-flex" role="radiogroup" aria-label="Rating">
                            <?php for ($star = 5; $star >= 1; $star--): ?>
                                <input class="btn-check" type="radio" name="rating" id="reviewStar<?= $star ?>" value="<?= $star ?>" required>
                                <label class="btn btn-outline-warning review-star-btn" for="reviewStar<?= $star ?>" title="<?= $star ?> star<?= $star > 1 ? 's' : '' ?>">
                                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                                    <span class="visually-hidden"><?= $star ?> star<?= $star > 1 ? 's' : '' ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small mb-1">Comment (optional)</label>
                        <textarea class="form-control form-control-sm" name="comment" rows="2" maxlength="500" placeholder="Share any details that would help others"></textarea>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small mb-1">Photo (optional)</label>
                        <input type="file" name="photo" accept="image/*" class="form-control form-control-sm mt-2" placeholder="Attach a photo only if it supports your feedback (e.g. damage)">
                    </div>
                </div>

                <div class="modal-footer py-2 d-flex flex-column gap-2">
                    <button type="submit" class="btn btn-success btn-sm w-100">Submit review</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" data-bs-dismiss="modal">Remind me later</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modalEl = document.getElementById('reviewModal');
            if (!modalEl) return;
            const modal = new bootstrap.Modal(modalEl, { backdrop: true, keyboard: true });
            modal.show();
        });
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>