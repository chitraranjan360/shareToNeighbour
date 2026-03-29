<?php
$pageTitle = 'Manage Users';
require_once __DIR__ . '/admin_header.php';

$search = trim($_GET['q'] ?? '');
$sql = "SELECT u.*, (SELECT COUNT(*) FROM furniture_items WHERE user_id = u.id) AS item_count FROM users u";
$params = []; $types = '';
if ($search !== '') {
    $sql .= " WHERE u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?";
    $like = '%'.$search.'%'; $params = [$like,$like,$like]; $types = 'sss';
}
$sql .= " ORDER BY u.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people-fill text-primary"></i> Local Users</h2>
    <span class="badge bg-primary fs-6"><?= count($users) ?> users</span>
</div>

<form method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="Search name, username, email…" value="<?= h($search) ?>">
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
        <?php if ($search): ?><a href="<?= ADMIN_URL ?>/manage_users.php" class="btn btn-outline-secondary">Clear</a><?php endif; ?>
    </div>
</form>

<div class="table-responsive">
<table class="table table-hover table-striped align-middle">
    <thead class="table-dark">
        <tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Address</th><th>Items</th><th>Joined</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if (empty($users)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
    <?php else: foreach ($users as $u): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td><strong><?= h($u['username']) ?></strong></td>
            <td><?= h($u['full_name']) ?></td>
            <td><?= h($u['email']) ?></td>
            <td class="small"><?= h($u['address'] ?? '—') ?></td>
            <td><span class="badge bg-info"><?= $u['item_count'] ?></span></td>
            <td><small><?= date('M j, Y', strtotime($u['created_at'])) ?></small></td>
            <td>
                <form action="<?= ADMIN_URL ?>/delete_user.php" method="POST" class="d-inline"
                      onsubmit="return confirm('⚠️ Delete user \'<?= h($u['username']) ?>\'?\n\nAll their listings, messages, and requests will be permanently deleted!');">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>