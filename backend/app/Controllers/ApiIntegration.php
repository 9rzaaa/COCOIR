<?php

namespace App\Controllers;

use App\Libraries\CocoExpressCourierService;
use App\Libraries\IntegrationHttpClient;
use App\Libraries\PayMongoGatewayService;
use App\Models\IntegrationLogModel;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\WebhookEventModel;

/**
 * REST API v1 & Webhook Integration Controller
 * Implements CLO 5 (Middleware & APIs) and CLO 6 (Reliability, Validation & Security)
 */
class ApiIntegration extends BaseController
{
    /**
     * GET /api/v1/health
     * Returns integration health, circuit breaker states, and active chaos modes.
     */
    public function health()
    {
        $client = new IntegrationHttpClient();
        $chaos  = IntegrationHttpClient::getChaosConfig();

        return $this->response
            ->setHeader('X-API-Version', '2026-11-01')
            ->setJSON([
                'status'      => 'healthy',
                'service'     => 'cocoir-core-api',
                'api_version' => '2026-11-01',
                'timestamp'   => date('c'),
                'integrations' => [
                    'paymongo' => [
                        'protocol'      => 'REST JSON + HMAC-SHA256 Webhook',
                        'circuit_state' => $client->getCircuitState('paymongo'),
                        'chaos_mode'    => $chaos['paymongo'] ?? 'normal',
                    ],
                    'cocoexpress_courier' => [
                        'protocol'      => 'REST JSON + HMAC-SHA256 Webhook',
                        'circuit_state' => $client->getCircuitState('cocoexpress_courier'),
                        'chaos_mode'    => $chaos['cocoexpress_courier'] ?? 'normal',
                    ],
                ],
            ]);
    }

    /**
     * POST /api/v1/webhooks/paymongo
     * Protected by 'webhook_hmac' filter (WebhookSignatureFilter)
     */
    public function paymongoWebhook()
    {
        $startTime     = microtime(true);
        $rawBody       = (string) $this->request->getBody();
        $correlationId = $this->request->getHeaderLine('X-Correlation-ID') ?: ('wh-pm-' . bin2hex(random_bytes(5)));

        $payload = json_decode($rawBody, true);
        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            return $this->rejectMalformedWebhook('paymongo', $correlationId, $rawBody, 'Malformed JSON syntax in webhook body', 400);
        }

        // Strict schema validation
        $eventId   = $payload['data']['id'] ?? null;
        $eventType = $payload['data']['attributes']['type'] ?? null;
        $innerAttr = $payload['data']['attributes']['data']['attributes'] ?? null;
        $orderRef  = $innerAttr['reference_number'] ?? null;
        $amount    = $innerAttr['amount'] ?? null;

        if (empty($eventId) || empty($eventType) || !is_array($innerAttr) || empty($orderRef) || !is_numeric($amount) || (int) $amount <= 0) {
            return $this->rejectMalformedWebhook(
                'paymongo',
                $correlationId,
                $rawBody,
                'Schema validation failed: missing data.id, attributes.type, reference_number, or positive numeric amount',
                422
            );
        }

        $webhookModel = new WebhookEventModel();
        if ($webhookModel->hasProcessed($eventId)) {
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            (new IntegrationLogModel())->record([
                'correlation_id'   => $correlationId,
                'service_name'     => 'paymongo',
                'direction'        => 'inbound_webhook',
                'endpoint'         => '/api/v1/webhooks/paymongo',
                'http_method'      => 'POST',
                'status_code'      => 200,
                'latency_ms'       => $latency,
                'idempotency_key'  => $eventId,
                'circuit_state'    => 'CLOSED',
                'request_payload'  => substr($rawBody, 0, 1500),
                'response_payload' => '{"status":"ignored_duplicate"}',
            ]);

            return $this->response->setStatusCode(200)->setJSON([
                'status'               => 'ok',
                'idempotent_duplicate' => true,
                'event_id'             => $eventId,
                'message'              => 'Event already processed; skipped duplicate execution.',
            ]);
        }

        $orderModel = new OrderModel();
        $order      = $orderModel->where('order_number', $orderRef)->first();
        if (!$order && is_numeric($orderRef)) {
            $order = $orderModel->find((int) $orderRef);
        }

        if (!$order) {
            return $this->rejectMalformedWebhook('paymongo', $correlationId, $rawBody, "Referenced order '{$orderRef}' does not exist", 404);
        }

