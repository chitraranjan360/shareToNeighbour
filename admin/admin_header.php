<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle ?? 'Admin — ' . SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="<?= ADMIN_URL ?>/dashboard.php">
            <i class="bi bi-shield-lock-fill"></i> Admin Panel
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= ADMIN_URL ?>/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= ADMIN_URL ?>/manage_users.php"><i class="bi bi-people"></i> Users</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= ADMIN_URL ?>/manage_items.php"><i class="bi bi-grid"></i> Items</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= ADMIN_URL ?>/add_item.php"><i class="bi bi-plus-circle"></i> Add Item</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link text-muted" href="<?= SITE_URL ?>/index.php"><i class="bi bi-house"></i> Main Site</a></li>
                <li class="nav-item">
                    <span class="nav-link text-warning"><i class="bi bi-person-badge"></i> <?= h(currentAdminName()) ?></span>
                </li>
                <li class="nav-item"><a class="nav-link text-danger" href="<?= ADMIN_URL ?>../../public/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?php if ($s = getFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= h($s) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($e = getFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= h($e) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>