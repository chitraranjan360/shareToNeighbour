<?php
$pageTitle = 'ShareToNeighbour — Share Furniture with Your Neighbours';
require_once __DIR__ . '/../includes/header.php';

// Latest items
$stmt = $conn->prepare("
    SELECT fi.*, u.username
    FROM furniture_items fi
    JOIN users u ON fi.user_id = u.id
    WHERE fi.status IN ('available','requested','taken')
    ORDER BY fi.created_at DESC
    LIMIT 9
");
$stmt->execute();
$latest = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<section class="hero-premium text-white rounded-4 p-5 p-lg-6 mb-5 shadow-sm">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <p class="text-uppercase small fw-semibold text-white-50 mb-2">Neighbour-to-Neighbour Sharing</p>
            <h1 class="display-5 fw-bold mb-3">
                <i class="bi bi-house-heart-fill"></i> Share Furniture, Build Community
            </h1>
            <p class="lead text-white-75 mb-4">
                Pass on what you don’t need. Discover what you do. Keep it local, circular, and friendly.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <span class="pill-soft pill-primary"><i class="bi bi-geo-alt"></i> Copenhagen locals</span>
                <span class="pill-soft pill-success"><i class="bi bi-recycle"></i> Circular living</span>
                <span class="pill-soft pill-secondary"><i class="bi bi-chat-dots"></i> Built-in chat</span>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="glass-card h-100">
                <h5 class="mb-3 text-white-75">Why ShareToNeighbour?</h5>
                <ul class="list-unstyled text-white-75 mb-0">
                    <li class="d-flex align-items-start gap-2 mb-2">
                        <i class="bi bi-check2-circle text-success"></i>
                        <span>Free to use—no fees.</span>
                    </li>
                    <li class="d-flex align-items-start gap-2 mb-2">
                        <i class="bi bi-check2-circle text-success"></i>
                        <span>Stay within 1 km of your home.</span>
                    </li>
                    <li class="d-flex align-items-start gap-2 mb-2">
                        <i class="bi bi-check2-circle text-success"></i>
                        <span>Request items or chat instantly.</span>
                    </li>
                </ul>
                <?php if (!isUserLoggedIn()): ?>
                    <div class="d-flex gap-2 mt-3">
                        <a href="<?= SITE_URL ?>/register.php" class="btn btn-success flex-fill">
                            <i class="bi bi-person-plus"></i> Join now
                        </a>
                        <a href="<?= SITE_URL ?>/browse.php" class="btn btn-outline-light flex-fill">
                            <i class="bi bi-search"></i> Browse items
                        </a>
                    </div>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>/upload.php" class="btn btn-success w-100 mt-3">
                        <i class="bi bi-plus-circle"></i> Share an item
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if (!isUserLoggedIn()): ?>
<section class="mb-5">
    <h2 class="text-center mb-4 text-dark"><i class="bi bi-arrow-repeat"></i> How It Works</h2>
    <div class="row g-4 text-center">
        <?php
        $steps = [
            ['icon' => 'person-plus', 'title' => 'Register & Login', 'desc' => 'Create your free account to start sharing.'],
            ['icon' => 'camera', 'title' => 'Share or Browse', 'desc' => 'Upload furniture to give away or explore nearby finds.'],
            ['icon' => 'chat-heart', 'title' => 'Chat & Collect', 'desc' => 'Message the owner, agree on pick-up, give it a second life.'],
        ];
        foreach ($steps as $step): ?>
            <div class="col-md-4">
                <div class="glass-card h-100 text-dark">
                    <div class="display-5 text-success mb-3"><i class="bi bi-<?= $step['icon'] ?>"></i></div>
                    <h5><?= $step['title'] ?></h5>
                    <p class="text-muted mb-0"><?= $step['desc'] ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="bi bi-clock-history"></i> Latest Shared Items</h2>
        <a href="<?= SITE_URL ?>/browse.php" class="btn btn-outline-success btn-sm">View All &rarr;</a>
    </div>

    <div class="row g-4">
        <?php if (empty($latest)): ?>
            <p class="text-muted text-center">No furniture shared yet. Be the first!</p>
        <?php else: ?>
            <?php foreach ($latest as $item):
                $status = $item['status'] ?? 'available';
                $statusClass = match ($status) {
                    'available' => 'pill-primary',
                    'requested' => 'pill-warning',
                    'taken'     => 'pill-dark',
                    default     => 'pill-secondary'
                };
            ?>
            <div class="col-md-4">
                <a class="card h-100 shadow-sm item-card-link text-decoration-none" href="<?= SITE_URL ?>/item.php?id=<?= $item['id'] ?>">
                    <div class="item-card-img rounded-4 overflow-hidden">
                        <img src="<?= UPLOAD_URL . '/' . h($item['photo'] ?: 'placeholder.jpg') ?>"
                             class="w-100 h-100 object-fit-cover" alt="<?= h($item['title']) ?>">
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex gap-2 mb-2 flex-wrap">
                            <span class="pill-soft pill-success"><?= h(ucfirst($item['category'])) ?></span>
                            <span class="pill-soft pill-secondary"><?= h(ucfirst(str_replace('_', ' ', $item['condition_level']))) ?></span>
                            <span class="pill-soft <?= $statusClass ?>"><?= h(ucfirst($status)) ?></span>
                        </div>
                        <h5 class="card-title text-dark mb-1"><?= h($item['title']) ?></h5>
                        <p class="card-text text-muted small flex-grow-1 mb-2">
                            <?= h(mb_strimwidth($item['description'], 0, 110, '…')) ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center text-muted small">
                            <span><i class="bi bi-person"></i> <?= h($item['username']) ?></span>
                            <span class="d-inline-flex align-items-center gap-1">
                                <i class="bi bi-chevron-right"></i> View
                            </span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<section class="glass-card p-5 mb-5">
    <div class="row align-items-center g-4">
        <div class="col-md-7">
            <h2 class="mb-3"><i class="bi bi-heart-fill text-danger"></i> About ShareToNeighbour</h2>
            <p class="text-muted mb-3">We help Copenhagen residents share unused furniture with neighbours within walking distance. Reduce waste, save money, and build community connections.</p>
            <ul class="list-unstyled mb-0 text-muted">
                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> 100% free — no fees ever</li>
                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Local — within 1 km of your home</li>
                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Sustainable — reuse instead of landfill</li>
                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Register to share or chat</li>
            </ul>
        </div>
        <div class="col-md-5 text-center">
            <div class="display-1 text-success"><i class="bi bi-recycle"></i></div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>