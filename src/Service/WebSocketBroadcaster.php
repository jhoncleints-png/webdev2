<?php

namespace App\Service;

class WebSocketBroadcaster
{
    private static string $websocketUrl;
    private static bool $enabled = true;

    public static function init(): void
    {
        self::$websocketUrl = getenv('WEBSOCKET_SERVICE_URL') ?: 'http://localhost:8080';
        self::$enabled = getenv('WEBSOCKET_ENABLED') !== 'false';
    }

    public static function broadcast(string $type, array $data): void
    {
        if (!isset(self::$websocketUrl)) {
            self::init();
        }

        if (!self::$enabled) {
            error_log('[WEBSOCKET BROADCASTER] Broadcasting disabled');
            return;
        }

        $message = json_encode([
            'type' => $type,
            'data' => $data,
            'timestamp' => time()
        ]);

        try {
            $ch = curl_init(self::$websocketUrl . '/broadcast');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode !== 200) {
                error_log('[WEBSOCKET BROADCASTER] Failed to broadcast: HTTP ' . $httpCode);
            } else {
                error_log('[WEBSOCKET BROADCASTER] Broadcasted: ' . $type);
            }
            
            curl_close($ch);
        } catch (\Exception $e) {
            error_log('[WEBSOCKET BROADCASTER] Error: ' . $e->getMessage());
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
