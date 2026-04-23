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

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-grid-fill text-success me-2"></i>Manage Items</h2>
        <p class="text-body-secondary mb-0">Review and moderate all furniture listings.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge text-bg-success-subtle border border-success-subtle text-success-emphasis fs-6"><?= count($items) ?> items</span>
        <a href="<?= ADMIN_URL ?>/add_item.php" class="btn btn-success btn-sm"><i class="bi bi-plus-circle me-1"></i>Add Item</a>
    </div>
</div>

<form id="adminManageItemsFiltersForm" method="GET" class="card border-0 p-3 mb-4 shadow-sm rounded-4">
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
            <button type="submit" id="adminManageItemsApplyFiltersBtn" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
            <a id="adminManageItemsClearFiltersBtn" href="<?= ADMIN_URL ?>/manage_items.php" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
</form>

<script src="<?= SITE_URL ?>/js/manage-items-filters.js"></script>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead class="table-light">
        <tr><th>ID</th><th>Photo</th><th>Title</th><th>Category</th><th>Owner</th><th>Status</th><th>Posted</th><th class="text-end">Actions</th></tr>
    </thead>
    <tbody>
    <?php if (empty($items)): ?>
        <tr><td colspan="8" class="text-center text-muted py-5">No items found for the selected filters.</td></tr>
    <?php else: foreach ($items as $item): ?>
        <tr>
            <td class="fw-semibold"><?= (int)$item['id'] ?></td>
            <td><img src="<?= UPLOAD_URL . '/' . h($item['photo'] ?: 'placeholder.jpg') ?>" alt="<?= h($item['title']) ?>" class="rounded-3 border" style="width:56px;height:56px;object-fit:cover;"></td>
            <td>
                <a href="<?= SITE_URL ?>/item.php?id=<?= (int)$item['id'] ?>" target="_blank" class="text-decoration-none fw-semibold"><?= h($item['title']) ?></a>
            </td>
            <td><span class="badge text-bg-success-subtle border border-success-subtle text-success-emphasis"><?= h(ucfirst($item['category'])) ?></span></td>
            <td><?= h($item['username']) ?></td>
            <td><span class="badge bg-<?= $item['status']==='available'?'primary':($item['status']==='requested'?'warning text-dark':'secondary') ?>"><?= h(ucfirst($item['status'])) ?></span></td>
            <td><small class="text-body-secondary"><?= date('M j, Y', strtotime($item['created_at'])) ?></small></td>
            <td class="text-end">
                <form action="<?= ADMIN_URL ?>/delete_item.php" method="POST" class="d-inline"
                      onsubmit="return confirm('Delete \'<?= h($item['title']) ?>\'?');">
                    <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>