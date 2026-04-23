<?php
$pageTitle = 'My Profile — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

$uid = currentUserId();

// Fetch user's review stats (use $uid)
$stmt = $conn->prepare("
  SELECT 
    COUNT(*) AS review_count,
    AVG(rating) AS avg_rating
  FROM reviews
  WHERE reviewee_id = ?
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$ratingRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

$reviewCount = (int)($ratingRow['review_count'] ?? 0);
$avgRating = $ratingRow['avg_rating'] !== null ? (float)$ratingRow['avg_rating'] : null;

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch user's listings stats
$stmt = $conn->prepare("SELECT status FROM furniture_items WHERE user_id = ?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalListings = count($rows);
$availableCount = count(array_filter($rows, fn($i) => ($i['status'] ?? '') === 'available'));
$requestedCount = count(array_filter($rows, fn($i) => ($i['status'] ?? '') === 'requested'));
$takenCount = count(array_filter($rows, fn($i) => ($i['status'] ?? '') === 'taken'));

require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="css/profile.css">
<section class="profile-hero text-light rounded-4 p-4 p-md-5 mb-4 shadow-sm">
  <div class="hero-options dropdown rounded bg-white bg-opacity-50 shadow-sm">
    <button class="btn btn-sm btn-outline-secondary" type="button"
      data-bs-toggle="dropdown">
      <i class="bi bi-three-dots-vertical"></i>
    </button>
    <ul class="dropdown-menu rounded shadow-extra-sm ">
      <li><a class="dropdown-item" href="<?= SITE_URL ?>/edit_profile.php">Edit Profile</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="<?= SITE_URL ?>/change_password.php">Change Password</a></li>
    </ul>
  </div>

  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
      <div class="profile-avatar">
        <i class="bi bi-person-circle"></i>
      </div>
      <div>
        <p class="text-uppercase small fw-semibold text-white-50 mb-1">My Profile</p>
        <h1 class="h3 mb-1"><?= h($user['full_name']) ?></h1>
        <div class="text-white-75 d-flex flex-wrap gap-3 small">
          <span><i class="bi bi-at"></i> <?= h($user['username']) ?></span>
          <span><i class="bi bi-calendar3"></i> Joined <?= date('M j, Y', strtotime($user['created_at'])) ?></span>
          <?php if (!empty($user['address'])): ?>
            <span><i class="bi bi-geo-alt"></i> <?= h($user['address']) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="glass-card p-4 h-100">
      <h5 class="mb-3 d-flex align-items-center gap-2">
        <span class="icon-bubble"><i class="bi bi-shield-check"></i></span>
        Account
      </h5>

      <div class="profile-info">
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-opacity-10">
          <div class="text-muted small">Email</div>
          <div class="fw-semibold"><?= h($user['email']) ?></div>
        </div>

        <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-opacity-10">
          <div class="text-muted small">Username</div>
          <div class="fw-semibold">@<?= h($user['username']) ?></div>
        </div>

        <?php if (!empty($user['address'])): ?>
          <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-opacity-10">
            <div class="text-muted small">Address</div>
            <div class="fw-semibold text-end"><?= h($user['address']) ?></div>
          </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center py-2">
          <div class="text-muted small">Member since</div>
          <div class="fw-semibold"><?= date('M j, Y', strtotime($user['created_at'])) ?></div>
        </div>
      </div>

      <hr class="opacity-25 my-3">

      <h6 class="mb-2 text-muted text-uppercase small">Reputation</h6>
      <?php if ($reviewCount > 0): ?>
        <div class="d-flex align-items-center gap-2">
          <div class="rating-stars"><?= renderStars($avgRating) ?></div>
          <div class="text-muted small">
            <span class="fw-semibold text-dark"><?= number_format($avgRating, 1) ?></span> / 5
            <span class="mx-1">•</span>
            <?= (int)$reviewCount ?> reviews
          </div>
        </div>
      <?php else: ?>
        <div class="text-muted small">No reviews yet</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="row g-4">
      <div class="col-md-6 col-xl-3">
        <div class="stat-tile">
          <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-grid"></i></div>
          <div>
            <div class="stat-number"><?= (int)$totalListings ?></div>
            <div class="stat-label">Total Listings</div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-xl-3">
        <div class="stat-tile">
          <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-check2-circle"></i></div>
          <div>
            <div class="stat-number"><?= (int)$availableCount ?></div>
            <div class="stat-label">Available</div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-xl-3">
        <div class="stat-tile">
          <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-hourglass-split"></i></div>
          <div>
            <div class="stat-number"><?= (int)$requestedCount ?></div>
            <div class="stat-label">Requested</div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-xl-3">
        <div class="stat-tile">
          <div class="stat-icon bg-dark-subtle text-dark"><i class="bi bi-box-seam"></i></div>
          <div>
            <div class="stat-number"><?= (int)$takenCount ?></div>
            <div class="stat-label">Taken</div>
          </div>
        </div>
      </div>
    </div>

    <div class="glass-card p-4 mt-4">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-1"><i class="bi bi-lightning-charge text-success"></i> Quick Actions</h5>
          <div class="text-muted small">Manage your account and your listings faster.</div>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= SITE_URL ?>/browse.php" class="btn btn-outline-success">
            <i class="bi bi-search"></i> Browse
          </a>
          <a href="<?= SITE_URL ?>/messages.php" class="btn btn-outline-primary">
            <i class="bi bi-chat-dots"></i> Messages
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>