        $stockDeductedNow = false;
        if ($eventType === 'checkout_session.payment.paid' || $eventType === 'payment.paid') {
            $updateData = [
                'payment_status'    => 'paid',
                'payment_reference' => $payload['data']['attributes']['data']['id'] ?? $eventId,
                'paid_at'           => $order['paid_at'] ?: date('Y-m-d H:i:s'),
            ];
            if (($order['status'] ?? '') === 'to_pay' || ($order['status'] ?? '') === 'pending') {
                $updateData['status'] = 'to_ship';
            }
            $orderModel->update($order['id'], $updateData);
            $stockDeductedNow = $orderModel->deductStockIdempotent((int) $order['id']);
        } elseif ($eventType === 'payment.failed') {
            $orderModel->update($order['id'], [
                'payment_status' => 'failed',
                'status'         => 'to_pay',
            ]);
        }

        $webhookModel->insert([
            'event_id'        => $eventId,
            'provider'        => 'paymongo',
            'event_type'      => $eventType,
            'signature_valid' => 1,
            'status'          => 'processed',
            'payload'         => substr($rawBody, 0, 2000),
            'processed_at'    => date('Y-m-d H:i:s'),
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        $responseBody = [
            'status'         => 'processed',
            'correlation_id' => $correlationId,
            'event_id'       => $eventId,
            'order_number'   => $order['order_number'],
            'payment_status' => $eventType === 'payment.failed' ? 'failed' : 'paid',
            'stock_deducted' => $stockDeductedNow,
        ];

        $latency = round((microtime(true) - $startTime) * 1000, 2);
        (new IntegrationLogModel())->record([
            'correlation_id'   => $correlationId,
            'service_name'     => 'paymongo',
            'direction'        => 'inbound_webhook',
            'endpoint'         => '/api/v1/webhooks/paymongo',
            'http_method'      => 'POST',
            'status_code'      => 200,
            'latency_ms'       => $latency,
            'idempotency_key'  => $eventId,
            'circuit_state'    => 'CLOSED',
            'request_payload'  => substr($rawBody, 0, 1500),
            'response_payload' => json_encode($responseBody),
        ]);

        return $this->response->setStatusCode(200)->setJSON($responseBody);
    }

    /**
     * POST /api/v1/webhooks/courier
     * Protected by 'webhook_hmac' filter (WebhookSignatureFilter)
     */
    public function courierWebhook()
    {
        $startTime     = microtime(true);
        $rawBody       = (string) $this->request->getBody();
        $correlationId = $this->request->getHeaderLine('X-Correlation-ID') ?: ('wh-cr-' . bin2hex(random_bytes(5)));

        $payload = json_decode($rawBody, true);
        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            return $this->rejectMalformedWebhook('cocoexpress_courier', $correlationId, $rawBody, 'Malformed JSON syntax in courier webhook', 400);
        }

        $eventId        = $payload['event_id'] ?? null;
        $eventType      = $payload['event_type'] ?? null;
        $trackingNumber = $payload['data']['tracking_number'] ?? null;
        $orderNumber    = $payload['data']['order_number'] ?? null;
        $courierStatus  = $payload['data']['status'] ?? null;

        $allowedStatuses = ['pickup_scheduled', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'delivery_failed'];
        if (empty($eventId) || empty($eventType) || empty($trackingNumber) || empty($orderNumber) || !in_array($courierStatus, $allowedStatuses, true)) {
            return $this->rejectMalformedWebhook(
                'cocoexpress_courier',
                $correlationId,
                $rawBody,
                'Schema validation failed: missing event_id, tracking_number, order_number, or valid status enum',
                422
            );
        }

        $webhookModel = new WebhookEventModel();
        if ($webhookModel->hasProcessed($eventId)) {
            return $this->response->setStatusCode(200)->setJSON([
                'status'               => 'ok',
                'idempotent_duplicate' => true,
                'event_id'             => $eventId,
            ]);
        }

        $orderModel = new OrderModel();
        $order      = $orderModel->where('order_number', $orderNumber)->first();
        if (!$order) {
            return $this->rejectMalformedWebhook('cocoexpress_courier', $correlationId, $rawBody, "Order '{$orderNumber}' not found", 404);
        }

        $history = !empty($order['tracking_history']) ? (json_decode($order['tracking_history'], true) ?: []) : [];
        if (!empty($payload['data']['checkpoint']) && is_array($payload['data']['checkpoint'])) {
            $history[] = $payload['data']['checkpoint'];
        } else {
            $history[] = [
                'status'      => $courierStatus,
                'location'    => $payload['data']['location'] ?? 'CocoExpress Hub',
                'description' => $payload['data']['description'] ?? ("Courier status updated to {$courierStatus}"),
                'timestamp'   => date('Y-m-d H:i:s'),
            ];
        }

        $newOrderStatus = $order['status'];
        if (in_array($courierStatus, ['picked_up', 'in_transit', 'out_for_delivery'], true)) {
            $newOrderStatus = 'to_receive';
        } elseif ($courierStatus === 'delivered') {
            $newOrderStatus = 'completed';
        }

        $updateFields = [
            'tracking_number'    => $trackingNumber,
            'tracking_status'    => $courierStatus,
            'tracking_history'   => json_encode($history),
            'tracking_synced_at' => date('Y-m-d H:i:s'),
            'status'             => $newOrderStatus,
        ];
        if ($courierStatus === 'delivered' && empty($order['paid_at'])) {
            $updateFields['payment_status'] = 'paid';
            $updateFields['paid_at']        = date('Y-m-d H:i:s');
        }

        $orderModel->update($order['id'], $updateFields);

        $webhookModel->insert([
            'event_id'        => $eventId,
            'provider'        => 'cocoexpress_courier',
            'event_type'      => $eventType,
            'signature_valid' => 1,
            'status'          => 'processed',
            'payload'         => substr($rawBody, 0, 2000),
            'processed_at'    => date('Y-m-d H:i:s'),
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        $responseBody = [
            'status'          => 'processed',
            'correlation_id'  => $correlationId,
            'event_id'        => $eventId,
            'order_number'    => $order['order_number'],
            'tracking_number' => $trackingNumber,
            'tracking_status' => $courierStatus,
            'order_status'    => $newOrderStatus,
        ];

        $latency = round((microtime(true) - $startTime) * 1000, 2);
        (new IntegrationLogModel())->record([
            'correlation_id'   => $correlationId,
            'service_name'     => 'cocoexpress_courier',
            'direction'        => 'inbound_webhook',
            'endpoint'         => '/api/v1/webhooks/courier',
            'http_method'      => 'POST',
            'status_code'      => 200,
            'latency_ms'       => $latency,
            'idempotency_key'  => $eventId,
            'circuit_state'    => 'CLOSED',
            'request_payload'  => substr($rawBody, 0, 1500),
            'response_payload' => json_encode($responseBody),
        ]);

        return $this->response->setStatusCode(200)->setJSON($responseBody);
    }

    /**
     * GET /api/v1/orders/(:segment)/tracking
     * Fetches live tracking from CocoExpress Courier API or falls back to cached tracking on failure.
     */
    public function orderTracking(string $orderIdentifier)
    {
        $orderModel = new OrderModel();
        $order      = is_numeric($orderIdentifier)
            ? $orderModel->find((int) $orderIdentifier)
            : $orderModel->where('order_number', $orderIdentifier)->first();

        if (!$order) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'error'  => ['code' => 'ORDER_NOT_FOUND', 'message' => "Order {$orderIdentifier} not found."],
            ]);
        }

