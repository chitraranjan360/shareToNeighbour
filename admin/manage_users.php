<?php
$pageTitle = 'Manage Users';
require_once __DIR__ . '/admin_header.php';

$search = trim($_GET['q'] ?? '');

$sql = "
SELECT
  u.id, u.username, u.full_name, u.email, u.address, u.created_at,
  u.is_active, u.disabled_at, u.disabled_reason,

  (SELECT COUNT(*) FROM furniture_items WHERE user_id = u.id) AS item_count,

  COUNT(r.id) AS review_count,
  COALESCE(SUM(CASE WHEN r.rating <= 2 THEN 1 ELSE 0 END), 0) AS bad_count,
  AVG(r.rating) AS avg_rating,
  CASE
    WHEN COUNT(r.id) = 0 THEN 0
    ELSE (SUM(CASE WHEN r.rating <= 2 THEN 1 ELSE 0 END) / COUNT(r.id)) * 100
  END AS bad_percent

FROM users u
LEFT JOIN reviews r ON r.reviewee_id = u.id
";

$params = [];
$types  = '';

if ($search !== '') {
  $sql .= " WHERE u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?";
  $like = '%' . $search . '%';
  $params = [$like, $like, $like];
  $types  = 'sss';
}

$sql .= "
GROUP BY u.id
ORDER BY bad_percent DESC, review_count DESC, u.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function badge($text, $class)
{
  return '<span class="badge ' . $class . '">' . h($text) . '</span>';
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
  <div>
    <h2 class="mb-1"><i class="bi bi-people-fill text-primary"></i> Manage Users</h2>
    <div class="text-muted small">Reputation & moderation (auto-flag at 80% bad reviews)</div>
  </div>
  <span class="badge text-bg-primary-subtle border border-primary-subtle text-primary-emphasis fs-6"><?= count($users) ?> users</span>
</div>

<form id="adminManageUsersSearchForm" method="GET" class="card border-0 shadow-sm rounded-4 p-3 mb-4">
  <div class="input-group shadow-sm">
    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
    <input type="text" name="q" class="form-control" placeholder="Search name, username, email…" value="<?= h($search) ?>">
    <button type="submit" id="adminManageUsersSearchBtn" class="btn btn-primary">Search</button>
    <?php if ($search): ?>
      <a href="<?= ADMIN_URL ?>/manage_users.php" class="btn btn-outline-secondary">Clear</a>
    <?php endif; ?>
  </div>
</form>

<script src="<?= SITE_URL ?>/js/manage-users-search.js"></script>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
<div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th>ID</th>
        <th>User</th>
        <th>Email</th>
        <th>Status</th>
        <th>Items</th>
        <th>Reviews</th>
        <th>Bad %</th>
        <th>Joined</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>

    <tbody>
      <?php if (empty($users)): ?>
        <tr>
          <td colspan="9" class="text-center text-muted py-4">No users found.</td>
        </tr>
        <?php else: foreach ($users as $u): ?>
          <?php
          $reviewCount = (int)$u['review_count'];
          $badPercent  = (float)$u['bad_percent'];
          $avgRating   = ($u['avg_rating'] !== null) ? (float)$u['avg_rating'] : null;

          // Flag rule: 80%+ bad AND at least 5 reviews (avoid false positives)
          $isFlagged = ($reviewCount >= 5 && $badPercent >= 80.0);
          ?>
            <tr class="<?= $isFlagged ? 'table-warning' : '' ?>">
              <td><?= (int)$u['id'] ?></td>

              <td>
                <div class="fw-semibold">
                  <a class="text-decoration-none" href="<?= ADMIN_URL ?>/user_details.php?id=<?= (int)$u['id'] ?>">
                    <?= h($u['username']) ?>
                  </a>
                  <?php if ($isFlagged): ?>
                    <span class="badge bg-danger ms-2"><i class="bi bi-flag-fill"></i> FLAG 80%+</span>
                  <?php endif; ?>
                </div>
                <div class="text-muted small"><?= h($u['full_name'] ?: '—') ?></div>
              </td>

              <td class="small"><?= h($u['email']) ?></td>

              <td>
                <?php if ((int)$u['is_active'] === 1): ?>
                  <?= badge('Active', 'bg-success') ?>
                <?php else: ?>
                  <?= badge('Disabled', 'bg-secondary') ?>
                  <?php if (!empty($u['disabled_at'])): ?>
                    <div class="text-muted small">Since <?= date('M j, Y', strtotime($u['disabled_at'])) ?></div>
                  <?php endif; ?>
                <?php endif; ?>
              </td>

              <td><?= badge((string)(int)$u['item_count'], 'bg-info text-dark') ?></td>

              <td>
                <div class="small">
                  <?= (int)$reviewCount ?> review(s)
                  <?php if ($avgRating !== null): ?>
                    <span class="text-muted">• avg <?= number_format($avgRating, 1) ?>/5</span>
                  <?php endif; ?>
                </div>
                <div class="text-muted small"><?= (int)$u['bad_count'] ?> bad (≤2)</div>
              </td>

              <td>
                <span class="badge <?= $isFlagged ? 'bg-danger' : 'bg-primary' ?>">
                  <?= number_format($badPercent, 0) ?>%
                </span>
              </td>

            <td><small><?= date('M j, Y', strtotime($u['created_at'])) ?></small></td>

            <td class="text-end">
              <div class="listing-actions dropdown dropdown-sm">
                <button class="btn btn-light btn-sm icon-btn shadow-sm"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Listing options"
                    onclick="event.stopPropagation();">
                  <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-extra-sm p-3 m-2 hover-bg rounded">
                  <li>
                    <a class=" btn btn-sm bg-primary-subtle rounded text-primary" href="<?= ADMIN_URL ?>/user_details.php?id=<?= (int)$u['id'] ?>">
                      <i class="bi bi-person-lines-fill me-2 "></i> View</a>
                  </li>
                  <li>
                    <hr class="dropdown-divider">
                  </li>
                  <li>
                    <form action="<?= ADMIN_URL ?>/delete_user.php" method="POST" class="d-inline"
                      onsubmit="return confirm('⚠️ Delete user \'<?= h($u['username']) ?>\'?\n\nAll their listings, messages, and requests will be permanently deleted!');">
                      <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                      <button class="btn btn-sm p-2 text-danger bg-danger-subtle rounded"><i class="bi bi-trash me-2"></i>Delete</button>
                    </form>
                  </li>
                </ul>
              </div>
            </td>
            </tr>
      <?php endforeach;
      endif; ?>
    </tbody>

  </table>
</div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>