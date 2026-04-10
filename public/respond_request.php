<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php'; // ✅ Step 7 email alerts

requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/messages.php?tab=requests');
}

$requestId = (int)($_POST['request_id'] ?? 0);
$action    = $_POST['action'] ?? '';

if ($requestId <= 0 || !in_array($action, ['accept', 'decline'], true)) {
    setFlash('error', 'Invalid request action.');
    redirect(SITE_URL . '/messages.php?tab=requests');
}

$ownerId = currentUserId();

// Load request and confirm this logged-in user is the owner
$stmt = $conn->prepare("
    SELECT r.*, fi.title AS item_title, fi.status AS item_status
    FROM requests r
    JOIN furniture_items fi ON r.item_id = fi.id
    WHERE r.id = ? AND r.owner_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $requestId, $ownerId);
$stmt->execute();
$req = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$req) {
    setFlash('error', 'Request not found or you do not have permission.');
    redirect(SITE_URL . '/messages.php?tab=requests');
}

if ($req['status'] !== 'pending') {
    setFlash('error', 'This request has already been processed.');
    redirect(SITE_URL . '/messages.php?tab=requests');
}

$conn->begin_transaction();

try {
    if ($action === 'accept') {

        // Ensure no other accepted request exists for this item
        $stmt = $conn->prepare("SELECT id FROM requests WHERE item_id = ? AND status = 'accepted' LIMIT 1");
        $stmt->bind_param('i', $req['item_id']);
        $stmt->execute();
        $alreadyAccepted = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($alreadyAccepted) {
            throw new Exception('Another request for this item is already accepted.');
        }

        // Ensure item is currently available (so you don't accept twice)
        $stmt = $conn->prepare("SELECT status FROM furniture_items WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $req['item_id']);
        $stmt->execute();
        $itemRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$itemRow || $itemRow['status'] !== 'available') {
            throw new Exception('Item is no longer available.');
        }

        // 1) Accept this request
        $stmt = $conn->prepare("UPDATE requests SET status = 'accepted' WHERE id = ?");
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $stmt->close();

        // 2) Mark item as REQUESTED (reserved)
        $stmt = $conn->prepare("UPDATE furniture_items SET status = 'requested' WHERE id = ?");
        $stmt->bind_param('i', $req['item_id']);
        $stmt->execute();
        $stmt->close();

        // 3) Auto-decline other pending requests for the same item
        $stmt = $conn->prepare("
            UPDATE requests
            SET status = 'declined'
            WHERE item_id = ? AND status = 'pending' AND id <> ?
        ");
        $stmt->bind_param('ii', $req['item_id'], $requestId);
        $stmt->execute();
        $stmt->close();

        // 4) Notify requester (in-app message)
        $subject = 'Request accepted: ' . $req['item_title'];
        $body    = "Good news!\n\nYour request for \"" . $req['item_title'] . "\" was ACCEPTED.\n\n"
            . "Status is now: REQUESTED (reserved).\n"
            . "Please message the owner to arrange pickup time.\n\n"
            . "After pickup, the owner will mark the item as TAKEN.";

        $stmt = $conn->prepare("
            INSERT INTO messages (sender_id, receiver_id, item_id, subject, body)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iiiss', $ownerId, $req['requester_id'], $req['item_id'], $subject, $body);
        $stmt->execute();
        $stmt->close();

        // 4.1) Bell notification for requester
        $nType  = 'request_accepted';
        $nRefId = $requestId;
        $nTitle = 'Your request was accepted';
        $nBody  = 'Item: ' . $req['item_title'];

        $nStmt = $conn->prepare("
            INSERT INTO notifications (user_id, type, ref_id, title, body, is_seen)
            VALUES (?, ?, ?, ?, ?, 0)
            ON DUPLICATE KEY UPDATE id = id
        ");
        $requesterId = (int)$req['requester_id'];
        $nStmt->bind_param('isiss', $requesterId, $nType, $nRefId, $nTitle, $nBody);
        $nStmt->execute();
        $nStmt->close();

        // ✅ Step 7: Email requester about acceptance
        $ownerInfo = getUserInfo($conn, $ownerId);
        if ($ownerInfo) {
            $emailSubject = "Request accepted: " . $req['item_title'];
            $emailBody = "Hello,\n\n"
                . "Your request for \"" . $req['item_title'] . "\" was ACCEPTED by @" . $ownerInfo['username'] . ".\n\n"
                . "Login to continue chatting:\n"
                . SITE_URL . "/messages.php?tab=inbox\n\n"
                . "- ShareToNeighbour";

            sendEmailToUser($conn, (int)$req['requester_id'], $emailSubject, $emailBody);
        }

        $conn->commit();
        setFlash('success', 'Request accepted. Item marked as RESERVED (requested) and requester notified.');
    }

    if ($action === 'decline') {
        // Decline only changes request status, item stays available
        $stmt = $conn->prepare("UPDATE requests SET status = 'declined' WHERE id = ?");
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $stmt->close();

        $subject = 'Request declined: ' . $req['item_title'];
        $body    = "Hello,\n\nYour request for \"" . $req['item_title'] . "\" was DECLINED.\n\n"
            . "You can browse other items nearby.";

        // In-app message
        $stmt = $conn->prepare("
            INSERT INTO messages (sender_id, receiver_id, item_id, subject, body)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iiiss', $ownerId, $req['requester_id'], $req['item_id'], $subject, $body);
        $stmt->execute();
        $stmt->close();

        // 4.1) Bell notification for requester
        $nType  = 'request_declined';
        $nRefId = $requestId;
        $nTitle = 'Your request was declined';
        $nBody  = 'Item: ' . $req['item_title'];

        $nStmt = $conn->prepare("
            INSERT INTO notifications (user_id, type, ref_id, title, body, is_seen)
            VALUES (?, ?, ?, ?, ?, 0)
            ON DUPLICATE KEY UPDATE id = id
        ");
        $requesterId = (int)$req['requester_id'];
        $nStmt->bind_param('isiss', $requesterId, $nType, $nRefId, $nTitle, $nBody);
        $nStmt->execute();
        $nStmt->close();

        // ✅ Step 7: Email requester about decline
        $ownerInfo = getUserInfo($conn, $ownerId);
        if ($ownerInfo) {
            $emailSubject = "Request declined: " . $req['item_title'];
            $emailBody = "Hello,\n\n"
                . "Your request for \"" . $req['item_title'] . "\" was DECLINED by @" . $ownerInfo['username'] . ".\n\n"
                . "Browse other items:\n"
                . SITE_URL . "/browse.php\n\n"
                . "- ShareToNeighbour";

            sendEmailToUser($conn, (int)$req['requester_id'], $emailSubject, $emailBody);
        }

        $conn->commit();
        setFlash('success', 'Request declined and requester notified.');
    }

} catch (Throwable $e) {
    $conn->rollback();
    setFlash('error', 'Action failed: ' . $e->getMessage());
}

redirect(SITE_URL . '/messages.php?tab=requests');