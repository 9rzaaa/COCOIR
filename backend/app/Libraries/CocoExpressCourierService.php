<?php

namespace App\Libraries;

use App\Models\OrderModel;

/**
 * CocoExpress Courier & Logistics Tracking Service Adapter (Integration Point 2)
 * Protocol: REST API v1 + Asynchronous Webhook Callbacks
 */
class CocoExpressCourierService
{
    private IntegrationClientInterface $client;
    private string $baseUrl;
    private string $apiKey;
    private string $webhookSecret;

    public function __construct(?IntegrationClientInterface $client = null)
    {
        $this->client = $client ?? new IntegrationHttpClient(['timeout_ms' => 1500, 'max_retries' => 2]);

        $envUrl = getenv('COURIER_API_URL');
        if (!empty($envUrl)) {
            $this->baseUrl = $envUrl;
        } elseif (gethostbyname('courier') !== 'courier') {
            $this->baseUrl = 'http://courier:8095/api/v1';
        } else {
            // Railway / single-container mode: connects to background Courier Microservice on 127.0.0.1:8095
            $this->baseUrl = 'http://127.0.0.1:8095/api/v1';
        }

        $this->apiKey        = getenv('COURIER_API_KEY') ?: 'ck_live_cocoexpress_2026_secret_token';
        $this->webhookSecret = getenv('COURIER_WEBHOOK_SECRET') ?: 'whsec_cocoexpress_courier_hmac_2026';
    }

    public function getWebhookSecret(): string
    {
        return $this->webhookSecret;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Book a shipment with CocoExpress Courier Service when an order is ready to ship.
     */
    public function bookShipment(array $order, array $items = []): array
    {
        $orderNumber = $order['order_number'] ?? ('ORD-' . $order['id']);
        $payload = [
            'order_number' => $orderNumber,
            'service_tier' => 'standard_eco',
            'recipient'    => [
                'name'        => $order['recipient_name'] ?? 'Valued Customer',
                'phone'       => $order['recipient_phone'] ?? '+639000000000',
                'email'       => $order['recipient_email'] ?? '',
                'address'     => $order['address'] ?? 'Store Pickup',
                'city'        => $order['city'] ?? 'Quezon City',
                'postal_code' => $order['postal_code'] ?? '1100',
            ],
            'cod_amount'   => ($order['payment_method'] ?? '') === 'cod' ? (float) ($order['total_amount'] ?? 0) : 0.0,
            'items_count'  => count($items) ?: 1,
            'webhook_url'  => site_url('api/v1/webhooks/courier'),
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'X-API-Key'     => $this->apiKey,
        ];

        $idempotencyKey = 'courier-book-' . $orderNumber;

        return $this->client->request(
            'cocoexpress_courier',
            'POST',
            rtrim($this->baseUrl, '/') . '/shipments',
            $payload,
            $headers,
            $idempotencyKey
        );
    }

    /**
     * Fetch live tracking from Courier Service with Graceful Degradation fallback to cached DB state.
     */
    public function getTrackingStatus(array $order): array
    {
        $trackingNumber = $order['tracking_number'] ?? null;
        if (empty($trackingNumber)) {
            return [
                'ok'           => false,
                'degraded'     => false,
                'source'       => 'none',
                'tracking'     => null,
                'message'      => 'No courier waybill assigned yet.',
            ];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'X-API-Key'     => $this->apiKey,
        ];

        $res = $this->client->request(
            'cocoexpress_courier',
            'GET',
            rtrim($this->baseUrl, '/') . '/shipments/' . urlencode($trackingNumber),
            [],
            $headers,
            'courier-track-' . $trackingNumber . '-' . floor(time() / 10)
        );

        if ($res['ok'] && !empty($res['data']['data'])) {
            $shipment = $res['data']['data'];
            // Update local cache on order
            try {
                $orderModel = new OrderModel();
                $orderModel->update($order['id'], [
                    'tracking_status'    => $shipment['status'] ?? $order['tracking_status'],
                    'tracking_history'   => json_encode($shipment['checkpoints'] ?? []),
                    'tracking_synced_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                // ignore cache write issues
            }

            return [
                'ok'         => true,
                'degraded'   => false,
                'source'     => 'live_courier_api',
                'latency_ms' => $res['latency_ms'],
                'tracking'   => $shipment,
                'message'    => 'Synced live with CocoExpress Courier API v1.',
            ];
        }

        // Graceful Degradation: return cached checkpoints from orders table!
        $cachedCheckpoints = !empty($order['tracking_history'])
            ? (json_decode($order['tracking_history'], true) ?: [])
            : [[
                'status'      => $order['tracking_status'] ?? 'in_transit',
                'location'    => 'COCOIR Fulfillment Hub',
                'description' => 'Shipment handed over to courier (cached milestone).',
                'timestamp'   => $order['tracking_synced_at'] ?? $order['updated_at'] ?? date('Y-m-d H:i:s'),
            ]];

        return [
            'ok'         => true,
            'degraded'   => true,
            'source'     => 'cached_fallback',
            'latency_ms' => $res['latency_ms'],
            'error'      => $res['error'] ?? ('Courier API returned HTTP ' . $res['status_code']),
            'tracking'   => [
                'tracking_number'    => $trackingNumber,
                'order_number'       => $order['order_number'] ?? '',
                'courier_name'       => 'CocoExpress Eco-Logistics (Cached)',
                'status'             => $order['tracking_status'] ?? 'in_transit',
                'estimated_delivery' => date('Y-m-d', strtotime('+3 days')),
                'checkpoints'        => $cachedCheckpoints,
                'last_synced_at'     => $order['tracking_synced_at'] ?? $order['updated_at'],
            ],
            'message'    => 'Live Courier API unavailable (' . ($res['error'] ?? 'HTTP ' . $res['status_code']) . '). Showing last cached tracking status.',
        ];
    }
}
