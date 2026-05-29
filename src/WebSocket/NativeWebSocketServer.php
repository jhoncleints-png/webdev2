<?php

namespace App\WebSocket;

class NativeWebSocketServer
{
    private $serverSocket;
    private $clients = [];
    private $port = 8080;
    private $host = '0.0.0.0';

    public function __construct()
    {
        $this->port = getenv('WEBSOCKET_PORT') ?: 8080;
        $this->host = getenv('WEBSOCKET_HOST') ?: '0.0.0.0';
    }

    public function start()
    {
        // Create TCP/IP stream socket
        $this->serverSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->serverSocket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->serverSocket, $this->host, $this->port);
        socket_listen($this->serverSocket);
        socket_set_nonblock($this->serverSocket);

        error_log('[WEBSOCKET] Server started on ' . $this->host . ':' . $this->port);

        $this->clients = [$this->serverSocket];

        while (true) {
            $read = $this->clients;
            $write = null;
            $except = null;

            $changed = socket_select($read, $write, $except, 0, 100000);

            if ($changed > 0) {
                foreach ($read as $socket) {
                    if ($socket === $this->serverSocket) {
                        // New connection
                        $newSocket = socket_accept($this->serverSocket);
                        $this->clients[] = $newSocket;
                        error_log('[WEBSOCKET] New client connected. Total: ' . (count($this->clients) - 1));
                    } else {
                        // Read from client
                        $data = socket_read($socket, 2048, PHP_NORMAL_READ);
                        if ($data === false || strlen($data) === 0) {
                            // Client disconnected
                            $this->disconnectClient($socket);
                        } else {
                            // Handle WebSocket handshake or message
                            $this->handleData($socket, $data);
                        }
                    }
                }
            }

            // Check for new messages to broadcast
            $this->checkBroadcastQueue();
        }
    }

    private function disconnectClient($socket)
    {
        $key = array_search($socket, $this->clients, true);
        if ($key !== false) {
            unset($this->clients[$key]);
            socket_close($socket);
            error_log('[WEBSOCKET] Client disconnected. Total: ' . (count($this->clients) - 1));
        }
    }

    private function handleData($socket, $data)
    {
        // Simple WebSocket handshake
        if (strpos($data, 'GET') === 0) {
            $this->performHandshake($socket, $data);
        }
    }

    private function performHandshake($socket, $data)
    {
        $lines = explode("\r\n", $data);
        $key = '';
        foreach ($lines as $line) {
            if (strpos($line, 'Sec-WebSocket-Key:') === 0) {
                $key = trim(substr($line, 18));
                break;
            }
        }

        $acceptKey = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
                   "Upgrade: websocket\r\n" .
                   "Connection: Upgrade\r\n" .
                   "Sec-WebSocket-Accept: $acceptKey\r\n" .
                   "\r\n";

        socket_write($socket, $upgrade, strlen($upgrade));
        error_log('[WEBSOCKET] Handshake completed');
    }

    private function checkBroadcastQueue()
    {
        // Check for messages in shared memory or file
        $queueFile = sys_get_temp_dir() . '/websocket_queue.txt';
        if (file_exists($queueFile)) {
            $messages = file($queueFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!empty($messages)) {
                foreach ($messages as $message) {
                    $this->broadcast($message);
                }
                file_put_contents($queueFile, '');
            }
        }
    }

    public function broadcast($message)
    {
        $frame = $this->createFrame($message);
        foreach ($this->clients as $client) {
            if ($client !== $this->serverSocket) {
                @socket_write($client, $frame, strlen($frame));
            }
        }
        error_log('[WEBSOCKET] Broadcasted to ' . (count($this->clients) - 1) . ' clients');
    }

    private function createFrame($data)
    {
        $payload = json_encode($data);
        $len = strlen($payload);
        $frame = chr(0x81); // FIN + text frame

        if ($len <= 125) {
            $frame .= chr($len);
        } elseif ($len <= 65535) {
            $frame .= chr(126) . pack('n', $len);
        } else {
            $frame .= chr(127) . pack('J', $len);
        }

        $frame .= $payload;
        return $frame;
    }
}
