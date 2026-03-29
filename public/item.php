<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$itemId = (int)($_GET['id'] ?? 0);
if ($itemId <= 0) {
    setFlash('error', 'Item not found.');
    redirect(SITE_URL . '/browse.php');
}

$stmt = $conn->prepare("SELECT fi.*, u.username, u.full_name, u.address AS owner_address FROM furniture_items fi JOIN users u ON fi.user_id = u.id WHERE fi.id = ?");
$stmt->bind_param('i', $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    setFlash('error', 'Item not found.');
    redirect(SITE_URL . '/browse.php');
}
$alreadyRequested = false;
if (isUserLoggedIn()) {
    $stmt = $conn->prepare("
        SELECT id
        FROM requests
        WHERE item_id = ? AND requester_id = ? AND status IN ('pending','accepted')
        LIMIT 1
    ");
    $uid = currentUserId();
    $stmt->bind_param('ii', $itemId, $uid);
    $stmt->execute();
    $alreadyRequested = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

$pageTitle = h($item['title']) . ' — ShareToNeighbour';
require_once __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/browse.php">Browse</a></li>
        <li class="breadcrumb-item active"><?= h($item['title']) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-md-6">
        <img src="<?= UPLOAD_URL . '/' . h($item['photo'] ?: 'placeholder.jpg') ?>"
            class="img-fluid rounded shadow-sm w-100" alt="<?= h($item['title']) ?>" style="max-height:500px;object-fit:cover;">
        <?php if ($item['video_link']): ?>
            <a href="<?= h($item['video_link']) ?>" target="_blank" class="btn btn-outline-danger btn-sm mt-3">
                <i class="bi bi-play-circle"></i> Watch Video
            </a>
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <h2><?= h($item['title']) ?></h2>
        <div class="d-flex gap-2 mb-3">
            <span class="badge bg-success fs-6"><?= ucfirst($item['category']) ?></span>
            <span class="badge bg-secondary fs-6"><?= ucfirst(str_replace('_', ' ', $item['condition_level'])) ?></span>
            <span class="badge bg-<?= $item['status'] === 'available' ? 'primary' : 'warning' ?> fs-6"><?= ucfirst($item['status']) ?></span>
        </div>
        <p class="lead"><?= nl2br(h($item['description'])) ?></p>
        <hr>
        <p><i class="bi bi-person-circle"></i> <strong><?= h($item['full_name']) ?></strong> (@<?= h($item['username']) ?>)</p>
        <?php if ($item['owner_address']): ?>
            <p class="text-muted small"><i class="bi bi-geo-alt"></i> <?= h($item['owner_address']) ?></p>
        <?php endif; ?>
        <p class="text-muted small"><i class="bi bi-clock"></i> Posted <?= date('M j, Y H:i', strtotime($item['created_at'])) ?></p>
        <hr>


        <!-- ★★★ ACTION BUTTONS — login required for request/chat ★★★ -->
        <?php if (isUserLoggedIn() && currentUserId() === (int)$item['user_id']): ?>

            <!-- OWNER VIEW -->
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> This is your listing.

                <div class="mt-3">
                    <form action="<?= SITE_URL ?>/update_item_status.php" method="POST" class="row g-2 align-items-end">
                        <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">

                        <div class="col-sm-6">
                            <label class="form-label mb-1"><strong>Update Status</strong></label>
                            <select class="form-select" name="status">
                                <option value="available" <?= $item['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                                <option value="requested" <?= $item['status'] === 'requested' ? 'selected' : '' ?>>Requested (Reserved)</option>
                                <option value="taken" <?= $item['status'] === 'taken' ? 'selected' : '' ?>>Taken</option>
                            </select>
                        </div>

                        <div class="col-sm-6 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100"
                                onclick="return confirm('Change item status?');">
                                <i class="bi bi-save"></i> Save
                            </button>

                        </div>
                    </form>
                </div>
            </div>

        <?php elseif (isUserLoggedIn() && currentUserId() !== (int)$item['user_id']): ?>

            <!-- NON-OWNER USER VIEW -->
            <?php if ($item['status'] === 'available'): ?>

                <?php if ($alreadyRequested): ?>
                    <button class="btn btn-warning btn-lg w-100 mb-2" disabled>
                        <i class="bi bi-hourglass-split"></i> Request Already Sent
                    </button>
                <?php else: ?>
                    <form action="<?= SITE_URL ?>/request_item.php" method="POST" class="mb-2">
                        <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                        <textarea name="message" class="form-control mb-2" rows="2"
                            placeholder="Message to owner (optional)…"></textarea>
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="bi bi-hand-index-thumb"></i> Request This Item
                        </button>
                    </form>
                <?php endif; ?>

                <a href="<?= SITE_URL ?>/send_message.php?to=<?= (int)$item['user_id'] ?>&item=<?= (int)$item['id'] ?>"
                    class="btn btn-outline-primary w-100">
                    <i class="bi bi-chat-dots"></i> Chat with Owner
                </a>

            <?php else: ?>
                <button class="btn btn-secondary btn-lg w-100" disabled>
                    <i class="bi bi-x-circle"></i> No Longer Available (<?= h(ucfirst($item['status'])) ?>)
                </button>
            <?php endif; ?>

        <?php else: ?>

            <!-- NOT LOGGED IN -->
            <div class="alert alert-warning">
                <i class="bi bi-lock"></i> <strong>Login required</strong>
                <p class="mb-2">You must be logged in to request items or chat with the owner.</p>
                <a href="<?= SITE_URL ?>/login.php" class="btn btn-success me-2">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </a>
                <a href="<?= SITE_URL ?>/register.php" class="btn btn-outline-success">
                    <i class="bi bi-person-plus"></i> Register
                </a>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>