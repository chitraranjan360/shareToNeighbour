<?php
$pageTitle = 'Add Item';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/image_resize.php';
requireAdminLogin();

$allUsers = $conn->query("SELECT id, username, full_name FROM users ORDER BY username")->fetch_all(MYSQLI_ASSOC);

$errors = [];
$postAsUser = ''; $title = $description = $video_link = '';
$category = 'other'; $condition = 'good';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAsUser  = (int)($_POST['user_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = $_POST['category'] ?? 'other';
    $condition   = $_POST['condition_level'] ?? 'good';
    $video_link  = trim($_POST['video_link'] ?? '');

    if ($postAsUser <= 0) $errors[] = 'Select a user.';
    if (strlen($title) < 3) $errors[] = 'Title too short.';
    if (strlen($description) < 10) $errors[] = 'Description too short.';

    $stmt = $conn->prepare("SELECT id, latitude, longitude FROM users WHERE id = ?");
    $stmt->bind_param('i', $postAsUser); $stmt->execute();
    $tUser = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$tUser) $errors[] = 'User not found.';

    $photoName = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['photo']['tmp_name']); finfo_close($finfo);
        if (!in_array($mime, ['image/jpeg','image/png','image/gif','image/webp'])) $errors[] = 'Invalid image type.';
        else {
            $photoName = 'item_' . time() . '_' . mt_rand(1000,9999) . '.jpg';
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
            if (!resizeAndSaveImage($_FILES['photo']['tmp_name'], UPLOAD_DIR . $photoName)) {
                $errors[] = 'Image processing failed.'; $photoName = null;
            }
        }
    } else $errors[] = 'Photo required.';

    if (empty($errors)) {
        $lat = $tUser['latitude'] ?? 55.6761; $lng = $tUser['longitude'] ?? 12.5683;
        $stmt = $conn->prepare("INSERT INTO furniture_items (user_id,title,description,category,condition_level,photo,video_link,latitude,longitude) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('issssssdd', $postAsUser, $title, $description, $category, $condition, $photoName, $video_link, $lat, $lng);
        if ($stmt->execute()) {
            setFlash('success', 'Item "' . $title . '" added!');
            redirect(ADMIN_URL . '/manage_items.php');
        } else $errors[] = 'Save failed.';
        $stmt->close();
    }
}

require_once __DIR__ . '/admin_header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-warning"><h4 class="mb-0"><i class="bi bi-plus-square-fill"></i> Add Furniture Item</h4></div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><ul class="mb-0">
                    <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                </ul></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" novalidate>
                    <div class="mb-3 p-3 bg-light rounded border">
                        <label class="form-label fw-bold"><i class="bi bi-person-badge"></i> Post as User</label>
                        <select class="form-select" name="user_id" required>
                            <option value="">— Select a local user —</option>
                            <?php foreach ($allUsers as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $postAsUser==$u['id']?'selected':'' ?>>
                                <?= h($u['username']) ?> — <?= h($u['full_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
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
                                <option value="like_new">Like New</option>
                                <option value="good" selected>Good</option>
                                <option value="fair">Fair</option>
                                <option value="needs_repair">Needs Repair</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Photo *</label>
                        <input type="file" class="form-control" name="photo" accept="image/*" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Video Link <small class="text-muted">(optional)</small></label>
                        <input type="url" class="form-control" name="video_link" value="<?= h($video_link) ?>">
                    </div>
                    <button type="submit" class="btn btn-warning w-100 btn-lg fw-bold">
                        <i class="bi bi-cloud-upload"></i> Add Item
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>