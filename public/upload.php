<?php
$pageTitle = 'Share Furniture — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/image_resize.php';

// ★★★ USER MUST BE LOGGED IN TO POST ★★★
requireUserLogin();

$errors = [];
$title = $description = $video_link = '';
$category = 'other'; $condition = 'good';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = $_POST['category'] ?? 'other';
    $condition   = $_POST['condition_level'] ?? 'good';
    $video_link  = trim($_POST['video_link'] ?? '');

    if (strlen($title) < 3)        $errors[] = 'Title must be at least 3 characters.';
    if (strlen($description) < 10) $errors[] = 'Description must be at least 10 characters.';

    $photoName = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['photo']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed))             $errors[] = 'Only JPEG, PNG, GIF, WEBP allowed.';
        elseif ($_FILES['photo']['size'] > 5242880) $errors[] = 'Image must be under 5 MB.';
        else {
            $photoName = 'item_' . time() . '_' . mt_rand(1000,9999) . '.jpg';
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            if (!resizeAndSaveImage($_FILES['photo']['tmp_name'], UPLOAD_DIR . '/' . $photoName, MAX_IMG_WIDTH, MAX_IMG_HEIGHT)) {
                $errors[] = 'Image processing failed.'; $photoName = null;
            }
        }
    } else {
        $errors[] = 'Please upload a photo.';
    }

    if ($video_link !== '' && !filter_var($video_link, FILTER_VALIDATE_URL))
        $errors[] = 'Enter a valid video URL.';

    $uid = currentUserId();
    $stmt = $conn->prepare("SELECT latitude, longitude FROM users WHERE id = ?");
    $stmt->bind_param('i', $uid); $stmt->execute();
    $loc = $stmt->get_result()->fetch_assoc(); $stmt->close();
    $lat = $loc['latitude'] ?? 55.6761; $lng = $loc['longitude'] ?? 12.5683;

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO furniture_items (user_id,title,description,category,condition_level,photo,video_link,latitude,longitude) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('issssssdd', $uid, $title, $description, $category, $condition, $photoName, $video_link, $lat, $lng);
        if ($stmt->execute()) {
            setFlash('success', 'Furniture listed successfully!');
            redirect(SITE_URL . '/item.php?id=' . $stmt->insert_id);
        } else $errors[] = 'Save failed.';
        $stmt->close();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="text-center mb-2"><i class="bi bi-plus-circle-fill text-success"></i> Share Furniture</h2>
                <p class="text-center text-muted small mb-4">Logged in as <strong><?= h(currentUserName()) ?></strong></p>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><ul class="mb-0">
                    <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                </ul></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Item Title *</label>
                        <input type="text" class="form-control" name="title" value="<?= h($title) ?>" required placeholder="e.g. Blue IKEA Sofa">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea class="form-control" name="description" rows="4" required placeholder="Size, colour, defects…"><?= h($description) ?></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <?php foreach (['sofa','table','chair','bed','shelf','desk','wardrobe','other'] as $c): ?>
                                <option value="<?= $c ?>" <?= $category===$c?'selected':'' ?>><?= ucfirst($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Condition</label>
                            <select class="form-select" name="condition_level">
                                <option value="like_new" <?= $condition==='like_new'?'selected':'' ?>>Like New</option>
                                <option value="good" <?= $condition==='good'?'selected':'' ?>>Good</option>
                                <option value="fair" <?= $condition==='fair'?'selected':'' ?>>Fair</option>
                                <option value="needs_repair" <?= $condition==='needs_repair'?'selected':'' ?>>Needs Repair</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Photo * <small class="text-muted">(resized to 800×600)</small></label>
                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Video Link <small class="text-muted">(optional)</small></label>
                        <input type="url" class="form-control" name="video_link" value="<?= h($video_link) ?>" placeholder="https://youtube.com/watch?v=…">
                    </div>
                    <button type="submit" class="btn btn-success w-100 btn-lg">
                        <i class="bi bi-cloud-upload"></i> Publish Listing
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>