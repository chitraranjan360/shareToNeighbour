<?php
// Set page title for the header template
$pageTitle = 'User Details';
require_once __DIR__ . '/admin_header.php';

// Extract and validate user ID from GET parameter
$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
  // Redirect if no valid ID provided
  redirect(ADMIN_URL . '/manage_users.php');
}

// Fetch user details from database
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Redirect if user doesn't exist
if (!$user) {
  redirect(ADMIN_URL . '/manage_users.php');
}

// Fetch review statistics for the user
$stmt = $conn->prepare("
  SELECT
    COUNT(*) AS review_count,
    SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) AS bad_count,
    AVG(rating) AS avg_rating
  FROM reviews
  WHERE reviewee_id = ?
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$sum = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Calculate review metrics
$reviewCount = (int)($sum['review_count'] ?? 0);
$badCount    = (int)($sum['bad_count'] ?? 0);
$avgRating   = ($sum['avg_rating'] !== null) ? (float)$sum['avg_rating'] : null;
$badPercent  = ($reviewCount > 0) ? ($badCount / $reviewCount) * 100 : 0;

// Fetch all reviews received by this user with reviewer and item details
$stmt = $conn->prepare("
  SELECT
    r.id, r.rating, r.comment, r.image, r.created_at, r.request_id, r.item_id,
    ru.username AS reviewer_username,
    ru.full_name AS reviewer_name,
    fi.title AS item_title
  FROM reviews r
  JOIN users ru ON ru.id = r.reviewer_id
  LEFT JOIN furniture_items fi ON fi.id = r.item_id
  WHERE r.reviewee_id = ?
  ORDER BY r.created_at DESC
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check if user account is active
$isActive = ((int)($user['is_active'] ?? 1) === 1);
?>

<!-- Hero section with user header and overview -->
<section class="admin-hero rounded-4 p-4 p-md-5 mb-4 shadow-sm">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
    <div>
      <!-- Back navigation link -->
      <a class="admin-back-link text-decoration-none" href="<?= ADMIN_URL ?>/manage_users.php">
        <i class="bi bi-arrow-left"></i> Back to users
      </a>

      <!-- User profile header with avatar, username, and join date -->
      <div class="d-flex align-items-center gap-3 mt-2">
        <div class="admin-user-avatar">
          <i class="bi bi-person-badge"></i>
        </div>
        <div>
          <h1 class="h3 mb-1"><?= h($user['username']) ?></h1>
          <div class="text-muted">
            <?= h($user['full_name'] ?: '—') ?>
            <span class="mx-2">•</span>
            Joined <?= date('M j, Y', strtotime($user['created_at'])) ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick info chips: email, status, and rating -->
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-lg-end">
      <span class="chip">
        <i class="bi bi-envelope"></i> <?= h($user['email']) ?>
      </span>

      <!-- Account status badge -->
      <?php if ($isActive): ?>
        <span class="chip chip-success"><i class="bi bi-check-circle"></i> Active</span>
      <?php else: ?>
        <span class="chip chip-secondary"><i class="bi bi-slash-circle"></i> Disabled</span>
      <?php endif; ?>

      <!-- Average rating display -->
      <span class="chip chip-primary">
        <i class="bi bi-star-half"></i>
        <?= $avgRating === null ? '—' : number_format($avgRating, 1) ?>/5
        <span class="text-muted">(<?= $reviewCount ?>)</span>
      </span>
    </div>
  </div>
</section>

<div class="row g-4">
  <!-- Left sidebar: User info and moderation controls -->
  <div class="col-lg-4">
    <!-- User information card -->
    <div class="admin-glass card border-0 rounded-4">
      <div class="card-body p-4">
        <!-- Card header with title and user ID -->
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h5 class="mb-0"><i class="bi bi-info-circle"></i> User Info</h5>
          <span class="badge rounded-pill admin-pill"><?= (int)$user['id'] ?></span>
        </div>

        <!-- Key-value pairs for user details -->
        <div class="admin-kv">
          <div class="admin-kv-row">
            <div class="k">Email</div>
            <div class="v"><?= h($user['email']) ?></div>
          </div>

          <div class="admin-kv-row">
            <div class="k">Address</div>
            <div class="v"><?= h($user['address'] ?? '—') ?></div>
          </div>

          <div class="admin-kv-row">
            <div class="k">Account</div>
            <div class="v">
              <!-- Display account status badge -->
              <?php if ($isActive): ?>
                <span class="badge bg-success">Active</span>
              <?php else: ?>
                <span class="badge bg-secondary">Disabled</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Show disabled date if account is disabled -->
          <?php if (!$isActive && !empty($user['disabled_at'])): ?>
            <div class="admin-kv-row">
              <div class="k">Disabled at</div>
              <div class="v"><?= date('M j, Y H:i', strtotime($user['disabled_at'])) ?></div>
            </div>
          <?php endif; ?>

          <!-- Show disable reason if account is disabled -->
          <?php if (!$isActive && !empty($user['disabled_reason'])): ?>
            <div class="admin-kv-row">
              <div class="k">Reason</div>
              <div class="v"><?= h($user['disabled_reason']) ?></div>
            </div>
          <?php endif; ?>
        </div>

        <hr class="my-4">

        <!-- Reputation statistics section -->
        <h6 class="mb-3 text-uppercase small text-muted"><i class="bi bi-shield-exclamation"></i> Reputation</h6>

        <div class="row g-3">
          <!-- Total review count -->
          <div class="col-6">
            <div class="mini-stat">
              <div class="mini-stat-label">Total</div>
              <div class="mini-stat-value"><?= $reviewCount ?></div>
            </div>
          </div>
          <!-- Bad reviews count (rating <= 2) -->
          <div class="col-6">
            <div class="mini-stat mini-stat-danger">
              <div class="mini-stat-label">Bad (≤2)</div>
              <div class="mini-stat-value"><?= $badCount ?></div>
            </div>
          </div>
          <!-- Percentage of bad reviews -->
          <div class="col-6">
            <div class="mini-stat mini-stat-warning">
              <div class="mini-stat-label">Bad %</div>
              <div class="mini-stat-value"><?= number_format($badPercent, 0) ?>%</div>
            </div>
          </div>
          <!-- Average rating score -->
          <div class="col-6">
            <div class="mini-stat mini-stat-primary">
              <div class="mini-stat-label">Avg</div>
              <div class="mini-stat-value"><?= $avgRating === null ? '—' : number_format($avgRating, 1) ?></div>
            </div>
          </div>
        </div>

        <!-- Flag alert for users with 80%+ bad reviews -->
        <?php if ($reviewCount >= 5 && $badPercent >= 80): ?>
          <div class="alert alert-danger border-0 mt-3 mb-0 rounded-4">
            <i class="bi bi-flag-fill"></i> Flagged: 80%+ bad reviews (≥5 reviews)
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Moderation card for disabling/enabling accounts -->
    <div class="admin-glass card border-0 rounded-4 mt-4">
      <div class="card-body p-4">
        <h5 class="mb-3"><i class="bi bi-slash-circle"></i> Moderation</h5>

        <!-- Show disable form if account is active -->
        <?php if ($isActive): ?>
          <form method="POST" action="<?= ADMIN_URL ?>/user_status.php" onsubmit="return confirm('Disable this account?');">
            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
            <input type="hidden" name="action" value="disable">

            <label class="form-label small">Reason (required)</label>
            <input class="form-control mb-2" name="reason" required maxlength="255"
                   placeholder="e.g. abusive behavior, repeated spam, 80% bad reviews">

            <button class="btn btn-danger w-100 admin-action-btn">
              <i class="bi bi-lock-fill"></i> Disable permanently
            </button>
          </form>
        <?php else: ?>
          <!-- Show enable form if account is disabled -->
          <form method="POST" action="<?= ADMIN_URL ?>/user_status.php" onsubmit="return confirm('Re-enable this account?');">
            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
            <input type="hidden" name="action" value="enable">

            <button class="btn btn-success w-100 admin-action-btn">
              <i class="bi bi-unlock-fill"></i> Re-enable account
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right main content: Reviews table -->
  <div class="col-lg-8">
    <div class="admin-glass card border-0 rounded-4">
      <div class="card-body p-4">
        <!-- Section header with review count -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <h5 class="mb-1"><i class="bi bi-star-half"></i> Reviews Received</h5>
            <div class="text-muted small">All feedback left for this user.</div>
          </div>
          <span class="badge rounded-pill admin-pill"><?= count($reviews) ?></span>
        </div>

        <!-- Show empty state if no reviews exist -->
        <?php if (empty($reviews)): ?>
          <div class="text-muted">No reviews for this user yet.</div>
        <?php else: ?>
          <!-- Reviews table -->
          <div class="table-responsive">
            <table class="table table-hover align-middle admin-table rounded">
              <thead>
                <tr>
                  <th style="width: 110px;">Rating</th>
                  <th>Comment</th>
                  <th style="width: 180px;">Reviewer</th>
                  <th style="width: 180px;">Item</th>
                  <th style="width: 130px;">Date</th>
                  <th style="width: 180px;">Image (optional)</th>
                </tr>
              </thead>
              <tbody>
              <!-- Loop through and display each review -->
              <?php foreach ($reviews as $r): ?>
                <?php $isBad = ((int)$r['rating'] <= 2); ?>
                <tr>
                  <!-- Rating badge (red for bad, green for good) -->
                  <td>
                    <span class="badge rounded-pill <?= $isBad ? 'text-bg-danger' : 'text-bg-success' ?>">
                      <?= (int)$r['rating'] ?>/5
                    </span>
                  </td>
                  <!-- Review comment with 2-line truncation -->
                  <td class="small">
                    <div class="text-truncate-2"><?= h($r['comment'] ?: '—') ?></div>
                  </td>
                  <!-- Reviewer name and username -->
                  <td class="small">
                    <div class="fw-semibold"><?= h($r['reviewer_username']) ?></div>
                    <?php if (!empty($r['reviewer_name'])): ?>
                      <div class="text-muted"><?= h($r['reviewer_name']) ?></div>
                    <?php endif; ?>
                  </td>
                  <!-- Item title or fallback ID -->
                  <td class="small">
                    <?= h($r['item_title'] ?: ('Item #' . (int)$r['item_id'])) ?>
                  </td>
                  <!-- Review creation date -->
                  <td class="small"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                  <!-- Image (optional) -->
                  <td class="small">
                    <?php if (!empty($r['image'])): ?>
                      <a href="<?= h(SITE_URL . '/' . ltrim($r['image'], '/')) ?>" target="_blank" rel="noopener noreferrer" title="Open full image">
                        <img src="<?= h(SITE_URL . '/' . ltrim($r['image'], '/')) ?>" alt="Review Image" class="img-fluid rounded" style="max-height: 50px;">
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>

            </div>
            <!-- User's Items -->
             <hr>
             <div class="table-responsive">
              <h5 class="mb-3"><i class="bi bi-box-seam"></i> User's Items</h5>
             
            <table class="table table-hover align-middle admin-table rounded">

              <thead>
                <tr>
                  <th style="width: 60px;">ID</th>
                  <th style="width: 60px;">Photo</th>
                  <th>Title</th>
                  <th style="width: 120px;">Category</th>
                  <th style="width: 120px;">Status</th>
                  <th style="width: 130px;">Posted</th>
                </tr>
              </thead>
              <tbody>
              <?php
              // Fetch items posted by this user
              $stmt = $conn->prepare("SELECT id, title, category, photo, status, created_at FROM furniture_items WHERE user_id = ? ORDER BY created_at DESC");
              $stmt->bind_param('i', $userId);
              $stmt->execute();
              $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
              $stmt->close();

              // Display each item or show empty state if none exist
              if (empty($items)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No items posted by this user.</td></tr>
              <?php else: foreach ($items as $item): ?>
                <tr>
                  <td class="fw-semibold"><?= (int)$item['id'] ?></td>
                  <td><img src="<?= UPLOAD_URL . '/' . h($item['photo'] ?: 'placeholder.jpg') ?>" alt="<?= h($item['title']) ?>" class="rounded-3 border" style="width:56px;height:56px;object-fit:cover;"></td>
                  <td><?= h($item['title']) ?></td>
                  <td><span class="badge text-bg-success-subtle border border-success-subtle text-success-emphasis"><?= h(ucfirst($item['category'])) ?></span></td>
                  <td><span class="badge bg-<?= $item['status']==='available'?'primary':($item['status']==='requested'?'warning text-dark':'secondary') ?>"><?= h(ucfirst($item['status'])) ?></span></td>
                  <td><small class="text-body-secondary"><?= date('M j, Y', strtotime($item['created_at'])) ?></small></td>
                </tr>
              <?php endforeach; endif; ?>
              </tbody>
            </table>
            
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>