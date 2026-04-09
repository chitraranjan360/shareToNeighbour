<?php
$pageTitle = 'Chat — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

requireUserLogin();
$uid = currentUserId();

$otherUserId = (int)($_GET['user'] ?? 0);
if ($otherUserId <= 0 || $otherUserId === $uid) {
    setFlash('error', 'Invalid chat user.');
    redirect(SITE_URL . '/messages.php');
}

$stmt = $conn->prepare("SELECT id, username, full_name, is_online, last_seen FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $otherUserId);
$stmt->execute();
$otherUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$otherUser) {
    setFlash('error', 'User not found.');
    redirect(SITE_URL . '/messages.php');
}

$errors = [];

// Send new message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = trim($_POST['body'] ?? '');
    $subject = 'Chat Message';

    if ($body === '') {
        $errors[] = 'Message cannot be empty.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO messages (sender_id, receiver_id, item_id, subject, body, is_read)
            VALUES (?, ?, NULL, ?, ?, 0)
        ");
        $stmt->bind_param('iiss', $uid, $otherUserId, $subject, $body);

        if ($stmt->execute()) {
            $messageId = (int)$stmt->insert_id;
            $stmt->close();

            $_SESSION['ws_notify'] = [
                'to' => $otherUserId,
                'from' => $uid,
                'message_id' => $messageId,
                'subject' => $subject,
                'body' => $body
            ];

            // Optional email notification
            $senderInfo = getUserInfo($conn, $uid);
            $receiverInfo = getUserInfo($conn, $otherUserId);
            if ($senderInfo && $receiverInfo) {
                $emailSubject = "New message from @" . $senderInfo['username'];
                $emailBody =
                    "Hello " . $receiverInfo['name'] . ",\n\n"
                    . "You received a new message on ShareToNeighbour.\n\n"
                    . "From: @" . $senderInfo['username'] . "\n"
                    . "Message: " . $body . "\n\n"
                    . "Open chat:\n" . SITE_URL . "/chat_thread.php?user=" . $uid . "\n\n"
                    . "- ShareToNeighbour";
                sendEmailToUser($conn, $otherUserId, $emailSubject, $emailBody);
            }

            redirect(SITE_URL . '/chat_thread.php?user=' . $otherUserId);
        } else {
            $errors[] = 'Failed to send message.';
            $stmt->close();
        }
    }
}

// mark incoming as read
$stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
$stmt->bind_param('ii', $otherUserId, $uid);
$stmt->execute();
$stmt->close();

