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

//Ollama Api
define('CHATBOT_ENABLED', true);
define('OLLAMA_URL', 'http://127.0.0.1:11434');
define('OLLAMA_MODEL', 'llama3.1:8b');

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

// Checks if given lat/lng is within Denmark using OpenStreetMap Nominatim API 
function isLatLngInDenmark(float $lat, float $lng): bool
{
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=" . urlencode((string)$lat) . "&lon=" . urlencode((string)$lng) . "&zoom=18&addressdetails=1";

    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: ShareToNeighbour/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $json = @file_get_contents($url, false, $context);
    if ($json === false) return false;

    $data = json_decode($json, true);
    $cc = strtolower($data['address']['country_code'] ?? '');

    return $cc === 'dk';
}

function renderStars($avg) {
    if ($avg === null) return '';
    $full = (int)floor($avg);
    $half = ($avg - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;

    $html = '';
    for ($i=0; $i<$full; $i++) $html .= '<i class="bi bi-star-fill text-warning"></i>';
    if ($half) $html .= '<i class="bi bi-star-half text-warning"></i>';
    for ($i=0; $i<$empty; $i++) $html .= '<i class="bi bi-star text-warning"></i>';
    return $html;
}

// Haversine formula to calculate distance between two lat/lng points
function haversineKm($lat1, $lon1, $lat2, $lon2) {
    $R = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}