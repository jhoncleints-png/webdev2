<?php

namespace App\Service;

class SocketioBroadcaster
{
    private static string $socketioServiceUrl;

    public function __construct()
    {
        self::$socketioServiceUrl = $_ENV['SOCKETIO_SERVICE_URL'] ?? 'https://brewery-socketio-production.up.railway.app';
    }

    /**
     * Broadcast new order event to Socket.io service
     */
    public static function broadcastNewOrder(array $data): void
    {
        $socketioServiceUrl = $_ENV['SOCKETIO_SERVICE_URL'] ?? 'https://brewery-socketio-production.up.railway.app';
        $url = $socketioServiceUrl . '/broadcast';
        
        $payload = [
            'event' => 'new_order',
            'data' => $data
        ];

        self::sendBroadcast($url, $payload);
    }

    /**
     * Broadcast order update event to Socket.io service
     */
    public static function broadcastOrderUpdate(array $data): void
    {
        $socketioServiceUrl = $_ENV['SOCKETIO_SERVICE_URL'] ?? 'https://brewery-socketio-production.up.railway.app';
        $url = $socketioServiceUrl . '/broadcast';
        
        $payload = [
            'event' => 'order_update',
            'data' => $data
        ];

        self::sendBroadcast($url, $payload);
    }

    /**
     * Send HTTP POST request to Socket.io service
     */
    private static function sendBroadcast(string $url, array $payload): void
    {
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            error_log('[SOCKETIO] cURL error: ' . $error);
        }

        if ($httpCode !== 200) {
            error_log('[SOCKETIO] HTTP error: ' . $httpCode . ' - Response: ' . $response);
        }

        error_log('[SOCKETIO] Broadcast sent to ' . $url . ' - Event: ' . $payload['event']);
    }
}
