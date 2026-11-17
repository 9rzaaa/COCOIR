<?php

namespace App\Libraries;

/**
 * PayMongo Payment Gateway Service Adapter (Integration Point 1)
 * Protocol: REST over HTTPS (JSON API v1) + HMAC-SHA256 Signed Webhooks
 */
class PayMongoGatewayService
{
    private IntegrationClientInterface $client;
    private string $baseUrl;
    private string $secretKey;
    private string $webhookSecret;

    public function __construct(?IntegrationClientInterface $client = null)
    {
        $this->client        = $client ?? new IntegrationHttpClient(['timeout_ms' => 1500, 'max_retries' => 2]);
        $this->baseUrl       = getenv('PAYMONGO_API_URL') ?: 'internal://paymongo.sandbox/v1';
        $this->secretKey     = getenv('PAYMONGO_SECRET_KEY') ?: 'sk_test_cocoir_paymongo_2026_key';
        $this->webhookSecret = getenv('PAYMONGO_WEBHOOK_SECRET') ?: 'whsec_cocoir_paymongo_hmac_secret_2026';
    }

    public function getWebhookSecret(): string
    {
        return $this->webhookSecret;
    }

    /**
     * Create a PayMongo Checkout Session for an order.
     */
    public function createCheckoutSession(array $order, array $items): array
    {
        $amountCentavos = (int) round(((float) ($order['total_amount'] ?? 0)) * 100);
        $orderNumber    = $order['order_number'] ?? ('ORD-' . ($order['id'] ?? '0'));

        $lineItems = [];
        foreach ($items as $it) {
            $lineItems[] = [
                'name'     => $it['product_name'] ?? $it['name'] ?? 'COCOIR Eco Product',
                'quantity' => (int) ($it['quantity'] ?? 1),
                'amount'   => (int) round(((float) ($it['unit_price'] ?? $it['price'] ?? 0)) * 100),
                'currency' => 'PHP',
            ];
        }

        $payload = [
            'data' => [
                'attributes' => [
                    'reference_number'     => $orderNumber,
                    'amount'               => $amountCentavos,
                    'currency'             => 'PHP',
                    'description'          => "COCOIR Order {$orderNumber}",
                    'payment_method_types' => ['qrph', 'gcash', 'paymaya'],
                    'line_items'           => $lineItems,
                    'success_url'          => site_url('orders?tab=to_ship&paid=' . urlencode($orderNumber)),
                    'cancel_url'           => site_url('orders?tab=to_pay'),
                ],
            ],
        ];

        $headers = [
            'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':'),
        ];

        $idempotencyKey = 'paymongo-cs-' . $orderNumber;

        return $this->client->request(
            'paymongo',
            'POST',
            rtrim($this->baseUrl, '/') . '/checkout_sessions',
            $payload,
            $headers,
            $idempotencyKey
        );
    }
}
