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

<section class="admin-hero rounded-4 p-4 p-md-5 mb-4">
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
    <div>
      <p class="text-uppercase small fw-semibold mb-2 text-primary">Admin overview</p>
      <h1 class="h3 mb-2"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
      <p class="text-body-secondary mb-0">Monitor activity, review platform health, and quickly jump into moderation tasks.</p>
    </div>

    <div class="card border-0 shadow-sm bg-white rounded-4">
      <div class="card-body py-3 px-4">
        <div class="small text-uppercase text-body-secondary fw-semibold">Quick scope</div>
        <div class="fw-semibold">Users, items, pending requests, and messages</div>
      </div>
    </div>
  </div>
</section>

<div class="row g-3 g-md-4">
  <div class="col-12 col-sm-6 col-xl">
    <div class="admin-stat stat-primary h-100">
      <div class="stat-icon"><i class="bi bi-people"></i></div>
      <div class="stat-meta">
        <div class="stat-value"><?= (int)$stats['users'] ?></div>
        <div class="stat-label">Users</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl">
    <div class="admin-stat stat-success h-100">
      <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
      <div class="stat-meta">
        <div class="stat-value"><?= (int)$stats['items'] ?></div>
        <div class="stat-label">Total Items</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl">
    <div class="admin-stat stat-info h-100">
      <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
      <div class="stat-meta">
        <div class="stat-value"><?= (int)$stats['available'] ?></div>
        <div class="stat-label">Available</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl">
    <div class="admin-stat stat-secondary h-100">
      <div class="stat-icon"><i class="bi bi-envelope"></i></div>
      <div class="stat-meta">
        <div class="stat-value"><?= (int)$stats['messages'] ?></div>
        <div class="stat-label">Messages</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl">
    <div class="admin-stat stat-warning h-100">
      <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
      <div class="stat-meta">
        <div class="stat-value"><?= (int)$stats['pending'] ?></div>
        <div class="stat-label">Pending Requests</div>
      </div>
    </div>
  </div>
</div>


<?php require_once __DIR__ . '/admin_footer.php'; ?>