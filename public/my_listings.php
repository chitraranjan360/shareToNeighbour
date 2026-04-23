<?php
$pageTitle = 'My Listings — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

$uid = currentUserId();

$stmt = $conn->prepare("SELECT * FROM furniture_items WHERE 
 user_id = ? 
 AND is_deleted = 0
 ORDER BY created_at DESC");
$stmt->bind_param('i', $uid);
$stmt->execute();
$myItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="css/my_listing.css">
<link rel="stylesheet" href="css/profile.css">
<section class="profile-hero bg-primary text-light rounded-4 p-4 p-md-5 mb-4 shadow-sm">
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
    <div>
      <p class="text-uppercase small fw-semibold mb-1 text-white-50">Dashboard</p>
      <h1 class="h3 mb-1"><i class="bi bi-grid"></i> My Listings</h1>
      <div class="text-white-75 small">Here is a list of your shared items.</div>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= SITE_URL ?>/upload.php" class="btn btn-light btn-lg">
        <i class="bi bi-plus-circle"></i> New Listing
      </a>
      <a href="<?= SITE_URL ?>/browse.php" class="btn btn-outline-light btn-lg">
        <i class="bi bi-search"></i> Browse
      </a>
    </div>
  </div>
</section>

<?php if (empty($myItems)): ?>
  <div class="glass-card p-5 text-center">
    <div class="display-1 text-success"><i class="bi bi-box-seam"></i></div>
    <h4 class="mt-3">No listings yet</h4>
    <p class="text-muted mb-4">Share your first item and help neighbours reuse furniture.</p>
    <a href="<?= SITE_URL ?>/upload.php" class="btn btn-success btn-lg">
      <i class="bi bi-plus-circle"></i> Share Now
    </a>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($myItems as $item): ?>
      <?php
      $status = $item['status'] ?? 'available';
      $badgeClass = match ($status) {
        'available' => 'pill-primary',
        'requested' => 'pill-warning',
        'taken'     => 'pill-danger',
        default     => 'pill-secondary'
      };
      ?>
      <div class="col-12 col-md-6 col-lg-4">
        <div class="listing-card-wrap h-100">
          <!--  clickable card -->
          <a class="listing-card-link text-decoration-none" href="<?= SITE_URL ?>/item.php?id=<?= (int)$item['id'] ?>">
            <div class="listing-card-img">
              <img src="<?= UPLOAD_URL . '/' . h($item['photo'] ?: 'placeholder.jpg') ?>"
                alt="<?= h($item['title']) ?>"
                class="w-100 h-100 object-fit-cover">
            </div>

            <div class="p-3">
              <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <h6 class="mb-0 text-dark text-truncate pe-2"><?= h($item['title']) ?></h6>
                <span class="pill-soft <?= $badgeClass ?>"><?= h(ucfirst($status)) ?></span>
              </div>

              <p class="text-muted small mb-3">
                <?= h(mb_strimwidth($item['description'] ?? '', 0, 90, '…')) ?>
              </p>

            </div>
          </a>

          <!-- Options dropdown (three dots) -->
          <div class="listing-actions dropdown">
            <button class="btn btn-light btn-sm rounded shadow-sm"
              type="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
              aria-label="Listing options"
              onclick="event.stopPropagation();">
              <i class="bi bi-three-dots-vertical"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow-extra-sm rounded-3 ">
              <li>
                <a class="dropdown-item" href="<?= SITE_URL ?>/edit_item.php?id=<?= (int)$item['id'] ?>">
                  <i class="bi bi-pencil me-2"></i> Edit
                </a>
              </li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li>
                <form action="<?= SITE_URL ?>/delete_item.php"
                  method="POST"
                  onsubmit="return confirm('Delete this listing permanently?');">
                  <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                  <button type="submit" class="dropdown-item text-danger">
                    <i class="bi bi-trash me-2"></i> Delete
                  </button>
                </form>
              </li>
            </ul>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>