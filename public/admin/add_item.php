<?php
$pageTitle = 'Add Item — Admin — ShareToNeighbour';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/image_resize.php';
requireAdmin();

$errors = [];

// Fetch all users for the "post as" dropdown
$usersResult = $conn->query("SELECT id, username, full_name FROM users ORDER BY username ASC");
$allUsers = $usersResult->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAsUser  = (int)($_POST['user_id'] ?? currentUserId());
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = $_POST['category'] ?? 'other';
    $condition   = $_POST['condition_level'] ?? 'good';
    $video_link  = trim($_POST['video_link'] ?? '');
    $itemStatus  = $_POST['status'] ?? 'available';

    if (strlen($title) < 3)        $errors[] = 'Title must be at least 3 characters.';
    if (strlen($description) < 10) $errors[] = 'Description must be at least 10 characters.';

    // Validate the selected user exists
    $stmt = $conn->prepare("SELECT id, latitude, longitude FROM users WHERE id = ?");
    $stmt->bind_param('i', $postAsUser);
    $stmt->execute();
    $targetUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$targetUser) {
        $errors[] = 'Selected user does not exist.';
    }

    // Handle image
    $photoName = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo   = finfo_open(FILEINFO_MIME_TYPE);
        $mime    = finfo_file($finfo, $_FILES['photo']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            $errors[] = 'Only JPEG, PNG, GIF, and WEBP images are allowed.';
        } elseif ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Image must be smaller than 5 MB.';
        } else {
            $photoName = 'item_' . time() . '_' . mt_rand(1000, 9999) . '.jpg';
            $destPath  = UPLOAD_DIR . $photoName;

            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

            if (!resizeAndSaveImage($_FILES['photo']['tmp_name'], $destPath, MAX_IMG_WIDTH, MAX_IMG_HEIGHT)) {
                $errors[] = 'Failed to process image.';
                $photoName = null;
            }
        }
    } else {
        $errors[] = 'Please upload a photo.';
    }

    if ($video_link !== '' && !filter_var($video_link, FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid video URL.';
    }

    if (empty($errors)) {
        $lat = $targetUser['latitude']  ?? 55.6761;
        $lng = $targetUser['longitude'] ?? 12.5683;

        $stmt = $conn->prepare("
            INSERT INTO furniture_items
                (user_id, title, description, category, condition_level, photo, video_link, latitude, longitude, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'issssssdds',
            $postAsUser, $title, $description, $category, $condition,
            $photoName, $video_link, $lat, $lng, $itemStatus
        );

        if ($stmt->execute()) {
            $newId = $stmt->insert_id;
            setFlash('success', 'Item "' . $title . '" has been added (ID: ' . $newId . ').');
            redirect(SITE_URL . '/item.php?id=' . $newId);
        } else {
            $errors[] = 'Failed to save item.';
        }
        $stmt->close();
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/admin/dashboard.php">Admin</a></li>
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/admin/manage_items.php">Items</a></li>
        <li class="breadcrumb-item active">Add New Item</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-warning">
                <h4 class="mb-0"><i class="bi bi-plus-square-fill"></i> Admin: Add Furniture Item</h4>
            </div>
            <div class="card-body p-4">

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= h($e) ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" novalidate>

                    <!-- ADMIN FEATURE: Post as any user -->
                    <div class="mb-3 p-3 bg-light rounded border">
                        <label for="user_id" class="form-label fw-bold">
                            <i class="bi bi-person-badge"></i> Post as User (Admin Only)
                        </label>
                        <select class="form-select" id="user_id" name="user_id">
                            <?php foreach ($allUsers as $u): ?>
                            <option value="<?= $u['id'] ?>"
                                <?= ($postAsUser ?? currentUserId()) == $u['id'] ? 'selected' : '' ?>>
                                <?= h($u['username']) ?> — <?= h($u['full_name']) ?>
                                <?= $u['id'] == currentUserId() ? '(you)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">The item will appear under this user's profile.</small>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Item Title *</label>
                        <input type="text" class="form-control" id="title" name="title"
                               value="<?= h($title ?? '') ?>" required placeholder="e.g. Blue IKEA Sofa">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description"
                                  rows="4" required><?= h($description ?? '') ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <?php foreach (['sofa','table','chair','bed','shelf','desk','wardrobe','other'] as $cat): ?>
                                <option value="<?= $cat ?>" <?= ($category ?? '') === $cat ? 'selected' : '' ?>>
                                    <?= ucfirst($cat) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="condition_level" class="form-label">Condition</label>
                            <select class="form-select" id="condition_level" name="condition_level">
                                <option value="like_new">Like New</option>
                                <option value="good" selected>Good</option>
                                <option value="fair">Fair</option>
                                <option value="needs_repair">Needs Repair</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="available" selected>Available</option>
                                <option value="requested">Requested</option>
                                <option value="taken">Taken</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="photo" class="form-label">Photo * <small class="text-muted">(auto-resized)</small></label>
                        <input type="file" class="form-control" id="photo" name="photo"
                               accept="image/jpeg,image/png,image/gif,image/webp" required>
                    </div>

                    <div class="mb-3">
                        <label for="video_link" class="form-label">Video Link <small class="text-muted">(optional)</small></label>
                        <input type="url" class="form-control" id="video_link" name="video_link"
                               value="<?= h($video_link ?? '') ?>" placeholder="https://youtube.com/watch?v=…">
                    </div>

                    <button type="submit" class="btn btn-warning w-100 btn-lg">
                        <i class="bi bi-cloud-upload"></i> Add Item (Admin)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>