<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!defined('CHATBOT_ENABLED') || !CHATBOT_ENABLED) {
    http_response_code(503);
    echo json_encode(['error' => 'Chatbot disabled']);
    exit;
}

requireUserLogin();

$body = json_decode(file_get_contents('php://input'), true);
$msg = trim((string)($body['message'] ?? ''));

if ($msg === '' || mb_strlen($msg) > 800) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid message']);
    exit;
}

$rulesPath = __DIR__ . '/../includes/bot_rules.txt';
$siteRules = is_file($rulesPath) ? trim(file_get_contents($rulesPath)) : '';

$system =
"You are ShareToNeighbour Support Bot. Answer ONLY using the site rules below. If not found, say: 'I don't have that info, please contact admin.' " .
"Format replies as short bullet points or numbered steps with line breaks. Avoid long paragraphs.\n\n" .
"SITE RULES:\n" . $siteRules;

$user = currentUserName();

$payload = [
    'model' => OLLAMA_MODEL,
    'stream' => false,
    'options' => ['num_predict' => 200],
    'messages' => [
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => "User: {$user}\nQuestion: {$msg}"]
    ],
];

$ch = curl_init(rtrim(OLLAMA_URL, '/') . '/api/chat');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 60,
    
]);

$res = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($res === false || $code < 200 || $code >= 300) {
    http_response_code(502);
    error_log("BOT_FAIL HTTP=$code ERR=" . ($err ?: 'none') . " RES=" . substr((string)$res, 0, 300));
    echo json_encode(['error' => 'Bot backend error', 'detail' => $err ?: $res]);
    exit;
}

$data = json_decode($res, true);
$reply = trim((string)($data['message']['content'] ?? ''));

echo json_encode(['reply' => $reply !== '' ? $reply : "Sorry, I couldn't answer that."]);