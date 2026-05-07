<?php
require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Server\IoServer;

class ChatSocket implements MessageComponentInterface {
    protected \SplObjectStorage $clients;
    protected array $userConnections = []; // userId => [resourceId => ConnectionInterface]
    protected PDO $pdo;

    public function __construct() {
        $this->clients = new \SplObjectStorage();

        $this->pdo = new PDO(
            "mysql:host=127.0.0.1;dbname=sharetoneighbour;charset=utf8mb4",
            "root",
            ""
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function onOpen(ConnectionInterface $conn): void {
        $this->clients->attach($conn);

        parse_str($conn->httpRequest->getUri()->getQuery(), $query);
        $userId = isset($query['user_id']) ? (int)$query['user_id'] : 0;
        $conn->user_id = $userId;

        if ($userId > 0) {
            if (!isset($this->userConnections[$userId])) {
                $this->userConnections[$userId] = [];
            }
            $this->userConnections[$userId][$conn->resourceId] = $conn;

            $this->setUserOnline($userId, 1);
            $this->broadcastPresence($userId, 1);
        }
    }

    public function onMessage(ConnectionInterface $from, $msg): void {
        $data = json_decode($msg, true);
        if (!is_array($data)) return;

        $type = $data['type'] ?? '';

        if ($type === 'chat') {
            $to = (int)($data['to'] ?? 0);

            if ($to > 0 && isset($this->userConnections[$to])) {
                $payload = json_encode([
                    'type' => 'new_message',
                    'message_id' => (int)($data['message_id'] ?? 0),
                    'from' => (int)($from->user_id ?? 0),
                    'to' => $to,
                    'subject' => $data['subject'] ?? '',
                    'body' => $data['body'] ?? '',
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                foreach ($this->userConnections[$to] as $clientConn) {
                    $clientConn->send($payload);
                }
            }
            return;
        }

        if ($type === 'read_receipt') {
            $to = (int)($data['to'] ?? 0);

            if ($to > 0 && isset($this->userConnections[$to])) {
                $payload = json_encode([
                    'type' => 'read_receipt',
                    'from' => (int)($from->user_id ?? 0),
                    'to' => $to,
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                foreach ($this->userConnections[$to] as $clientConn) {
                    $clientConn->send($payload);
                }
            }
            return;
        }
    }

    public function onClose(ConnectionInterface $conn): void {
        $this->clients->detach($conn);
        $userId = (int)($conn->user_id ?? 0);

        if ($userId > 0 && isset($this->userConnections[$userId][$conn->resourceId])) {
            unset($this->userConnections[$userId][$conn->resourceId]);

            if (empty($this->userConnections[$userId])) {
                unset($this->userConnections[$userId]);
                $this->setUserOnline($userId, 0);
                $this->broadcastPresence($userId, 0);
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void {
        error_log("WebSocket error: " . $e->getMessage());
        $conn->close();
    }

    private function setUserOnline(int $userId, int $isOnline): void {
        $stmt = $this->pdo->prepare("UPDATE users SET is_online = ?, last_seen = NOW() WHERE id = ?");
        $stmt->execute([$isOnline, $userId]);
    }

    private function broadcastPresence(int $userId, int $isOnline): void {
        $payload = json_encode([
            'type' => 'presence',
            'user_id' => $userId,
            'is_online' => $isOnline
        ]);

        foreach ($this->clients as $client) {
            $client->send($payload);
        }
    }
}

$server = IoServer::factory(
    new HttpServer(new WsServer(new ChatSocket())),
    8080, '0.0.0.0' 
);

echo "WebSocket server started at ws://0.0.0.0:8080\n";
$server->run();