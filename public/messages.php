<?php
$pageTitle = 'Messages — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireUserLogin();

$uid = currentUserId();
$wsNotify = $_SESSION['ws_notify'] ?? null;
unset($_SESSION['ws_notify']);

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

$totalUnread = 0;
foreach ($conversations as $c) $totalUnread += (int)$c['unread_count'];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.msg-shell { max-width: 980px; margin: 0 auto; }
.msg-card {
  border: 1px solid #e9ecef;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0,0,0,.04);
}
.msg-head {
  background: #fff;
  border-bottom: 1px solid #eef1f4;
  padding: 14px 18px;
}
.msg-list .item {
  display: block;
  padding: 14px 16px;
  border-bottom: 1px solid #f1f3f5;
  text-decoration: none;
  color: inherit;
  transition: background .15s ease;
}
.msg-list .item:last-child { border-bottom: 0; }
.msg-list .item:hover { background: #f8fafc; }

.avatar {
  width: 42px; height: 42px; border-radius: 50%;
  display: inline-flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 14px;
  color: #fff; background: #198754;
  position: relative;
  flex-shrink: 0;
}
.dot {
  width: 10px; height: 10px; border-radius: 50%;
  position: absolute; right: -1px; bottom: -1px;
  border: 2px solid #fff;
}
.dot.on { background: #22c55e; }
.dot.off { background: #9ca3af; }

.name { font-weight: 600; }
.preview { color: #6c757d; font-size: .93rem; line-height: 1.3; }
.time { font-size: .78rem; color: #6c757d; white-space: nowrap; }
.badge-unread {
  min-width: 20px; height: 20px; border-radius: 10px;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: .75rem; font-weight: 700;
}
.empty {
  padding: 46px 20px; text-align: center; color: #6c757d;
}
</style>

<div class="msg-shell">
  <div class="msg-card">
    <div class="msg-head d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Conversations</h5>
      <span class="badge bg-success-subtle text-success border"><?= (int)$totalUnread ?> unread</span>
    </div>

    <div class="msg-list">
      <?php if (empty($conversations)): ?>
        <div class="empty">
          <i class="bi bi-chat-square-text fs-1 d-block mb-2"></i>
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
                <div class="time"><?= date('M j, H:i', strtotime($c['created_at'])) ?></div>
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
</div>

<script>
const CURRENT_USER_ID = <?= (int)$uid ?>;
const WS_HOST = window.location.hostname;
const ws = new WebSocket(`ws://${WS_HOST}:8080?user_id=${CURRENT_USER_ID}`);

function setPresence(userId, isOnline) {
  const dot = document.getElementById(`user-dot-${userId}`);
  const status = document.getElementById(`user-status-${userId}`);
  if (dot) {
    dot.classList.remove('on', 'off');
    dot.classList.add(isOnline ? 'on' : 'off');
  }
  if (status) status.textContent = isOnline ? 'Online' : 'Offline';
}

ws.onopen = () => {
  <?php if (!empty($wsNotify)): ?>
  ws.send(JSON.stringify({
    type: "chat",
    to: <?= (int)$wsNotify['to'] ?>,
    message_id: <?= (int)$wsNotify['message_id'] ?>,
    subject: <?= json_encode($wsNotify['subject']) ?>,
    body: <?= json_encode($wsNotify['body']) ?>
  }));
  <?php endif; ?>
};

ws.onmessage = (event) => {
  const data = JSON.parse(event.data);
  if (data.type === 'new_message' && Number(data.to) === Number(CURRENT_USER_ID)) {
    location.reload();
  }
  if (data.type === 'presence') {
    setPresence(Number(data.user_id), Number(data.is_online) === 1);
  }
};
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>