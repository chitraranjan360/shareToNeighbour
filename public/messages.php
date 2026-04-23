<?php
$pageTitle = 'Messages — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

$uid = currentUserId();
$tab = $_GET['tab'] ?? 'chats';
if (!in_array($tab, ['chats', 'requests'], true)) $tab = 'chats';

$wsNotify = $_SESSION['ws_notify'] ?? null;
unset($_SESSION['ws_notify']);

// Chats: one row per conversation/user
$stmt = $conn->prepare("
    SELECT
      u.id AS other_user_id,
      u.username AS other_username,
      u.full_name AS other_full_name,
      u.is_online AS other_online,
      u.last_seen AS other_last_seen,
      m.id AS last_message_id,
      m.sender_id,
      m.receiver_id,
      m.subject,
      m.body,
      m.created_at,
      (
        SELECT COUNT(*)
        FROM messages um
        WHERE um.sender_id = u.id
          AND um.receiver_id = ?
          AND um.is_read = 0
      ) AS unread_count
    FROM users u
    JOIN messages m ON m.id = (
      SELECT m2.id
      FROM messages m2
      WHERE (m2.sender_id = ? AND m2.receiver_id = u.id)
         OR (m2.sender_id = u.id AND m2.receiver_id = ?)
      ORDER BY m2.created_at DESC, m2.id DESC
      LIMIT 1
    )
    WHERE u.id <> ?
      AND EXISTS (
        SELECT 1
        FROM messages mx
        WHERE (mx.sender_id = ? AND mx.receiver_id = u.id)
           OR (mx.sender_id = u.id AND mx.receiver_id = ?)
      )
    ORDER BY m.created_at DESC, m.id DESC
");
$stmt->bind_param('iiiiii', $uid, $uid, $uid, $uid, $uid, $uid);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Requests tab (owner receives these)
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

$totalUnread = 0;
foreach ($conversations as $c) $totalUnread += (int)$c['unread_count'];

$pendingRequests = 0;
foreach ($requests as $r) {
    if (($r['status'] ?? '') === 'pending') $pendingRequests++;
}




require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="css/message.css">
<div class="msg-shell">

  <ul class="nav nav-tabs mb-3">
    <li class="nav-item">
      <a class="nav-link <?= $tab==='chats'?'active':'' ?>" href="<?= SITE_URL ?>/messages.php?tab=chats">
        <i class="bi bi-chat-left-text me-1"></i>Chats
        <span class="badge bg-success ms-1"><?= (int)$totalUnread ?></span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $tab==='requests'?'active':'' ?>" href="<?= SITE_URL ?>/messages.php?tab=requests">
        <i class="bi bi-hand-index me-1"></i>Requests
        <span class="badge bg-warning text-dark ms-1"><?= (int)$pendingRequests ?></span>
      </a>
    </li>
  </ul>

  <?php if ($tab === 'chats'): ?>
    <div class="msg-card">
      <div class="msg-head d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Conversations</h5>
        <span class="badge bg-success-subtle text-success border"><?= (int)$totalUnread ?> unread</span>
      </div>

      <div class="msg-list">
        <?php if (empty($conversations)): ?>
          <div class="empty">
            <i class="bi bi-chat-square-text fs-1 d-block"></i>
            No conversations yet.
          </div>
        <?php else: ?>
          <?php foreach ($conversations as $c): ?>
            <?php
              $otherId = (int)$c['other_user_id'];
              $name = trim((string)($c['other_full_name'] ?: $c['other_username']));
              $initials = strtoupper(substr($name, 0, 1));
              $isMine = ((int)$c['sender_id'] === $uid);
              $preview = trim((string)$c['body']);
              if ($preview === '') $preview = '(No text)';
              $preview = mb_strimwidth($preview, 0, 95, '…');
            ?>
            <a href="<?= SITE_URL ?>/chat_thread.php?user=<?= $otherId ?>" class="item">
              <div class="d-flex justify-content-between gap-3">
                <div class="d-flex gap-3 align-items-start flex-grow-1">
                  <div class="avatar">
                    <?= h($initials) ?>
                    <span id="user-dot-<?= $otherId ?>" class="dot <?= ((int)$c['other_online']===1?'on':'off') ?>"></span>
                  </div>
                  <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2">
                      <span class="name"><?= h($name) ?></span>
                      <small id="user-status-<?= $otherId ?>" class="text-muted">
                        <?= ((int)$c['other_online']===1) ? 'Online' : 'Offline' ?>
                      </small>
                    </div>
                    <div class="preview mt-1">
                      <?= $isMine ? '<span class="text-muted">You: </span>' : '' ?><?= h($preview) ?>
                    </div>
                  </div>
                </div>

                <div class="text-end">
                  <div class="d-flex align-items-center justify-content-end gap-2">
                    <div class="time"><?= date('M j, H:i', strtotime($c['created_at'])) ?></div>
                    <?php if ((int)$c['last_message_id'] > 0): ?>
                      <div class="dropdown" onclick="event.stopPropagation();">
                        <button class="btn btn-sm btn-outline-secondary" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false"
                                aria-label="Conversation options">
                          <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-extra-sm p-2  rounded">
                          <li>
                            <form action="<?= SITE_URL ?>/delete_message.php" method="POST"
                                  onclick="event.stopPropagation();"
                                  onsubmit="return confirm('Delete this conversation? This will remove all messages in this thread for both parties.');">
                              <input type="hidden" name="message_id" value="<?= (int)$c['last_message_id'] ?>">
                              <input type="hidden" name="return_tab" value="chats">
                              <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-trash me-2"></i>Delete Chat
                              </button>
                            </form>
                          </li>
                        </ul>
                      </div>
                    <?php endif; ?>
                  </div>
                  <?php if ((int)$c['unread_count'] > 0): ?>
                    <span class="badge bg-danger badge-unread mt-2"><?= (int)$c['unread_count'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  <?php else: ?>
    <div class="msg-card">
      <div class="msg-head d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-hand-index me-2"></i>Item Requests</h5>
        <span class="badge bg-warning text-dark"><?= (int)$pendingRequests ?> pending</span>
      </div>

      <div class="msg-list">
        <?php if (empty($requests)): ?>
          <div class="empty">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            No requests found.
          </div>
        <?php else: ?>
          <?php foreach ($requests as $req): ?>
            <div class="item">
              <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                  <div class="fw-semibold">
                    <i class="bi bi-person"></i> <?= h($req['requester_name']) ?>
                    requested:
                    <a href="<?= SITE_URL ?>/item.php?id=<?= (int)$req['item_id'] ?>"><?= h($req['item_title']) ?></a>
                  </div>

                  <?php if (!empty($req['message'])): ?>
                    <div class="text-muted small mt-1">"<?= h($req['message']) ?>"</div>
                  <?php endif; ?>

                  <small class="text-muted d-block mt-1"><?= date('M j, Y H:i', strtotime($req['created_at'])) ?></small>
                </div>

                <div class="text-end">
                  <span class="badge bg-<?= $req['status']==='pending' ? 'warning text-dark' : ($req['status']==='accepted' ? 'success' : 'secondary') ?>">
                    <?= ucfirst(h($req['status'])) ?>
                  </span>
                </div>
              </div>

              <?php if (($req['status'] ?? '') === 'pending'): ?>
                <div class="mt-3 d-flex gap-2 flex-wrap">
                  <form action="<?= SITE_URL ?>/respond_request.php" method="POST"
                        onsubmit="return confirm('Accept this request?');">
                    <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                    <input type="hidden" name="action" value="accept">
                    <button type="submit" class="btn btn-sm btn-success">
                      <i class="bi bi-check-circle"></i> Accept
                    </button>
                  </form>

                  <form action="<?= SITE_URL ?>/respond_request.php" method="POST"
                        onsubmit="return confirm('Decline this request?');">
                    <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                    <input type="hidden" name="action" value="decline">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                      <i class="bi bi-x-circle"></i> Decline
                    </button>
                  </form>

                  <a href="<?= SITE_URL ?>/chat_thread.php?user=<?= (int)$req['requester_id'] ?>"
                     class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-chat-dots"></i> Message
                  </a>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<script>
const CURRENT_USER_ID = <?= (int)$uid ?>;
const WS_HOST = window.location.hostname;
let ws = null;
let wsNotifySent = false;

function setPresence(userId, isOnline) {
  const dot = document.getElementById(`user-dot-${userId}`);
  const status = document.getElementById(`user-status-${userId}`);
  if (dot) {
    dot.classList.remove('on', 'off');
    dot.classList.add(isOnline ? 'on' : 'off');
  }
  if (status) status.textContent = isOnline ? 'Online' : 'Offline';
}

function connectMessagesSocket() {
  ws = new WebSocket(`ws://${WS_HOST}:8080?user_id=${CURRENT_USER_ID}`);

  ws.onopen = () => {
    <?php if (!empty($wsNotify)): ?>
    if (!wsNotifySent) {
      ws.send(JSON.stringify({
        type: 'chat',
        to: <?= (int)$wsNotify['to'] ?>,
        message_id: <?= (int)$wsNotify['message_id'] ?>,
        subject: <?= json_encode($wsNotify['subject']) ?>,
        body: <?= json_encode($wsNotify['body']) ?>
      }));
      wsNotifySent = true;
    }
    <?php endif; ?>
  };

  ws.onmessage = (event) => {
    let data;
    try {
      data = JSON.parse(event.data);
    } catch (_e) {
      return;
    }

    if (data.type === 'new_message' && Number(data.to) === Number(CURRENT_USER_ID)) {
      location.reload();
      return;
    }

    if (data.type === 'new_request') {
      location.reload();
      return;
    }

    if (data.type === 'presence') {
      setPresence(Number(data.user_id), Number(data.is_online) === 1);
    }
  };

  ws.onerror = () => {
    try { ws.close(); } catch (_e) {}
  };

  ws.onclose = () => {
    setTimeout(connectMessagesSocket, 1500);
  };
}

connectMessagesSocket();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>