// load thread
$stmt = $conn->prepare("
    SELECT id, sender_id, receiver_id, body, created_at
    FROM messages
    WHERE (sender_id = ? AND receiver_id = ?)
       OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC, id ASC
");
$stmt->bind_param('iiii', $uid, $otherUserId, $otherUserId, $uid);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$wsNotify = $_SESSION['ws_notify'] ?? null;
unset($_SESSION['ws_notify']);

$name = trim((string)($otherUser['full_name'] ?: $otherUser['username']));
$initial = strtoupper(substr($name, 0, 1));

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.chat-shell { max-width: 980px; margin: 0 auto; }
.chat-card {
    border: 1px solid #e9ecef;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,.04);
    background: #fff;
}
.chat-header {
    padding: 14px 16px;
    border-bottom: 1px solid #eef1f4;
    background: #fff;
}
.avatar {
    width: 38px; height: 38px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; color: #fff; background: #198754;
    position: relative;
}
.presence-dot {
    width: 10px; height: 10px; border-radius: 50%;
    position: absolute; right: -1px; bottom: -1px; border: 2px solid #fff;
}
.presence-on { background: #22c55e; }
.presence-off { background: #9ca3af; }

.chat-body {
    height: 62vh;
    overflow-y: auto;
    padding: 16px;
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
}
.msg-row { display: flex; margin-bottom: 10px; }
.msg-row.mine { justify-content: flex-end; }
.msg-row.theirs { justify-content: flex-start; }

.msg-bubble {
    max-width: 76%;
    border-radius: 14px;
    padding: 9px 12px;
    line-height: 1.35;
    word-break: break-word;
    white-space: pre-wrap;
    box-shadow: 0 1px 2px rgba(0,0,0,.06);
}
.msg-row.mine .msg-bubble {
    background: #198754;
    color: #fff;
    border-bottom-right-radius: 6px;
}
.msg-row.theirs .msg-bubble {
    background: #fff;
    color: #1f2937;
    border: 1px solid #eef1f4;
    border-bottom-left-radius: 6px;
}
.msg-time {
    margin-top: 4px;
    font-size: .74rem;
    color: #6b7280;
}
.msg-row.mine .msg-time { text-align: right; }

.chat-footer {
    padding: 12px;
    border-top: 1px solid #eef1f4;
    background: #fff;
}
.chat-input {
    border-radius: 12px;
    resize: none;
}
.send-btn { border-radius: 12px; min-width: 100px; }
</style>

<div class="chat-shell">
    <div class="chat-card">
        <div class="chat-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar">
                    <?= h($initial) ?>
                    <span id="presenceDot" class="presence-dot <?= ((int)$otherUser['is_online'] === 1 ? 'presence-on' : 'presence-off') ?>"></span>
                </div>
                <div>
                    <div class="fw-semibold"><?= h($name) ?></div>
                    <small id="presenceText" class="text-muted">
                        <?= ((int)$otherUser['is_online'] === 1) ? 'Online' : ('Last seen: ' . h($otherUser['last_seen'] ?? 'unknown')) ?>
                    </small>
                </div>
            </div>
            <a href="<?= SITE_URL ?>/messages.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger m-3 mb-0">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="chat-body" id="chatBody">
            <?php foreach ($messages as $m): ?>
                <?php $mine = ((int)$m['sender_id'] === $uid); ?>
                <div class="msg-row <?= $mine ? 'mine' : 'theirs' ?>" data-message-id="<?= (int)$m['id'] ?>">
                    <div>
                        <div class="msg-bubble"><?= h($m['body']) ?></div>
                        <div class="msg-time"><?= date('M j, H:i', strtotime($m['created_at'])) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="chat-footer">
            <form method="POST" id="chatForm" class="d-flex gap-2">
                <textarea name="body" id="bodyInput" class="form-control chat-input" rows="2" required placeholder="Type your message..."></textarea>
                <button type="submit" class="btn btn-success send-btn">
                    <i class="bi bi-send"></i> Send
                </button>
            </form>
        </div>
    </div>
</div>

<script>
const CURRENT_USER_ID = <?= (int)$uid ?>;
const OTHER_USER_ID = <?= (int)$otherUserId ?>;
const WS_HOST = window.location.hostname;
const ws = new WebSocket(`ws://${WS_HOST}:8080?user_id=${CURRENT_USER_ID}`);

const chatBody = document.getElementById('chatBody');
const presenceDot = document.getElementById('presenceDot');
const presenceText = document.getElementById('presenceText');
const bodyInput = document.getElementById('bodyInput');

function scrollBottom() {
    chatBody.scrollTop = chatBody.scrollHeight;
}
scrollBottom();

function appendMessage(body, mine, timeText) {
    const row = document.createElement('div');
    row.className = 'msg-row ' + (mine ? 'mine' : 'theirs');

    const wrap = document.createElement('div');
    const bubble = document.createElement('div');
    bubble.className = 'msg-bubble';
    bubble.textContent = body;

    const t = document.createElement('div');
    t.className = 'msg-time';
    t.textContent = timeText;

    wrap.appendChild(bubble);
    wrap.appendChild(t);
    row.appendChild(wrap);
    chatBody.appendChild(row);
    scrollBottom();
}

function setPresence(isOnline) {
    presenceDot.classList.remove('presence-on', 'presence-off');
    presenceDot.classList.add(isOnline ? 'presence-on' : 'presence-off');
    presenceText.textContent = isOnline ? 'Online' : 'Offline';
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

    if (data.type === 'new_message') {
        // incoming message for this thread
        if (Number(data.from) === OTHER_USER_ID && Number(data.to) === CURRENT_USER_ID) {
            appendMessage(data.body || '', false, data.created_at || new Date().toLocaleString());

            // mark read silently
            fetch(`<?= SITE_URL ?>/mark_thread_read.php?user=${OTHER_USER_ID}`, {
                method: 'GET',
                credentials: 'same-origin'
            }).catch(() => {});
        }
    }

    if (data.type === 'presence' && Number(data.user_id) === OTHER_USER_ID) {
        setPresence(Number(data.is_online) === 1);
    }
};

ws.onerror = (e) => console.error('WS error', e);

// QoL: Enter to send, Shift+Enter newline
bodyInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('chatForm').submit();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>