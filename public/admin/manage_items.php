<?php
$pageTitle = 'Manage Items — Admin — ShareToNeighbour';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$search   = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';
$status   = $_GET['status'] ?? '';

$sql = "SELECT fi.*, u.username 
        FROM furniture_items fi 
        JOIN users u ON fi.user_id = u.id 
        WHERE 1=1";
$params = [];
$types  = '';

if ($search !== '') {
    $sql .= " AND (fi.title LIKE ? OR fi.description LIKE ? OR u.username LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($category !== '' && in_array($category, ['sofa','table','chair','bed','shelf','desk','wardrobe','other'])) {
    $sql .= " AND fi.category = ?";
    $params[] = $category;
    $types .= 's';
}

if ($status !== '' && in_array($status, ['available','requested','taken'])) {
    $sql .= " AND fi.status = ?";
    $params[] = $status;
    $types .= 's';
}

$sql .= " ORDER BY fi.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . '/../../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/admin/dashboard.php">Admin</a></li>
        <li class="breadcrumb-item active">Manage Items</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-grid-fill text-success"></i> Manage Items</h2>
    <div>
        <a href="<?= SITE_URL ?>/admin/add_item.php" class="btn btn-success btn-sm">
            <i class="bi bi-plus-circle"></i> Add New Item
        </a>
        <span class="badge bg-success fs-6 ms-2"><?= count($items) ?> items</span>
    </div>
</div>

<!-- Filters -->
<form method="GET" class="card shadow-sm p-3 mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small">Search</label>
            <input type="text" name="q" class="form-control" placeholder="Title, description, or username…"
                   value="<?= h($search) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Category</label>
            <select name="category" class="form-select">
                <option value="">All</option>
                <?php foreach (['sofa','table','chair','bed','shelf','desk','wardrobe','other'] as $cat): ?>
                <option value="<?= $cat ?>" <?= $category === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select">
                <option value="">All</option>
                <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Available</option>
                <option value="requested" <?= $status === 'requested' ? 'selected' : '' ?>>Requested</option>
                <option value="taken" <?= $status === 'taken' ? 'selected' : '' ?>>Taken</option>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">
                <i class="bi bi-funnel"></i> Filter
            </button>
            <a href="<?= SITE_URL ?>/admin/manage_items.php" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
</form>

<!-- Items Table -->
<div class="table-responsive">
    <table class="table table-hover table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Photo</th>
                <th>Title</th>
                <th>Category</th>
                <th>Condition</th>
                <th>Owner</th>
                <th>Status</th>
                <th>Posted</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
            <tr>
                <td colspan="9" class="text-center text-muted py-4">No items found.</td>
            </tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= $item['id'] ?></td>
                    <td>
                        <img src="<?= UPLOAD_URL . '/' . h($item['photo'] ?: 'placeholder.jpg') ?>"
                             alt="" style="width:50px;height:50px;object-fit:cover;border-radius:4px;">
                    </td>
                    <td>
                        <a href="<?= SITE_URL ?>/item.php?id=<?= $item['id'] ?>" class="text-decoration-none">
                            <?= h($item['title']) ?>
                        </a>
                    </td>
                    <td><span class="badge bg-success"><?= ucfirst($item['category']) ?></span></td>
                    <td><?= ucfirst(str_replace('_', ' ', $item['condition_level'])) ?></td>
                    <td><?= h($item['username']) ?></td>
                    <td>
                        <span class="badge bg-<?= $item['status'] === 'available' ? 'primary' : ($item['status'] === 'requested' ? 'warning text-dark' : 'secondary') ?>">
                            <?= ucfirst($item['status']) ?>
                        </span>
                    </td>
                    <td><small><?= date('M j, Y', strtotime($item['created_at'])) ?></small></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= SITE_URL ?>/item.php?id=<?= $item['id'] ?>" 
                               class="btn btn-sm btn-outline-primary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <form action="<?= SITE_URL ?>/admin/delete_item.php" method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('Delete item \'<?= h($item['title']) ?>\'?\nThis cannot be undone.');">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>