        $courierService = new CocoExpressCourierService();
        $trackingResult = $courierService->getTrackingStatus($order);

        return $this->response->setStatusCode(200)->setJSON([
            'status'       => 'ok',
            'order_number' => $order['order_number'],
            'order_status' => $order['status'],
            'degraded'     => $trackingResult['degraded'],
            'source'       => $trackingResult['source'],
            'message'      => $trackingResult['message'],
            'tracking'     => $trackingResult['tracking'],
        ]);
    }

    private function rejectMalformedWebhook(
        string $provider,
        string $correlationId,
        string $rawBody,
        string $reason,
        int $statusCode = 422
    ) {
        try {
            (new WebhookEventModel())->insert([
                'event_id'        => 'malformed-' . bin2hex(random_bytes(6)),
                'provider'        => $provider,
                'event_type'      => 'validation.malformed_payload',
                'signature_valid' => 1,
                'status'          => 'rejected_malformed',
                'payload'         => substr($rawBody, 0, 1500),
                'processed_at'    => date('Y-m-d H:i:s'),
                'created_at'      => date('Y-m-d H:i:s'),
            ]);

            (new IntegrationLogModel())->record([
                'correlation_id'   => $correlationId,
                'service_name'     => $provider,
                'direction'        => 'inbound_webhook',
                'endpoint'         => $provider === 'paymongo' ? '/api/v1/webhooks/paymongo' : '/api/v1/webhooks/courier',
                'http_method'      => 'POST',
                'status_code'      => $statusCode,
                'latency_ms'       => 1.50,
                'retry_count'      => 0,
                'circuit_state'    => 'CLOSED',
                'error_message'    => $reason,
                'request_payload'  => substr($rawBody, 0, 1500),
                'response_payload' => json_encode(['error' => ['code' => 'MALFORMED_PAYLOAD', 'message' => $reason]]),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'rejectMalformedWebhook log error: ' . $e->getMessage());
        }

        return $this->response
            ->setStatusCode($statusCode)
            ->setHeader('X-Correlation-ID', $correlationId)
            ->setHeader('X-API-Version', '2026-11-01')
            ->setJSON([
                'status'         => 'error',
                'correlation_id' => $correlationId,
                'error'          => [
                    'code'    => 'MALFORMED_PAYLOAD',
                    'message' => $reason,
                ],
            ]);
    }
}
