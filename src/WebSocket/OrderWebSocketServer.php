<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class OrderWebSocketServer implements MessageComponentInterface
{
    protected \SplObjectStorage $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        error_log('[WEBSOCKET] New connection! (' . $this->clients->count() . ' clients connected)');
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        // Handle incoming messages from clients if needed
        error_log('[WEBSOCKET] Message from ' . $from->resourceId . ': ' . $msg);
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);
        error_log('[WEBSOCKET] Connection closed! (' . $this->clients->count() . ' clients remaining)');
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        error_log('[WEBSOCKET] Error: ' . $e->getMessage());
        $conn->close();
    }

    public function broadcast(string $type, array $data): void
    {
        $message = json_encode([
            'type' => $type,
            'data' => $data,
            'timestamp' => time()
        ]);

        foreach ($this->clients as $client) {
            $client->send($message);
        }

        error_log('[WEBSOCKET] Broadcasted: ' . $type . ' to ' . $this->clients->count() . ' clients');
    }
}
