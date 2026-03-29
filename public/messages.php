<?php
$pageTitle = 'Messages — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

$uid = currentUserId();
$tab = $_GET['tab'] ?? 'inbox';

// INBOX
$stmt = $conn->prepare("
    SELECT m.*, u.username AS sender_name, fi.title AS item_title
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    LEFT JOIN furniture_items fi ON m.item_id = fi.id
    WHERE m.receiver_id = ?
    ORDER BY m.created_at DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$inbox = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// SENT
$stmt = $conn->prepare("
    SELECT m.*, u.username AS receiver_name, fi.title AS item_title
    FROM messages m
    JOIN users u ON m.receiver_id = u.id
    LEFT JOIN furniture_items fi ON m.item_id = fi.id
    WHERE m.sender_id = ?
    ORDER BY m.created_at DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$outbox = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// REQUESTS (received by owner)
$stmt = $conn->prepare("
    SELECT r.*, u.username AS requester_name, fi.title AS item_title
    FROM requests r
    JOIN users u ON r.requester_id = u.id
    JOIN furniture_items fi ON r.item_id = fi.id
    WHERE r.owner_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Mark inbox messages as read
$stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0");
$stmt->bind_param('i', $uid);
$stmt->execute();
$stmt->close();

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4"><i class="bi bi-chat-dots"></i> My Messages</h2>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $tab==='inbox'?'active':'' ?>" href="?tab=inbox">
            <i class="bi bi-inbox"></i> Inbox <span class="badge bg-success"><?= count($inbox) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab==='sent'?'active':'' ?>" href="?tab=sent">
            <i class="bi bi-send"></i> Sent <span class="badge bg-secondary"><?= count($outbox) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab==='requests'?'active':'' ?>" href="?tab=requests">
            <i class="bi bi-hand-index"></i> Requests <span class="badge bg-warning text-dark"><?= count($requests) ?></span>
        </a>
    </li>
</ul>

<?php if ($tab === 'inbox'): ?>

    <?php if (empty($inbox)): ?>
        <p class="text-center text-muted py-4">
            <i class="bi bi-inbox display-4"></i><br>No messages yet.
        </p>
    <?php else: ?>
        <div class="list-group">
        <?php foreach ($inbox as $msg): ?>
            <div class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between">
                    <h6><i class="bi bi-person"></i> <?= h($msg['sender_name']) ?> — <?= h($msg['subject']) ?></h6>
                    <small class="text-muted"><?= date('M j, H:i', strtotime($msg['created_at'])) ?></small>
                </div>
                <p class="mb-1"><?= nl2br(h($msg['body'])) ?></p>
                <?php if (!empty($msg['item_title'])): ?>
                    <small class="text-muted">
                        Re: <a href="<?= SITE_URL ?>/item.php?id=<?= (int)$msg['item_id'] ?>"><?= h($msg['item_title']) ?></a>
                    </small>
                <?php endif; ?>
                <div class="mt-2">
                    <a href="<?= SITE_URL ?>/send_message.php?to=<?= (int)$msg['sender_id'] ?>&item=<?= (int)($msg['item_id'] ?? 0) ?>"
                       class="btn btn-sm btn-outline-success">
                        <i class="bi bi-reply"></i> Reply
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php elseif ($tab === 'sent'): ?>

    <?php if (empty($outbox)): ?>
        <p class="text-center text-muted py-4">
            <i class="bi bi-send display-4"></i><br>No sent messages.
        </p>
    <?php else: ?>
        <div class="list-group">
        <?php foreach ($outbox as $msg): ?>
            <div class="list-group-item">
                <div class="d-flex justify-content-between">
                    <h6>To: <?= h($msg['receiver_name']) ?> — <?= h($msg['subject']) ?></h6>
                    <small class="text-muted"><?= date('M j, H:i', strtotime($msg['created_at'])) ?></small>
                </div>
                <p class="mb-1"><?= nl2br(h($msg['body'])) ?></p>
                <?php if (!empty($msg['item_title'])): ?>
                    <small class="text-muted">
                        Re: <a href="<?= SITE_URL ?>/item.php?id=<?= (int)$msg['item_id'] ?>"><?= h($msg['item_title']) ?></a>
                    </small>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php elseif ($tab === 'requests'): ?>

    <?php if (empty($requests)): ?>
        <p class="text-muted text-center py-4">
            <i class="bi bi-hand-index display-4"></i><br>No item requests.
        </p>
    <?php else: ?>
        <div class="list-group">
        <?php foreach ($requests as $req): ?>
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">
                            <i class="bi bi-person"></i> <?= h($req['requester_name']) ?>
                            wants <a href="<?= SITE_URL ?>/item.php?id=<?= (int)$req['item_id'] ?>"><?= h($req['item_title']) ?></a>
                        </h6>
                        <?php if (!empty($req['message'])): ?>
                            <p class="mb-1 text-muted small">"<?= h($req['message']) ?>"</p>
                        <?php endif; ?>
                        <small class="text-muted"><?= date('M j, Y H:i', strtotime($req['created_at'])) ?></small>
                    </div>

                    <div class="text-end">
                        <span class="badge bg-<?= $req['status'] === 'pending' ? 'warning text-dark' : ($req['status'] === 'accepted' ? 'success' : 'danger') ?>">
                            <?= ucfirst($req['status']) ?>
                        </span>
                    </div>
                </div>

                <?php if ($req['status'] === 'pending'): ?>
                    <div class="mt-3 d-flex gap-2 flex-wrap">
                        <form action="<?= SITE_URL ?>/respond_request.php" method="POST"
                              onsubmit="return confirm('Accept this request? This will mark the item as requested and notify the requester.');">
                            <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                            <input type="hidden" name="action" value="accept">
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="bi bi-check-circle"></i> Accept
                            </button>
                        </form>

                        <form action="<?= SITE_URL ?>/respond_request.php" method="POST"
                              onsubmit="return confirm('Decline this request and notify the requester?');">
                            <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                            <input type="hidden" name="action" value="decline">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-x-circle"></i> Decline
                            </button>
                        </form>

                        <a href="<?= SITE_URL ?>/send_message.php?to=<?= (int)$req['requester_id'] ?>&item=<?= (int)$req['item_id'] ?>"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-chat-dots"></i> Message
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <?php
      // Unknown tab -> fallback
      redirect(SITE_URL . '/messages.php?tab=inbox');
    ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>