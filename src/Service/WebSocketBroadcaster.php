<?php

namespace App\Service;

class WebSocketBroadcaster
{
    private static ?\App\WebSocket\OrderWebSocketServer $server = null;

    public static function setServer(\App\WebSocket\OrderWebSocketServer $server): void
    {
        self::$server = $server;
    }

    public static function broadcast(string $type, array $data): void
    {
        if (self::$server) {
            self::$server->broadcast($type, $data);
        } else {
            error_log('[WEBSOCKET BROADCASTER] Server not set, cannot broadcast');
        }
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
