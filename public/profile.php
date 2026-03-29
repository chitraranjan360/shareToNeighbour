<?php
$pageTitle = 'My Profile — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

$uid = currentUserId();
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $uid); $stmt->execute();
$user = $stmt->get_result()->fetch_assoc(); $stmt->close();

$stmt = $conn->prepare("SELECT * FROM furniture_items WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $uid); $stmt->execute();
$myItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card shadow-sm text-center">
            <div class="card-body p-4">
                <div class="display-1 text-success mb-3"><i class="bi bi-person-circle"></i></div>
                <h4><?= h($user['full_name']) ?></h4>
                <p class="text-muted">@<?= h($user['username']) ?></p>
                <p class="text-muted small"><i class="bi bi-envelope"></i> <?= h($user['email']) ?></p>
                <?php if ($user['address']): ?>
                <p class="text-muted small"><i class="bi bi-geo-alt"></i> <?= h($user['address']) ?></p>
                <?php endif; ?>
                <p class="text-muted small"><i class="bi bi-calendar"></i> Joined <?= date('M j, Y', strtotime($user['created_at'])) ?></p>
            </div>
        </div>
        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <h6><i class="bi bi-bar-chart"></i> Stats</h6>
                <ul class="list-unstyled mb-0">
                    <li><span class="badge bg-primary"><?= count($myItems) ?></span> Total listings</li>
                    <li><span class="badge bg-success"><?= count(array_filter($myItems, fn($i)=>$i['status']==='available')) ?></span> Available</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><i class="bi bi-grid"></i> My Listings</h3>
            <a href="<?= SITE_URL ?>/upload.php" class="btn btn-success btn-sm"><i class="bi bi-plus-circle"></i> New</a>
        </div>
        <?php if (empty($myItems)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-box-seam display-1"></i>
                <p class="mt-3">No listings yet.</p>
                <a href="<?= SITE_URL ?>/upload.php" class="btn btn-outline-success">Share Now</a>
            </div>
        <?php else: ?>
            <div class="row g-3">
            <?php foreach ($myItems as $item): ?>
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm">
                        <img src="<?= UPLOAD_URL . '/' . h($item['photo'] ?: 'placeholder.jpg') ?>" class="card-img-top" style="height:160px;object-fit:cover;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h6><?= h($item['title']) ?></h6>
                                <span class="badge bg-<?= $item['status']==='available'?'success':'warning' ?>"><?= ucfirst($item['status']) ?></span>
                            </div>
                            <a href="<?= SITE_URL ?>/item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>