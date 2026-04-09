<?php
$pageTitle = 'Share Furniture — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/image_resize.php';

requireUserLogin();

$errors = [];
$title = $description = $video_link = '';
$category = 'other';
$condition = 'good';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = $_POST['category'] ?? 'other';
    $condition   = $_POST['condition_level'] ?? 'good';
    $video_link  = trim($_POST['video_link'] ?? '');

    if (strlen($title) < 3)        $errors[] = 'Title must be at least 3 characters.';
    if (strlen($description) < 10) $errors[] = 'Description must be at least 10 characters.';

    if ($video_link !== '' && !filter_var($video_link, FILTER_VALIDATE_URL)) {
        $errors[] = 'Enter a valid video URL.';
    }

    // ✅ FIRST: user location must exist (real address saved)
    $uid = currentUserId();
    $stmt = $conn->prepare("SELECT latitude, longitude FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $loc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $lat = $loc['latitude'] ?? null;
    $lng = $loc['longitude'] ?? null;

    if ($lat === null || $lng === null) {
        $errors[] = 'Please update your profile address (select from suggestions) before posting items.';
    }

    // ✅ Exactly 3 photos required
    $photoNames = [];

    if (empty($errors)) {
        if (!isset($_FILES['photos'])) {
            $errors[] = 'Please upload exactly 3 photos.';
        } else {
            $files = $_FILES['photos'];

            // Collect uploaded indexes (ignore empty inputs)
            $indexes = [];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    $indexes[] = $i;
                }
            }

            if (count($indexes) !== 3) {
                $errors[] = 'Please upload exactly 3 photos.';
            } else {
                $allowed = ['image/jpeg','image/png','image/gif','image/webp'];

                foreach ($indexes as $pos => $i) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                        $errors[] = 'One of the photos failed to upload.';
                        break;
                    }

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime  = finfo_file($finfo, $files['tmp_name'][$i]);
                    finfo_close($finfo);

                    if (!in_array($mime, $allowed, true)) {
                        $errors[] = 'Only JPEG, PNG, GIF, WEBP allowed.';
                        break;
                    }

                    if ($files['size'][$i] > 5242880) {
                        $errors[] = 'Each image must be under 5 MB.';
                        break;
                    }

                    $photoName = 'item_' . time() . '_' . mt_rand(1000,9999) . '_' . ($pos + 1) . '.jpg';

                    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

                    if (!resizeAndSaveImage(
                        $files['tmp_name'][$i],
                        UPLOAD_DIR . '/' . $photoName,
                        MAX_IMG_WIDTH,
                        MAX_IMG_HEIGHT
                    )) {
                        $errors[] = 'Image processing failed.';
                        break;
                    }

                    $photoNames[] = $photoName;
                }

                // If any error happened mid-way, cleanup saved files
                if (!empty($errors) && !empty($photoNames)) {
                    foreach ($photoNames as $fn) {
                        $path = UPLOAD_DIR . '/' . $fn;
                        if (is_file($path)) @unlink($path);
                    }
                    $photoNames = [];
                }
            }
        }
    }

    if (empty($errors)) {
        $coverPhoto = $photoNames[0]; // first image as cover

        // Insert item
        $stmt = $conn->prepare("
            INSERT INTO furniture_items
              (user_id,title,description,category,condition_level,photo,video_link,latitude,longitude)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");
        $stmt->bind_param('issssssdd', $uid, $title, $description, $category, $condition, $coverPhoto, $video_link, $lat, $lng);

        if ($stmt->execute()) {
            $itemId = $stmt->insert_id;
            $stmt->close();

            // Insert all 3 images
            $imgStmt = $conn->prepare("
                INSERT INTO furniture_item_images (item_id, filename, sort_order)
                VALUES (?, ?, ?)
            ");

            $order = 1;
            foreach ($photoNames as $fn) {
                $imgStmt->bind_param('isi', $itemId, $fn, $order);
                $imgStmt->execute();
                $order++;
            }
            $imgStmt->close();

            setFlash('success', 'Furniture listed successfully!');
            redirect(SITE_URL . '/item.php?id=' . $itemId);
        } else {
            $stmt->close();
            $errors[] = 'Save failed.';
        }

        // Cleanup images if DB insert failed
        if (!empty($errors) && !empty($photoNames)) {
            foreach ($photoNames as $fn) {
                $path = UPLOAD_DIR . '/' . $fn;
                if (is_file($path)) @unlink($path);
            }
        }
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
                        <input type="text" class="form-control" name="title" value="<?= h($title) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea class="form-control" name="description" rows="4" required><?= h($description) ?></textarea>
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
                        <label class="form-label">Photos (exactly 3) *</label>
                        <input type="file" class="form-control" name="photos[]" accept="image/*" multiple required>
                        <div class="form-text">Select exactly 3 images. The first selected image becomes the cover.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Video Link <small class="text-muted">(optional)</small></label>
                        <input type="url" class="form-control" name="video_link" value="<?= h($video_link) ?>">
                    </div>

                    <button type="submit" class="btn btn-success w-100 btn-lg">
                        <i class="bi bi-cloud-upload"></i> Post
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>