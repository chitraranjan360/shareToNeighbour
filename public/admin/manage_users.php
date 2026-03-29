<?php
$pageTitle = 'Manage Users — Admin — ShareToNeighbour';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$search = trim($_GET['q'] ?? '');

// Build user query
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM furniture_items WHERE user_id = u.id) AS item_count
        FROM users u";
$params = [];
$types  = '';

if ($search !== '') {
    $sql .= " WHERE u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
    $types  = 'sss';
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . '/../../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/admin/dashboard.php">Admin</a></li>
        <li class="breadcrumb-item active">Manage Users</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people-fill text-primary"></i> Manage Users</h2>
    <span class="badge bg-primary fs-6"><?= count($users) ?> users</span>
</div>

<!-- Search -->
<form method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" name="q" class="form-control" 
               placeholder="Search by username, email, or name…"
               value="<?= h($search) ?>">
        <button class="btn btn-outline-primary" type="submit">
            <i class="bi bi-search"></i> Search
        </button>
        <?php if ($search): ?>
        <a href="<?= SITE_URL ?>/admin/manage_users.php" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>

<!-- Users Table -->
<div class="table-responsive">
    <table class="table table-hover table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Items</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
            <tr>
                <td colspan="8" class="text-center text-muted py-4">No users found.</td>
            </tr>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                <tr class="<?= $u['role'] === 'admin' ? 'table-warning' : '' ?>">
                    <td><?= $u['id'] ?></td>
                    <td>
                        <strong><?= h($u['username']) ?></strong>
                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="badge bg-warning text-dark">Admin</span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($u['full_name']) ?></td>
                    <td><?= h($u['email']) ?></td>
                    <td>
                        <span class="badge bg-<?= $u['role'] === 'admin' ? 'warning text-dark' : 'secondary' ?>">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td><span class="badge bg-info"><?= $u['item_count'] ?></span></td>
                    <td><small><?= date('M j, Y', strtotime($u['created_at'])) ?></small></td>
                    <td>
                        <?php if ((int)$u['id'] !== currentUserId()): ?>
                        <form action="<?= SITE_URL ?>/admin/delete_user.php" method="POST"
                              class="d-inline"
                              onsubmit="return confirm('⚠️ Delete user \'<?= h($u['username']) ?>\'?\n\nThis will also delete ALL their furniture listings, messages, and requests.\n\nThis cannot be undone!');">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                        <?php else: ?>
                            <span class="text-muted small">You</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>