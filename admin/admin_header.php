<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle ?? 'Admin — ' . SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/admin.css">
</head>
<body class="bg-body-tertiary">

<nav class="navbar navbar-expand-lg navbar-dark admin-nav sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= ADMIN_URL ?>/dashboard.php">
            <span class="admin-brand-badge">
                <i class="bi bi-shield-lock-fill"></i>
            </span>
            <span>Admin Panel</span>
            <span class="badge rounded-pill text-bg-warning ms-1">Secure</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/dashboard.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'manage_users.php' || $currentPage === 'user_details.php' ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/manage_users.php">
                        <i class="bi bi-people me-1"></i>Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'manage_items.php' ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/manage_items.php">
                        <i class="bi bi-grid me-1"></i>Items
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'add_item.php' ? 'active' : '' ?>" href="<?= ADMIN_URL ?>/add_item.php">
                        <i class="bi bi-plus-circle me-1"></i>Add Item
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav align-items-lg-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="admin-avatar">
                            <i class="bi bi-person-badge"></i>
                        </span>
                        <span class="d-none d-lg-inline"><?= h(currentAdminName()) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <span class="dropdown-item-text text-muted small">
                                Signed in as <strong><?= h(currentAdminName()) ?></strong>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= ADMIN_URL ?>../../public/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4 pb-5">
    <?php if ($s = getFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= h($s) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($e = getFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= h($e) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>