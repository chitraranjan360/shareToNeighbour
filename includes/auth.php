<?php
/**
 * Authentication helpers
 * TWO separate systems: Local User + Admin
 */

// ════════════════════════════════════════
//  LOCAL USER AUTH
// ════════════════════════════════════════

function isUserLoggedIn(): bool {
    return isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'user';
}

function requireUserLogin(): void {
    if (!isUserLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        setFlash('error', 'You must log in first to do that.');
        redirect(SITE_URL . '/login.php');
    }
}

function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function currentUserName(): ?string {
    return $_SESSION['username'] ?? null;
}

function unreadMessageCount(mysqli $conn): int {
    if (!isUserLoggedIn()) return 0;
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM messages WHERE receiver_id = ? AND is_read = 0");
    $uid  = currentUserId();
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($r['c'] ?? 0);
}

function pendingRequestCount(mysqli $conn): int {
    if (!isUserLoggedIn()) return 0;
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM requests WHERE owner_id = ? AND status = 'pending'");
    $uid  = currentUserId();
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($r['c'] ?? 0);
}

// ════════════════════════════════════════
//  ADMIN AUTH (completely separate)
// ════════════════════════════════════════

function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && $_SESSION['user_type'] === 'admin';
}

function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        setFlash('error', 'Admin login required.');
        redirect(ADMIN_URL . '/login.php');
    }
}

function currentAdminName(): ?string {
    return $_SESSION['admin_username'] ?? null;
}