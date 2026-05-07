<?php
/**
 * Authentication helpers
 * TWO separate systems: Local User + Admin
 *
 * This file contains small helper functions used across the project to:
 * - check whether a user or admin is logged in
 * - redirect unauthenticated users
 * - read the current session identity
 * - count unread messages and pending requests
 * - log out disabled users automatically
 */

// ════════════════════════════════════════
//  LOCAL USER AUTH
// ════════════════════════════════════════

// Returns true when the current session belongs to a logged-in local user.
function isUserLoggedIn(): bool {
    return isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'user';
}

// Redirects visitors to the login page when they are not authenticated as a local user.
function requireUserLogin(): void {
    if (!isUserLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        setFlash('error', 'You must log in first to do that.');
        redirect(SITE_URL . '/login.php');
    }
}

// Returns the currently logged-in local user ID, or null when no user session exists.
function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

// Returns the currently logged-in local username, or null when unavailable.
function currentUserName(): ?string {
    return $_SESSION['username'] ?? null;
}

// Counts unread messages for the current local user.
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

// Counts pending item requests owned by the current local user.
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

// Logs out a local user automatically when the account is disabled or inactive.
// This is intended to run on page load so blocked accounts cannot keep using the session.
function logoutDisabledUser(mysqli $conn): void
{
    // Skip the check entirely if no local user is logged in.
    if (!isUserLoggedIn()) return;

    // Read the current session user ID and guard against invalid values.
    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid <= 0) return;

    // Check whether the account is still active and capture the disabled reason.
    $stmt = $conn->prepare("SELECT is_active, disabled_reason FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$u || (int)$u['is_active'] !== 1) {
        // Clear the local-user session so the account cannot continue browsing.
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['user_type']);

        // Build the flash message with the reason when one exists.
        $msg = 'Your account has been disabled.';
        if (!empty($u['disabled_reason'])) $msg .= ' Reason: ' . $u['disabled_reason'];

        // Show the message on the login page and redirect there immediately.
        setFlash('error', $msg);
        redirect(SITE_URL . '/login.php');
    }
}
// ════════════════════════════════════════
//  ADMIN AUTH (completely separate)
// ════════════════════════════════════════

// Returns true when the current session belongs to a logged-in admin.
function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && $_SESSION['user_type'] === 'admin';
}

// Redirects visitors to the admin login page when they are not authenticated.
function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        setFlash('error', 'Admin login required.');
        redirect(ADMIN_URL . '/login.php');
    }
}

// Returns the current admin username from session, or null when unavailable.
function currentAdminName(): ?string {
    return $_SESSION['admin_username'] ?? null;
}