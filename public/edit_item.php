<?php
$pageTitle = 'Edit Listing — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/image_resize.php';

requireUserLogin();

$uid = currentUserId();
$itemId = (int)($_GET['id'] ?? $_POST['item_id'] ?? 0);

if ($itemId <= 0) {
    setFlash('error', 'Invalid item.');
    redirect(SITE_URL . '/my_listings.php');
}

// Load item and verify ownership
$stmt = $conn->prepare("SELECT * FROM furniture_items WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    setFlash('error', 'Item not found.');
    redirect(SITE_URL . '/my_listings.php');
}

if ((int)$item['user_id'] !== $uid) {
    setFlash('error', 'You do not have permission to edit this item.');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
}

// Load gallery images (expected 3)
$stmt = $conn->prepare("
    SELECT id, filename, sort_order
    FROM furniture_item_images
    WHERE item_id = ?
    ORDER BY sort_order ASC, id ASC
");
$stmt->bind_param('i', $itemId);
$stmt->execute();
$galleryRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Build array indexed by sort_order: 1..3
$gallery = [1 => null, 2 => null, 3 => null];
foreach ($galleryRows as $row) {
    $so = (int)$row['sort_order'];
    if ($so >= 1 && $so <= 3 && $gallery[$so] === null) {
        $gallery[$so] = $row;
    }
}

// Prefill from DB
$errors = [];
$title = $item['title'] ?? '';
$description = $item['description'] ?? '';
$category = $item['category'] ?? 'other';
$condition = $item['condition_level'] ?? 'good';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = $_POST['category'] ?? 'other';
    $condition   = $_POST['condition_level'] ?? 'good';
   

    if (strlen($title) < 3)        $errors[] = 'Title must be at least 3 characters.';
    if (strlen($description) < 10) $errors[] = 'Description must be at least 10 characters.';
    

    // Replacement images (optional)
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    $replacements = []; // sort_order => newFilename
    $oldFilesToDelete = []; // old filenames to delete after commit

    if (empty($errors)) {
        for ($k = 1; $k <= 3; $k++) {
            $input = 'photo' . $k;
            if (!isset($_FILES[$input]) || $_FILES[$input]['error'] === UPLOAD_ERR_NO_FILE) {
                continue; // no replacement for this slot
            }

            if ($_FILES[$input]['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Image {$k} upload failed.";
                break;
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $_FILES[$input]['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowed, true)) { $errors[] = 'Only JPEG, PNG, GIF, WEBP allowed.'; break; }
            if ($_FILES[$input]['size'] > 5242880) { $errors[] = 'Each image must be under 5 MB.'; break; }

            $newName = 'item_' . time() . '_' . mt_rand(1000,9999) . '_edit_' . $k . '.jpg';
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

            if (!resizeAndSaveImage($_FILES[$input]['tmp_name'], UPLOAD_DIR . '/' . $newName, MAX_IMG_WIDTH, MAX_IMG_HEIGHT)) {
                $errors[] = 'Image processing failed.';
                break;
            }

            $replacements[$k] = $newName;

            // remember old file for deletion after successful commit
            if (!empty($gallery[$k]['filename'])) {
                $oldFilesToDelete[] = $gallery[$k]['filename'];
            }
        }

        // if error occurred after saving some new files -> cleanup new ones
        if (!empty($errors) && !empty($replacements)) {
            foreach ($replacements as $fn) {
                $p = UPLOAD_DIR . '/' . $fn;
                if (is_file($p)) @unlink($p);
            }
            $replacements = [];
        }
    }

    if (empty($errors)) {
        $conn->begin_transaction();

        try {
            // Update item fields
            $stmt = $conn->prepare("
                UPDATE furniture_items
                SET title=?, description=?, category=?, condition_level=?, video_link=?
                WHERE id=? AND user_id=?
            ");
            $stmt->bind_param('sssssii', $title, $description, $category, $condition, $video_link, $itemId, $uid);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('Update failed.');
            }
            $stmt->close();

            // Update gallery rows (if replacements provided)
            if (!empty($replacements)) {
                $stmt = $conn->prepare("
                    UPDATE furniture_item_images
                    SET filename = ?
                    WHERE item_id = ? AND sort_order = ?
                    LIMIT 1
                ");

                foreach ($replacements as $sortOrder => $newFile) {
                    $so = (int)$sortOrder;

                    // Ensure a row exists; if not, insert (safety)
                    if (empty($gallery[$so])) {
                        $ins = $conn->prepare("
                            INSERT INTO furniture_item_images (item_id, filename, sort_order)
                            VALUES (?, ?, ?)
                        ");
                        $ins->bind_param('isi', $itemId, $newFile, $so);
                        if (!$ins->execute()) {
                            $ins->close();
                            throw new Exception('Failed to insert gallery image.');
                        }
                        $ins->close();
                    } else {
                        $stmt->bind_param('sii', $newFile, $itemId, $so);
                        if (!$stmt->execute()) {
                            $stmt->close();
                            throw new Exception('Failed to update gallery image.');
                        }
                    }

                    // If cover (image 1) changed, update furniture_items.photo too
                    if ($so === 1) {
                        $c = $conn->prepare("UPDATE furniture_items SET photo = ? WHERE id = ? AND user_id = ? LIMIT 1");
                        $c->bind_param('sii', $newFile, $itemId, $uid);
                        if (!$c->execute()) {
                            $c->close();
                            throw new Exception('Failed to update cover photo.');
                        }
                        $c->close();
                    }
                }

                // close update stmt if it was used
                if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                    $stmt->close();
                }
            }

            $conn->commit();

            // Delete old files after successful commit
            foreach ($oldFilesToDelete as $old) {
                $path = UPLOAD_DIR . '/' . $old;
                if (is_file($path)) @unlink($path);
            }

            setFlash('success', 'Listing updated successfully!');
            redirect(SITE_URL . '/item.php?id=' . $itemId);

        } catch (Throwable $e) {
            $conn->rollback();

            // Delete new files if DB failed
            foreach ($replacements as $fn) {
                $p = UPLOAD_DIR . '/' . $fn;
                if (is_file($p)) @unlink($p);
            }

            $errors[] = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-9 col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="text-center mb-2"><i class="bi bi-pencil-square text-success"></i> Edit Listing</h2>
                <p class="text-center text-muted small mb-4">Update your furniture details</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="item_id" value="<?= (int)$itemId ?>">

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
                        <label class="form-label">Photos (replace any)</label>
                        <div class="row g-3">
                            <?php for ($k = 1; $k <= 3; $k++): ?>
                                <?php $fn = $gallery[$k]['filename'] ?? ($item['photo'] ?? 'placeholder.jpg'); ?>
                                <div class="col-md-4">
                                    <div class="border rounded p-2 h-100">
                                        <div class="small text-muted mb-2">Image <?= $k ?></div>
                                        <img src="<?= UPLOAD_URL . '/' . h($fn) ?>"
                                             class="img-fluid rounded border mb-2"
                                             style="width:100%;height:140px;object-fit:cover;"
                                             alt="Image <?= $k ?>">
                                        <input type="file" class="form-control form-control-sm" name="photo<?= $k ?>" accept="image/*">
                                        <div class="form-text">Optional replacement</div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success flex-grow-1">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                        <a href="<?= SITE_URL ?>/my_listings.php" class="btn btn-outline-secondary">Back</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>