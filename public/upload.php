<?php
$pageTitle = 'Share Furniture — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/image_resize.php';

requireUserLogin();

$errors = [];
$title = $description = '';
$category = 'other';
$condition = 'good';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = $_POST['category'] ?? 'other';
    $condition   = $_POST['condition_level'] ?? 'good';

    if (strlen($title) < 3)        $errors[] = 'Title must be at least 3 characters.';
    if (strlen($description) < 10) $errors[] = 'Description must be at least 10 characters.';

    // user location must exist
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

    // ✅ Photos: min 1, max 3
    $photoNames = [];

    if (empty($errors)) {
        if (!isset($_FILES['photos'])) {
            $errors[] = 'Please upload at least 1 photo (max 3).';
        } else {
            $files = $_FILES['photos'];

            // Collect uploaded indexes (ignore empty)
            $indexes = [];
            for ($i = 0; $i < count($files['name']); $i++) {
                if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $indexes[] = $i;
                }
            }

            $count = count($indexes);
            if ($count < 1 || $count > 3) {
                $errors[] = 'Maximum 3 photos allowded.';
            } else {
                $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                foreach ($indexes as $pos => $i) {
                    if (($files['error'][$i] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                        $errors[] = 'One of the photos failed to upload.';
                        break;
                    }

                    $tmp = $files['tmp_name'][$i] ?? '';
                    if ($tmp === '' || !is_uploaded_file($tmp)) {
                        $errors[] = 'Invalid uploaded file.';
                        break;
                    }

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime  = finfo_file($finfo, $tmp);
                    finfo_close($finfo);

                    if (!in_array($mime, $allowed, true)) {
                        $errors[] = 'Only JPEG, PNG, GIF, WEBP allowed.';
                        break;
                    }

                    if (($files['size'][$i] ?? 0) > 5242880) {
                        $errors[] = 'Each image must be under 5 MB.';
                        break;
                    }

                    $photoName = 'item_' . time() . '_' . mt_rand(1000, 9999) . '_' . ($pos + 1) . '.jpg';

                    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

                    if (!resizeAndSaveImage(
                        $tmp,
                        UPLOAD_DIR . '/' . $photoName,
                        MAX_IMG_WIDTH,
                        MAX_IMG_HEIGHT
                    )) {
                        $errors[] = 'Image processing failed.';
                        break;
                    }

                    $photoNames[] = $photoName;
                }

                // cleanup if error mid-way
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
        $coverPhoto = $photoNames[0];
        $video_link = null;

        $stmt = $conn->prepare("
            INSERT INTO furniture_items
              (user_id,title,description,category,condition_level,photo,video_link,latitude,longitude)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");
        $stmt->bind_param('issssssdd', $uid, $title, $description, $category, $condition, $coverPhoto, $video_link, $lat, $lng);

        if ($stmt->execute()) {
            $itemId = $stmt->insert_id;
            $stmt->close();

            // insert images (1..3)
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
    <div class="col-md-10 col-lg-8 col-xl-7">

        <!-- Premium header -->
        <div class="rounded-4 overflow-hidden mb-4 shadow-lg">
            <div class="p-4 p-md-5 mb-0  text-white shadow-sm fancy-nav">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <p class="text-uppercase small fw-semibold mb-1 text-white-50">Share something useful</p>
                        <h2 class="mb-1">
                            <i class="bi bi-plus-circle-fill me-2"></i> Share Furniture
                        </h2>
                        <p class="mb-0 text-white-75 small">
                            Logged in as <strong><?= h(currentUserName()) ?></strong>
                        </p>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="<?= SITE_URL ?>/my_listings.php" class="btn btn-outline-light">
                            <i class="bi bi-grid"></i> My Listings
                        </a>
                        <a href="<?= SITE_URL ?>/browse.php" class="btn btn-light">
                            <i class="bi bi-search"></i> Browse
                        </a>
                    </div>
                </div>
            </div>

            <!-- Form card -->
            <div class="card ">
                <div class="card-body p-4 p-md-5">

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger border-0 rounded-4">
                            <div class="d-flex gap-2">
                                <div class="pt-1"><i class="bi bi-exclamation-triangle-fill"></i></div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold mb-1">Please fix the following:</div>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" novalidate>
                        <div class="row g-4">

                            <!-- Title -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Item Title <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light"><i class="bi bi-type"></i></span>
                                    <input type="text" class="form-control" name="title" value="<?= h($title) ?>" required
                                        placeholder="e.g. Oak dining table">
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light align-items-start pt-3"><i class="bi bi-card-text"></i></span>
                                    <textarea class="form-control" name="description" rows="5" required
                                        placeholder="Add size, condition, pickup notes, etc."><?= h($description) ?></textarea>
                                </div>
                                <div class="form-text">Tip: include dimensions and pickup time window.</div>
                            </div>

                            <!-- Category + Condition -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Category</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-tags"></i></span>
                                    <select class="form-select" name="category">
                                        <?php foreach (['sofa', 'table', 'chair', 'bed', 'shelf', 'desk', 'wardrobe', 'other'] as $c): ?>
                                            <option value="<?= $c ?>" <?= $category === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Condition</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-stars"></i></span>
                                    <select class="form-select" name="condition_level">
                                        <option value="like_new" <?= $condition === 'like_new' ? 'selected' : '' ?>>Like New</option>
                                        <option value="good" <?= $condition === 'good' ? 'selected' : '' ?>>Good</option>
                                        <option value="fair" <?= $condition === 'fair' ? 'selected' : '' ?>>Fair</option>
                                        <option value="needs_repair" <?= $condition === 'needs_repair' ? 'selected' : '' ?>>Needs Repair</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Photos -->
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <label class="form-label fw-semibold mb-0">Photos (up to 3) <span class="text-danger">*</span></label>
                                    <span class="badge rounded-pill text-bg-light border">Cover = first selected</span>
                                </div>

                                <div class="upload-drop border rounded-4 p-3 p-md-4 mt-2 bg-light">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="upload-icon">
                                            <i class="bi bi-images"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">Upload images</div>
                                            <div class="text-muted small">Choose up to 3 photos. PNG/JPG recommended.</div>
                                        </div>
                                        <div>
                                            <label class="btn btn-outline-success mb-0">
                                                <i class="bi bi-paperclip"></i> Choose files
                                                <input id="photos" type="file" class="d-none" name="photos[]" accept="image/*" multiple required>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-text mt-2 mb-0">
                                        Upload up to 3 images. The first selected image becomes the cover.
                                    </div>
                                </div>

                                <div id="photoPreview" class="row g-2 mt-3"></div>
                            </div>

                            <!-- Submit -->
                            <div class="col-12">
                                <button type="submit" class="btn btn-success btn-lg w-100">
                                    <i class="bi bi-cloud-upload"></i> Post
                                </button>
                            </div>

                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
<script>
    (function() {
        const input = document.getElementById('photos');
        const preview = document.getElementById('photoPreview');

        function render() {
            preview.innerHTML = '';
            const files = input.files ? Array.from(input.files) : [];

            files.slice(0, 3).forEach((file, idx) => {
                const col = document.createElement('div');
                col.className = 'col-4';

                const wrap = document.createElement('div');
                wrap.className = 'border rounded p-1 bg-light';

                const label = document.createElement('div');
                label.className = 'small text-muted text-center mb-1';
                label.textContent = (idx === 0) ? 'Cover' : ('Photo ' + (idx + 1));

                const img = document.createElement('img');
                img.className = 'img-fluid rounded';
                img.style.width = '100%';
                img.style.height = '90px';
                img.style.objectFit = 'cover';
                img.src = URL.createObjectURL(file);

                wrap.appendChild(label);
                wrap.appendChild(img);
                col.appendChild(wrap);
                preview.appendChild(col);
            });
        }

        input.addEventListener('change', () => {
            if (input.files && input.files.length > 3) {
                alert('Please select maximum 3 images.');
                input.value = '';
                preview.innerHTML = '';
                return;
            }
            render();
        });
    })();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>