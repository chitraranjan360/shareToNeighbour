<?php
$pageTitle = 'Send Message — ShareToNeighbour';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

requireUserLogin();

$toUserId = (int)($_GET['to'] ?? $_POST['receiver_id'] ?? 0);
$itemId   = (int)($_GET['item'] ?? $_POST['item_id'] ?? 0);

$stmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE id = ?");
$stmt->bind_param('i', $toUserId);
$stmt->execute();
$recipient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$recipient) {
    setFlash('error','User not found.');
    redirect(SITE_URL.'/messages.php');
}

$itemTitle = '';
if ($itemId > 0) {
    $stmt = $conn->prepare("SELECT title FROM furniture_items WHERE id = ?");
    $stmt->bind_param('i', $itemId);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $itemTitle = $r['title'] ?? '';
}

$errors = [];
$subject = '';
$body = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $body    = trim($_POST['body'] ?? '');

    if ($subject === '') $errors[] = 'Subject required.';
    if ($body === '')    $errors[] = 'Message required.';

    if (empty($errors)) {
        $uid = currentUserId();
        $iParam = ($itemId > 0) ? $itemId : null;

        $stmt = $conn->prepare("INSERT INTO messages (sender_id,receiver_id,item_id,subject,body) VALUES (?,?,?,?,?)");
        $stmt->bind_param('iiiss', $uid, $toUserId, $iParam, $subject, $body);

        if ($stmt->execute()) {
            $messageId = (int)$stmt->insert_id;

            // queue realtime notify for next page load
            $_SESSION['ws_notify'] = [
                'to' => (int)$toUserId,
                'from' => (int)$uid,
                'message_id' => $messageId,
                'subject' => $subject,
                'body' => $body,
                'created_at' => date('Y-m-d H:i:s')
            ];

            // email alert
            $senderInfo = getUserInfo($conn, $uid);
            $receiverInfo = getUserInfo($conn, $toUserId);

            if ($senderInfo && $receiverInfo) {
                $emailSubject = "New message from @" . $senderInfo['username'];
                $emailBody =
                    "Hello " . $receiverInfo['name'] . ",\n\n"
                    . "You received a new message on ShareToNeighbour.\n\n"
                    . "From: @" . $senderInfo['username'] . "\n"
                    . "Subject: " . $subject . "\n\n"
                    . "Login to read and reply:\n"
                    . SITE_URL . "/messages.php?tab=inbox\n\n"
                    . "- ShareToNeighbour";

                sendEmailToUser($conn, $toUserId, $emailSubject, $emailBody);
            }

            setFlash('success', 'Message sent to ' . $recipient['username'] . '!');
            redirect(SITE_URL . '/messages.php?tab=sent');
        } else {
            $errors[] = 'Send failed.';
        }

        $stmt->close();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="mb-4">
                    <i class="bi bi-chat-dots text-success"></i>
                    Message <?= h($recipient['full_name']) ?>
                </h2>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="receiver_id" value="<?= $toUserId ?>">
                    <input type="hidden" name="item_id" value="<?= $itemId ?>">

                    <div class="mb-3">
                        <label class="form-label">To</label>
                        <input type="text" class="form-control" disabled
                               value="<?= h($recipient['full_name']) ?> (@<?= h($recipient['username']) ?>)">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" class="form-control" name="subject"
                               value="<?= h($subject ?: ($itemTitle ? 'Re: '.$itemTitle : 'Chat Message')) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="body" rows="5" required
                                  placeholder="Write your message…"><?= h($body) ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success flex-grow-1">
                            <i class="bi bi-send"></i> Send
                        </button>
                        <a href="<?= SITE_URL ?>/messages.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>