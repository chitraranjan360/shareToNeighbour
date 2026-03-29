<?php
$pageTitle = 'Admin Dashboard — ShareToNeighbour';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// ★ ADMIN ONLY
requireAdmin();

$uid = currentUserId();

// Stats
$stats = [];

$result = $conn->query("SELECT COUNT(*) AS cnt FROM users");
$stats['users'] = $result->fetch_assoc()['cnt'];

$result = $conn->query("SELECT COUNT(*) AS cnt FROM furniture_items");
$stats['items'] = $result->fetch_assoc()['cnt'];

$result = $conn->query("SELECT COUNT(*) AS cnt FROM furniture_items WHERE status = 'available'");
$stats['available'] = $result->fetch_assoc()['cnt'];

$result = $conn->query("SELECT COUNT(*) AS cnt FROM messages");
$stats['messages'] = $result->fetch_assoc()['cnt'];

$result = $conn->query("SELECT COUNT(*) AS cnt FROM requests WHERE status = 'pending'");
$stats['pending_requests'] = $result->fetch_assoc()['cnt'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-shield-lock-fill text-warning"></i> Admin Dashboard</h2>
    <span class="badge bg-warning text-dark fs-6">
        <i class="bi bi-person-badge"></i> <?= h(currentUserName()) ?> (Admin)
    </span>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-5">
    <div class="col-md-4 col-lg">
        <div class="card text-white bg-primary shadow">
            <div class="card-body text-center">
                <i class="bi bi-people display-4"></i>
                <h3 class="mt-2"><?= $stats['users'] ?></h3>
                <p class="mb-0">Total Users</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg">
        <div class="card text-white bg-success shadow">
            <div class="card-body text-center">
                <i class="bi bi-box-seam display-4"></i>
                <h3 class="mt-2"><?= $stats['items'] ?></h3>
                <p class="mb-0">Total Items</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg">
        <div class="card text-white bg-info shadow">
            <div class="card-body text-center">
                <i class="bi bi-check-circle display-4"></i>
                <h3 class="mt-2"><?= $stats['available'] ?></h3>
                <p class="mb-0">Available</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg">
        <div class="card text-white bg-secondary shadow">
            <div class="card-body text-center">
                <i class="bi bi-envelope display-4"></i>
                <h3 class="mt-2"><?= $stats['messages'] ?></h3>
                <p class="mb-0">Messages</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg">
        <div class="card text-white bg-warning shadow">
            <div class="card-body text-center">
                <i class="bi bi-hourglass-split display-4"></i>
                <h3 class="mt-2"><?= $stats['pending_requests'] ?></h3>
                <p class="mb-0">Pending Requests</p>
            </div>
        </div>
    </div>
</div>

<!-- Admin Quick Links -->
<div class="row g-4">
    <div class="col-md-4">
        <a href="<?= SITE_URL ?>/admin/manage_users.php" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body text-center py-4">
                <i class="bi bi-people-fill display-3 text-primary"></i>
                <h5 class="mt-3">Manage Users</h5>
                <p class="text-muted small">View, search, and delete user accounts</p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= SITE_URL ?>/admin/manage_items.php" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body text-center py-4">
                <i class="bi bi-grid-fill display-3 text-success"></i>
                <h5 class="mt-3">Manage Items</h5>
                <p class="text-muted small">View, delete, and manage furniture listings</p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= SITE_URL ?>/admin/add_item.php" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body text-center py-4">
                <i class="bi bi-plus-square-fill display-3 text-warning"></i>
                <h5 class="mt-3">Add Item</h5>
                <p class="text-muted small">Post a new furniture listing as admin</p>
            </div>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>