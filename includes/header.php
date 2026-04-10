<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$badgeTotal = 0;
if (isUserLoggedIn()) {
    $uid = currentUserId();
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM notifications WHERE user_id=? AND is_seen=0");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $badgeTotal = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle ?? SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/index.css">
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm fancy-nav sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= SITE_URL ?>/index.php">
                <span class="brand-icon d-inline-flex align-items-center justify-content-center me-2">
                    <i class="bi bi-house-heart-fill"></i>
                </span>
                <span><?= SITE_NAME ?></span>
            </a>

            <button class="navbar-toggler border-0 p-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/browse.php">
                            <i class="bi bi-search me-1"></i>Search
                        </a>
                    </li>
                    <?php if (isUserLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/upload.php">
                                <i class="bi bi-plus-circle me-1"></i>Share
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav align-items-lg-center">
                    <?php if (isUserLoggedIn()): ?>

                        <!-- Bell Icon + Dropdown Notifications -->
                        <li class="nav-item dropdown me-lg-2">
                            <a class="nav-link position-relative d-inline-flex align-items-center"
                               href="#"
                               id="notifBell"
                               role="button"
                               data-bs-toggle="dropdown"
                               aria-expanded="false"
                               title="Notifications">
                                <i class="bi bi-bell fs-5"></i>
                                <span id="globalMessageBadge"
                                      class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= $badgeTotal > 0 ? '' : 'd-none' ?>">
                                    <?= (int)$badgeTotal ?>
                                </span>
                            </a>

                            <div class="dropdown-menu dropdown-menu-end p-0 shadow" style="width:360px; max-height:420px; overflow:hidden;">
                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                    <strong>Notifications</strong>
                                    <button id="markAllSeenBtn" class="btn btn-sm btn-link text-decoration-none p-0">Mark all seen</button>
                                </div>
                                <div id="notifList" style="max-height:360px; overflow:auto;">
                                    <div class="p-3 text-muted small">Loading...</div>
                                </div>
                            </div>
                        </li>

                        <!-- Messages text link (optional keep) -->
                        <li class="nav-item me-lg-2">
                            <a class="nav-link" href="<?= SITE_URL ?>/messages.php">
                                <i class="bi bi-chat-dots me-1"></i>Messages
                            </a>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="avatar-placeholder">
                                    <i class="bi bi-person-circle"></i>
                                </span>
                                <span><?= h(currentUserName()) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><a class="dropdown-item" href="<?= SITE_URL ?>/profile.php"><i class="bi bi-person-badge me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="<?= SITE_URL ?>/messages.php"><i class="bi bi-envelope-open me-2"></i>Messages</a></li>
                                <li><a class="dropdown-item" href="<?= SITE_URL ?>/my_listings.php"><i class="bi bi-grid"></i> My Listings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= SITE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i><span>Account</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><a class="dropdown-item" href="<?= SITE_URL ?>/login.php"><i class="bi bi-box-arrow-in-right me-2"></i>Login</a></li>
                                <li><a class="dropdown-item" href="<?= SITE_URL ?>/register.php"><i class="bi bi-person-plus me-2"></i>Register</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <?php if ($s = getFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= h($s) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($e = getFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= h($e) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

<?php if (isUserLoggedIn()): ?>
<script>
(function () {
    const bell = document.getElementById('notifBell');
    const list = document.getElementById('notifList');
    const badge = document.getElementById('globalMessageBadge');
    const markBtn = document.getElementById('markAllSeenBtn');
    if (!bell || !list) return;

    function esc(s = '') {
        return String(s).replace(/[&<>"']/g, m => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[m]));
    }

    function formatTime(v) {
        try {
            return new Date(String(v).replace(' ', 'T')).toLocaleString();
        } catch(e) {
            return v || '';
        }
    }

    async function loadNotifications() {
        try {
            const res = await fetch('<?= SITE_URL ?>/api_notifications.php?action=list', { credentials: 'same-origin' });
            const json = await res.json();

            if (!json.ok) {
                list.innerHTML = `<div class="p-3 text-danger small">Failed to load notifications.</div>`;
                return;
            }

            const rows = json.data || [];
            if (!rows.length) {
                list.innerHTML = `<div class="p-3 text-muted small">No notifications.</div>`;
                return;
            }

            list.innerHTML = rows.map(n => `
                <div class="px-3 py-2 border-bottom ${Number(n.is_seen) === 0 ? 'bg-light' : ''}">
                    <div class="d-flex justify-content-between gap-2">
                        <div class="fw-semibold small">${esc(n.title || '')}</div>
                        <small class="text-muted text-nowrap">${esc(formatTime(n.created_at || ''))}</small>
                    </div>
                    ${n.body ? `<div class="small text-muted mt-1">${esc(n.body)}</div>` : ``}
                </div>
            `).join('');
        } catch (e) {
            list.innerHTML = `<div class="p-3 text-danger small">Failed to load notifications.</div>`;
        }
    }

    async function markSeen() {
        try {
            await fetch('<?= SITE_URL ?>/api_notifications.php?action=mark_seen', { credentials: 'same-origin' });
        } catch (e) {}
        if (badge) {
            badge.textContent = '0';
            badge.classList.add('d-none');
        }
        list.querySelectorAll('.bg-light').forEach(el => el.classList.remove('bg-light'));
    }

    bell.addEventListener('show.bs.dropdown', async () => {
        await loadNotifications();
        await markSeen();
    });

    markBtn?.addEventListener('click', async (e) => {
        e.preventDefault();
        await markSeen();
    });

    window.bumpNotificationBadge = function() {
        if (!badge) return;
        let n = parseInt(badge.textContent || '0', 10);
        n++;
        badge.textContent = String(n);
        badge.classList.remove('d-none');
    };
})();
</script>
<?php endif; ?>