<?php
// Private chat page for one-to-one messaging.
$pageTitle = 'Chat — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

requireUserLogin();
$uid = currentUserId();

// Current signed-in user id.
$otherUserId = (int)($_GET['user'] ?? 0);
if ($otherUserId <= 0 || $otherUserId === $uid) {
    setFlash('error', 'Invalid chat user.');
    redirect(SITE_URL . '/messages.php');
}

$stmt = $conn->prepare("SELECT id, username, full_name, is_online, last_seen FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $otherUserId);
$stmt->execute();
// Load the other user's public profile data.
$otherUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$otherUser) {
    setFlash('error', 'User not found.');
    redirect(SITE_URL . '/messages.php');
}

$errors = [];

// Handle a new message submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = trim($_POST['body'] ?? '');
    $subject = 'Chat Message';

    // Prevent empty messages and very long messages.
    if ($body === '') {
        $errors[] = 'Message cannot be empty.';
    } elseif (strlen($body) > 2000) {
        $errors[] = 'Message is too long (max 2000 characters).';
    }

    if (empty($errors)) {
        // Save the chat message in the messages table.
        $stmt = $conn->prepare("
            INSERT INTO messages (sender_id, receiver_id, item_id, subject, body, is_read)
            VALUES (?, ?, NULL, ?, ?, 0)
        ");
        $stmt->bind_param('iiss', $uid, $otherUserId, $subject, $body);

        if ($stmt->execute()) {
            $messageId = (int)$stmt->insert_id;
            $stmt->close();

            // Only notify if the receiver is not actively viewing this chat.
            $shouldNotify = true;
            $presenceStmt = $conn->prepare("
                SELECT 1
                FROM user_chat_presence
                WHERE user_id = ?
                  AND with_user_id = ?
                  AND updated_at >= (NOW() - INTERVAL 30 SECOND)
                LIMIT 1
            ");
            if ($presenceStmt) {
                $presenceStmt->bind_param('ii', $otherUserId, $uid);
                $presenceStmt->execute();
                $activeRow = $presenceStmt->get_result()->fetch_assoc();
                $presenceStmt->close();
                if ($activeRow) {
                    $shouldNotify = false;
                }
            }

            if ($shouldNotify) {
                // Create an in-app notification for the receiver.
                $nType  = 'message';
                $nRefId = $messageId;
                $nTitle = 'New message';
                $nBody  = mb_strimwidth($body, 0, 120, '...');

                $nStmt = $conn->prepare("
                    INSERT INTO notifications (user_id, type, ref_id, title, body, is_seen)
                    VALUES (?, ?, ?, ?, ?, 0)
                ");
                if ($nStmt) {
                    $nStmt->bind_param('isiss', $otherUserId, $nType, $nRefId, $nTitle, $nBody);
                    $nStmt->execute();
                    $nStmt->close();
                }
            }

            // Store data for the WebSocket notification flow.
            $_SESSION['ws_notify'] = [
                'to' => $otherUserId,
                'from' => $uid,
                'message_id' => $messageId,
                'subject' => $subject,
                'body' => $body
            ];

            // Send an email alert when the receiver is not actively in this chat.
            $senderInfo = getUserInfo($conn, $uid);
            $receiverInfo = getUserInfo($conn, $otherUserId);
            if ($senderInfo && $receiverInfo && $shouldNotify) {
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

// Mark messages from the other user as read.
$stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
$stmt->bind_param('ii', $otherUserId, $uid);
$stmt->execute();
$stmt->close();

// Load the full conversation history in order.
$stmt = $conn->prepare("
    SELECT id, sender_id, receiver_id, body, is_read, created_at
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

<div class="chat-shell container py-4">
    <div class="chat-card card mx-auto shadow-sm" style="max-width:900px;">
        <div class="chat-header card-header d-flex align-items-center justify-content-between py-3">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center fw-bold me-2" style="width:48px;height:48px;">
                    <?= h($initial) ?>
                    <span id="presenceDot" class="presence-dot <?= ((int)$otherUser['is_online'] === 1 ? 'presence-on' : 'presence-off') ?>"></span>
                </div>
                <div>
                    <div class="fw-semibold mb-0"><?= h($name) ?></div>
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

        <div class="chat-body overflow-auto p-3 d-flex flex-column" id="chatBody" style="max-height:60vh;">
            <?php foreach ($messages as $m): ?>
                <?php $mine = ((int)$m['sender_id'] === $uid); ?>
                <div class="msg-row <?= $mine ? 'mine d-flex justify-content-end' : 'theirs d-flex justify-content-start' ?> mb-2" data-message-id="<?= (int)$m['id'] ?>">
                    <div class="d-inline-block">
                        <div class="msg-bubble p-2 rounded-3 shadow-sm <?= $mine ? 'bg-primary text-white' : 'bg-light text-dark' ?>"><?= h($m['body']) ?></div>
                        <div class="msg-time small text-muted mt-1 text-end">
                            <?= date('M j, H:i', strtotime($m['created_at'])) ?>
                            <?php if ($mine): ?>
                                <span class="tick ms-2" id="tick-<?= (int)$m['id'] ?>">
                                    <?= ((int)$m['is_read'] === 1) ? '✓✓' : '✓' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="chat-footer card-footer bg-white border-top-0">
            <form method="POST" id="chatForm" class="d-flex gap-2" autocomplete="off" novalidate>
                <textarea name="body" id="bodyInput" class="form-control chat-input" rows="2" required placeholder="Type your message..."></textarea>
                <div class="invalid-feedback">
                    Message cannot be empty.
                </div>
                <button type="submit" class="btn btn-success send-btn">
                    <i class="bi bi-send"></i> Send
                </button>
            </form>
        </div>
    </div>
</div>

<script src="<?= SITE_URL ?>/js/app.js"></script>
<script>
    //This section handle real time chat section
    const CURRENT_USER_ID = <?= (int)$uid ?>;
    const OTHER_USER_ID = <?= (int)$otherUserId ?>;
    //const WS_HOST = "192.168.1.111"; // adjust to your WebSocket server host
    const WS_HOST = window.location.hostname; //use current host server
    let ws = null;

    const pendingNotify = <?= json_encode(!empty($wsNotify) ? [
        'type' => 'chat',
        'to' => (int)$wsNotify['to'],
        'from' => (int)$uid,
        'message_id' => (int)$wsNotify['message_id'],
        'subject' => $wsNotify['subject'],
        'body' => $wsNotify['body']
    ] : null) ?>;

    const chatBody = document.getElementById('chatBody');
    const presenceDot = document.getElementById('presenceDot');
    const presenceText = document.getElementById('presenceText');
    const bodyInput = document.getElementById('bodyInput');
    const chatForm = document.getElementById('chatForm');

    let sending = false;
    let pendingSent = false;

    function scrollBottom() {
        chatBody.scrollTop = chatBody.scrollHeight;
    }
    scrollBottom();

    function appendMessage(body, mine, timeText, messageId = null, isRead = 0) {
        // prevent duplicate render by message_id
        if (messageId) {
            const exists = chatBody.querySelector(`.msg-row[data-message-id="${messageId}"]`);
            if (exists) return;
        }

        const row = document.createElement('div');
        row.className = 'msg-row ' + (mine ? 'mine' : 'theirs');
        if (messageId) row.setAttribute('data-message-id', messageId);

        const wrap = document.createElement('div');

        const bubble = document.createElement('div');
        bubble.className = 'msg-bubble';
        bubble.textContent = body;

        const t = document.createElement('div');
        t.className = 'msg-time';
        t.textContent = timeText;

        if (mine && messageId) {
            const tick = document.createElement('span');
            tick.className = 'tick';
            tick.id = 'tick-' + messageId;
            tick.textContent = Number(isRead) === 1 ? '✓✓' : '✓';
            tick.style.marginLeft = '6px';
            t.appendChild(tick);
        }

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

    function sendPendingNotify() {
        if (!pendingNotify || pendingSent) return;
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify(pendingNotify));
            pendingSent = true; // one-time send only
        }
    }

    function pingPresence() {
        fetch(`<?= SITE_URL ?>/chat_presence_ping.php`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `with_user_id=${encodeURIComponent(OTHER_USER_ID)}`
        }).catch(() => {});
    }

    setInterval(pingPresence, 10000);

    function handleRealtimeEvent(data) {
        if (data.type === 'new_message') {
            if (Number(data.from) === OTHER_USER_ID && Number(data.to) === CURRENT_USER_ID) {
                appendMessage(
                    data.body || '',
                    false,
                    data.created_at || new Date().toLocaleString(),
                    data.message_id || null,
                    0
                );

                fetch(`<?= SITE_URL ?>/mark_thread_read.php?user=${OTHER_USER_ID}`, {
                    method: 'GET',
                    credentials: 'same-origin'
                }).catch(() => {});

                if (ws && ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({
                        type: 'read_receipt',
                        to: OTHER_USER_ID,
                        from: CURRENT_USER_ID
                    }));
                }
            }
        }

        if (data.type === 'read_receipt') {
            if (Number(data.from) === OTHER_USER_ID && Number(data.to) === CURRENT_USER_ID) {
                document.querySelectorAll('.tick').forEach(t => t.textContent = '✓✓');
            }
        }

        if (data.type === 'presence' && Number(data.user_id) === OTHER_USER_ID) {
            setPresence(Number(data.is_online) === 1);
        }
    }

    function connectThreadSocket() {
        ws = new WebSocket(`ws://${WS_HOST}:8080?user_id=${CURRENT_USER_ID}`);

        ws.onopen = () => {
            sendPendingNotify();
            pingPresence();
        };

        ws.onmessage = (event) => {
            let data;
            try {
                data = JSON.parse(event.data);
            } catch (_e) {
                return;
            }
            handleRealtimeEvent(data);
        };

        ws.onerror = () => {
            try { ws.close(); } catch (_e) {}
        };

        ws.onclose = () => {
            setTimeout(connectThreadSocket, 1500);
        };
    }

    connectThreadSocket();

    window.addEventListener('app:new-message', function (e) {
        if (!e || !e.detail) return;
        handleRealtimeEvent(e.detail);
    });

    // prevent double submit on mobile
    chatForm.addEventListener('submit', function(e) {
        if (sending) {
            e.preventDefault();
            return;
        }
        sending = true;

        const btn = chatForm.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';
        }
    });

    bodyInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey && !sending) {
            e.preventDefault();
            chatForm.requestSubmit();
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>