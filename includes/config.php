<?php
/**
 * ShareToNeighbour — Configuration
 * Auto-detects SITE_URL so links never break
 */

define('ENVIRONMENT', 'development');

if (ENVIRONMENT === 'development') {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'sharetoneighbour');
} else {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'id12345678_admin');
    define('DB_PASS', 'YourPassword');
    define('DB_NAME', 'id12345678_sharetoneighbour');
}

// ── Auto-detect base URL (FIXES broken links) ──
$protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host       = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Find the project root from any subfolder
if (preg_match('#(.*?/ShareToNeighbour)#i', $scriptPath, $m)) {
    $projectRoot = $m[1];
} else {
    // If project is placed at htdocs root without the folder name
    $projectRoot = '';
}

define('SITE_NAME',    'ShareToNeighbour');
define('BASE_URL',     $protocol . '://' . $host . $projectRoot);
define('SITE_URL',     BASE_URL . '/public');
define('ADMIN_URL',    BASE_URL . '/admin');
define('UPLOAD_DIR',   __DIR__ . '/../uploads/');
define('UPLOAD_URL',   BASE_URL . '/uploads');
define('MAX_IMG_WIDTH',  800);
define('MAX_IMG_HEIGHT', 600);

// ── Session ──
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Database ──
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('<div style="color:red;padding:20px;font-family:Arial;">
         Database connection failed: ' . htmlspecialchars($conn->connect_error) .
         '<br>Check includes/config.php</div>');
}
$conn->set_charset('utf8mb4');

// ── Helper functions ──
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function setFlash(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

function getFlash(string $key): ?string {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}
//for email notifications
define('EMAIL_ENABLED', true); // set false to disable emails quickly

// SMTP settings (RECOMMENDED: use SendGrid/Brevo/Mailgun; Gmail also works with App Password)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'sharetoneighbour360@gmail.com');  // change this
define('SMTP_PASS', 'yzoi fnfr ljbt rgrr');             // change this
define('SMTP_FROM_EMAIL', 'sharetoneighbour.alerts@gmail.com');
define('SMTP_FROM_NAME', 'ShareToNeighbour Alerts');

function getUserInfo(mysqli $conn, int $userId): ?array {
    $stmt = $conn->prepare("SELECT id, email, full_name, username, email_notifications FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return null;

    return [
        'id' => (int)$row['id'],
        'email' => $row['email'],
        'name' => $row['full_name'] ?: $row['username'],
        'username' => $row['username'],
        'email_notifications' => (int)($row['email_notifications'] ?? 1),
    ];
}