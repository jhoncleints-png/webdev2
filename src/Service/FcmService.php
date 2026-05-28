<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FcmService
{
    private const PROJECT_ID = 'brewery-f7ace';
    private const FCM_API_URL = 'https://fcm.googleapis.com/v1/projects/' . self::PROJECT_ID . '/messages:send';

    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    /**
     * Get OAuth2 access token from service account credentials
     */
    private function getAccessToken(): ?string
    {
        try {
            $serviceAccountJson = $_ENV['FIREBASE_SERVICE_ACCOUNT'] ?? '';
            
            if (empty($serviceAccountJson)) {
                error_log('FIREBASE_SERVICE_ACCOUNT not set in .env');
                return null;
            }

            $serviceAccount = json_decode($serviceAccountJson, true);
            
            if (!$serviceAccount) {
                error_log('Invalid FIREBASE_SERVICE_ACCOUNT JSON');
                return null;
            }

            // Create JWT for OAuth2
            $header = [
                'alg' => 'RS256',
                'typ' => 'JWT',
            ];

            $now = time();
            $payload = [
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            // Encode header and payload
            $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
            $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

            // Sign with private key
            $signature = '';
            openssl_sign(
                $base64UrlHeader . '.' . $base64UrlPayload,
                $signature,
                $serviceAccount['private_key'],
                OPENSSL_ALGO_SHA256
            );

            $base64UrlSignature = $this->base64UrlEncode($signature);
            $jwt = $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;

            // Exchange JWT for access token
            $response = $this->httpClient->request(
                'POST',
                'https://oauth2.googleapis.com/token',
                [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'body' => http_build_query([
                        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                        'assertion' => $jwt,
                    ]),
                ]
            );

            $data = $response->toArray();
            return $data['access_token'] ?? null;
        } catch (\Exception $e) {
            error_log('FCM OAuth2 Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Send FCM notification using HTTP v1 API
     */
    public function sendNotification(
        string $fcmToken,
        string $title,
        string $body,
        array $data = []
    ): bool {
        try {
            $accessToken = $this->getAccessToken();
            
            if (!$accessToken) {
                error_log('Failed to get FCM access token');
                return false;
            }

            $message = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'samaco_orders',
                            'sound' => 'default',
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'alert' => [
                                    'title' => $title,
                                    'body' => $body,
                                ],
                                'sound' => 'default',
                                'badge' => 1,
                            ],
                        ],
                    ],
                ],
            ];

            $response = $this->httpClient->request(
                'POST',
                self::FCM_API_URL,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $message,
                ]
            );

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                return true;
            }

            error_log('FCM Error: ' . $response->getContent(false));
            return false;
        } catch (\Exception $e) {
            error_log('FCM Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send order status update notification
     */
    public function sendOrderStatusNotification(
        string $fcmToken,
        string $orderNumber,
        string $status,
        string $customerName
    ): bool {
        $statusMessages = [
            'pending' => 'Your order is being processed',
            'delivered' => 'Your order has been delivered! 🎉',
            'cancelled' => 'Your order has been cancelled',
        ];

        $title = "Order #{$orderNumber} Update";
        $body = $statusMessages[$status] ?? "Order status: {$status}";

        return $this->sendNotification(
            $fcmToken,
            $title,
            $body,
            [
                'type' => 'order_status',
                'orderNumber' => $orderNumber,
                'status' => $status,
                'customerName' => $customerName,
            ]
        );
    }
}
