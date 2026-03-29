<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/admin_header.php';

$stats = [];
$r = $conn->query("SELECT COUNT(*) AS c FROM users"); $stats['users'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) AS c FROM furniture_items"); $stats['items'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) AS c FROM furniture_items WHERE status='available'"); $stats['available'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) AS c FROM messages"); $stats['messages'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) AS c FROM requests WHERE status='pending'"); $stats['pending'] = $r->fetch_assoc()['c'];
?>

<h2 class="mb-4"><i class="bi bi-speedometer2 text-warning"></i> Dashboard</h2>

<div class="row g-4 mb-5">
    <div class="col-md col-6">
        <div class="card text-white bg-primary shadow"><div class="card-body text-center">
            <i class="bi bi-people display-4"></i><h3 class="mt-2"><?= $stats['users'] ?></h3><p class="mb-0">Users</p>
        </div></div>
    </div>
    <div class="col-md col-6">
        <div class="card text-white bg-success shadow"><div class="card-body text-center">
            <i class="bi bi-box-seam display-4"></i><h3 class="mt-2"><?= $stats['items'] ?></h3><p class="mb-0">Total Items</p>
        </div></div>
    </div>
    <div class="col-md col-6">
        <div class="card text-white bg-info shadow"><div class="card-body text-center">
            <i class="bi bi-check-circle display-4"></i><h3 class="mt-2"><?= $stats['available'] ?></h3><p class="mb-0">Available</p>
        </div></div>
    </div>
    <div class="col-md col-6">
        <div class="card text-white bg-secondary shadow"><div class="card-body text-center">
            <i class="bi bi-envelope display-4"></i><h3 class="mt-2"><?= $stats['messages'] ?></h3><p class="mb-0">Messages</p>
        </div></div>
    </div>
    <div class="col-md col-6">
        <div class="card text-white bg-warning shadow"><div class="card-body text-center">
            <i class="bi bi-hourglass-split display-4"></i><h3 class="mt-2"><?= $stats['pending'] ?></h3><p class="mb-0">Pending</p>
        </div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <a href="<?= ADMIN_URL ?>/manage_users.php" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body text-center py-4">
                <i class="bi bi-people-fill display-3 text-primary"></i>
                <h5 class="mt-3">Manage Users</h5>
                <p class="text-muted small">View, search, delete local user accounts</p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= ADMIN_URL ?>/manage_items.php" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body text-center py-4">
                <i class="bi bi-grid-fill display-3 text-success"></i>
                <h5 class="mt-3">Manage Items</h5>
                <p class="text-muted small">View, delete furniture listings</p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= ADMIN_URL ?>/add_item.php" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body text-center py-4">
                <i class="bi bi-plus-square-fill display-3 text-warning"></i>
                <h5 class="mt-3">Add Item</h5>
                <p class="text-muted small">Post furniture on behalf of any user</p>
            </div>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>