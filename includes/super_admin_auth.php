<?php
// Authentication helpers for the separate super-admin session scope.
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check whether a super-admin session is currently active.
function superAdminIsLoggedIn(): bool {
    return !empty($_SESSION['super_admin_id']);
}

// Return the current super-admin ID when logged in.
function currentSuperAdminId(): ?int {
    return superAdminIsLoggedIn() ? (int)$_SESSION['super_admin_id'] : null;
}

// Redirect unauthenticated visitors to the super-admin login page.
function requireSuperAdminLogin(): void {
    if (!superAdminIsLoggedIn()) {
        header('Location: ' . Super_admin_URL . '/login.php');
        exit;
    }
}

// Clear the super-admin session values on logout.
function superAdminLogout(): void {
    unset($_SESSION['super_admin_id'], $_SESSION['super_admin_username']);
}

// Store a super-admin audit trail entry for administrative actions.
function superAdminAudit(string $action, ?int $targetAdminId = null, array $meta = []): void {
    global $conn;
    $sid = currentSuperAdminId();
    if (!$sid) return;

    // Capture request metadata for traceability.
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $metaJson = !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;

    $stmt = $conn->prepare("
        INSERT INTO super_admin_audit_logs
        (super_admin_id, action, target_admin_id, ip_address, user_agent, meta_json)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('isisss', $sid, $action, $targetAdminId, $ip, $ua, $metaJson);
    $stmt->execute();
    $stmt->close();
}