<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/browse.php');
}

$uid = currentUserId();

$itemId     = (int)($_POST['item_id'] ?? 0);
$requestId  = (int)($_POST['request_id'] ?? 0);
$revieweeId = (int)($_POST['reviewee_id'] ?? 0);
$rating     = (int)($_POST['rating'] ?? 0);
$comment    = trim($_POST['comment'] ?? '');

if ($itemId <= 0 || $requestId <= 0 || $revieweeId <= 0) {
    setFlash('error', 'Invalid review request.');
    redirect(SITE_URL . '/browse.php');
}

if ($rating < 1 || $rating > 5) {
    setFlash('error', 'Please select a rating between 1 and 5.');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
}

// Ensure item is taken
$stmt = $conn->prepare("SELECT id, user_id, status FROM furniture_items WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item || $item['status'] !== 'taken') {
    setFlash('error', 'Review is only allowed after item is marked as TAKEN.');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
}

// Ensure reviewee is the owner
if ((int)$item['user_id'] !== $revieweeId) {
    setFlash('error', 'Invalid review target.');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
}

// Ensure this request belongs to this item, this owner, and this reviewer is requester AND accepted
$stmt = $conn->prepare("
    SELECT id
    FROM requests
    WHERE id = ? AND item_id = ? AND owner_id = ? AND requester_id = ? AND status = 'accepted'
    LIMIT 1
");
$stmt->bind_param('iiii', $requestId, $itemId, $revieweeId, $uid);
$stmt->execute();
$ok = $stmt->get_result()->num_rows > 0;
$stmt->close();

if (!$ok) {
    setFlash('error', 'You are not allowed to review this transaction.');
    redirect(SITE_URL . '/item.php?id=' . $itemId);
}

/**
 * Handle optional image upload safely.
 * DB column `reviews.image` should store a string path, not $_FILES array.
 */
$imagePath = null;

if (isset($_FILES['photo']) && is_array($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $fileErr = (int)($_FILES['photo']['error'] ?? UPLOAD_ERR_OK);

    if ($fileErr !== UPLOAD_ERR_OK) {
        setFlash('error', 'Image upload failed. Please try again.');
        redirect(SITE_URL . '/item.php?id=' . $itemId);
    }

    $tmpPath = $_FILES['photo']['tmp_name'] ?? '';
    $origName = $_FILES['photo']['name'] ?? '';
    $size = (int)($_FILES['photo']['size'] ?? 0);

    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        setFlash('error', 'Invalid uploaded file.');
        redirect(SITE_URL . '/item.php?id=' . $itemId);
    }

    // 5MB max
    if ($size > 5 * 1024 * 1024) {
        setFlash('error', 'Image is too large. Max 5MB allowed.');
        redirect(SITE_URL . '/item.php?id=' . $itemId);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpPath);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    if (!isset($allowed[$mime])) {
        setFlash('error', 'Only JPG, PNG, WEBP, GIF images are allowed.');
        redirect(SITE_URL . '/item.php?id=' . $itemId);
    }

    $ext = $allowed[$mime];
    $uploadDirAbs = __DIR__ . '/../public/uploads/reviews';
    if (!is_dir($uploadDirAbs) && !mkdir($uploadDirAbs, 0775, true)) {
        setFlash('error', 'Could not create upload directory.');
        redirect(SITE_URL . '/item.php?id=' . $itemId);
    }

    $safeBase = bin2hex(random_bytes(16));
    $fileName = 'review_' . $itemId . '_' . $uid . '_' . $safeBase . '.' . $ext;
    $destAbs  = $uploadDirAbs . '/' . $fileName;

    if (!move_uploaded_file($tmpPath, $destAbs)) {
        setFlash('error', 'Could not save uploaded image.');
        redirect(SITE_URL . '/item.php?id=' . $itemId);
    }

    // Store relative web path in DB
    $imagePath = 'uploads/reviews/' . $fileName;
}

// Insert review (unique constraint prevents duplicates)
$stmt = $conn->prepare("
    INSERT INTO reviews (item_id, request_id, reviewer_id, reviewee_id, rating, comment, image)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('iiiiiss', $itemId, $requestId, $uid, $revieweeId, $rating, $comment, $imagePath);

if ($stmt->execute()) {
    setFlash('success', 'Thanks! Your review was submitted.');
} else {
    // If duplicate key, treat as already reviewed
    setFlash('success', 'Review already submitted.');
}
$stmt->close();

redirect(SITE_URL . '/item.php?id=' . $itemId);