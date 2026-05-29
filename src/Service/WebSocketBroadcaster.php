<?php

namespace App\Service;

class WebSocketBroadcaster
{
    private static string $queueFile;

    public static function init(): void
    {
        self::$queueFile = sys_get_temp_dir() . '/websocket_queue.txt';
    }

    public static function broadcast(string $type, array $data): void
    {
        if (!isset(self::$queueFile)) {
            self::init();
        }

        $message = json_encode([
            'type' => $type,
            'data' => $data,
            'timestamp' => time()
        ]);

        // Write to queue file for WebSocket server to read
        file_put_contents(self::$queueFile, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
        error_log('[WEBSOCKET BROADCASTER] Queued message: ' . $type);
    }

    public static function broadcastNewOrder(array $orderData): void
    {
        self::broadcast('new_order', $orderData);
    }

    public static function broadcastOrderUpdate(array $orderData): void
    {
        self::broadcast('order_update', $orderData);
    }
}
