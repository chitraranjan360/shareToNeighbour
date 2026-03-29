<?php
$pageTitle = 'Manage Items';
require_once __DIR__ . '/admin_header.php';

$search = trim($_GET['q'] ?? ''); $category = $_GET['category'] ?? '';
$sql = "SELECT fi.*, u.username FROM furniture_items fi JOIN users u ON fi.user_id = u.id WHERE 1=1";
$params = []; $types = '';
if ($search !== '') {
    $sql .= " AND (fi.title LIKE ? OR u.username LIKE ?)";
    $like = '%'.$search.'%'; $params[] = $like; $params[] = $like; $types .= 'ss';
}
if ($category && in_array($category, ['sofa','table','chair','bed','shelf','desk','wardrobe','other'])) {
    $sql .= " AND fi.category = ?"; $params[] = $category; $types .= 's';
}
$sql .= " ORDER BY fi.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-grid-fill text-success"></i> All Items</h2>
    <div>
        <a href="<?= ADMIN_URL ?>/add_item.php" class="btn btn-success btn-sm"><i class="bi bi-plus-circle"></i> Add Item</a>
        <span class="badge bg-success fs-6 ms-2"><?= count($items) ?></span>
    </div>
</div>

<form method="GET" class="card p-3 mb-4 shadow-sm">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <input type="text" name="q" class="form-control" placeholder="Search title or username…" value="<?= h($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <?php foreach (['sofa','table','chair','bed','shelf','desk','wardrobe','other'] as $c): ?>
                <option value="<?= $c ?>" <?= $category===$c?'selected':'' ?>><?= ucfirst($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
            <a href="<?= ADMIN_URL ?>/manage_items.php" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
</form>

<div class="table-responsive">
<table class="table table-hover table-striped align-middle">
    <thead class="table-dark">
        <tr><th>ID</th><th>Photo</th><th>Title</th><th>Category</th><th>Owner</th><th>Status</th><th>Posted</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if (empty($items)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No items.</td></tr>
    <?php else: foreach ($items as $item): ?>
        <tr>
            <td><?= $item['id'] ?></td>
            <td><img src="<?= UPLOAD_URL . '/' . h($item['photo'] ?: 'placeholder.jpg') ?>" style="width:50px;height:50px;object-fit:cover;border-radius:4px;"></td>
            <td><a href="<?= SITE_URL ?>/item.php?id=<?= $item['id'] ?>" target="_blank"><?= h($item['title']) ?></a></td>
            <td><span class="badge bg-success"><?= ucfirst($item['category']) ?></span></td>
            <td><?= h($item['username']) ?></td>
            <td><span class="badge bg-<?= $item['status']==='available'?'primary':($item['status']==='requested'?'warning text-dark':'secondary') ?>"><?= ucfirst($item['status']) ?></span></td>
            <td><small><?= date('M j, Y', strtotime($item['created_at'])) ?></small></td>
            <td>
                <form action="<?= ADMIN_URL ?>/delete_item.php" method="POST" class="d-inline"
                      onsubmit="return confirm('Delete \'<?= h($item['title']) ?>\'?');">
                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                    <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>