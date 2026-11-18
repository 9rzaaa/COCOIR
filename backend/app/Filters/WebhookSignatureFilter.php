<?php

namespace App\Filters;

use App\Libraries\CocoExpressCourierService;
use App\Libraries\IntegrationHttpClient;
use App\Libraries\PayMongoGatewayService;
use App\Models\IntegrationLogModel;
use App\Models\WebhookEventModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Middleware Filter: WebhookSignatureFilter
 * Validates HMAC-SHA256 signatures and timestamp replay window on inbound webhooks.
 */
class WebhookSignatureFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $uri     = (string) $request->getUri()->getPath();
        $rawBody = (string) $request->getBody();

        $isPayMongo = str_contains($uri, 'paymongo');
        $provider   = $isPayMongo ? 'paymongo' : 'cocoexpress_courier';

        $secret = $isPayMongo
            ? (new PayMongoGatewayService())->getWebhookSecret()
            : (new CocoExpressCourierService())->getWebhookSecret();

        $sigHeader = $request->getHeaderLine('X-PayMongo-Signature')
            ?: $request->getHeaderLine('Paymongo-Signature')
            ?: $request->getHeaderLine('X-Courier-Signature')
            ?: $request->getHeaderLine('X-Webhook-Signature');

        $correlationId = $request->getHeaderLine('X-Correlation-ID') ?: ('wh-corr-' . bin2hex(random_bytes(5)));

        if (!IntegrationHttpClient::verifyWebhookSignature($rawBody, $sigHeader, $secret, 300)) {
            // Log security rejection in both webhook_events and integration_logs
            try {
                (new WebhookEventModel())->insert([
                    'event_id'        => 'rejected-' . bin2hex(random_bytes(6)),
                    'provider'        => $provider,
                    'event_type'      => 'security.signature_verification_failed',
                    'signature_valid' => 0,
                    'status'          => 'rejected_signature',
                    'payload'         => substr($rawBody, 0, 1500),
                    'processed_at'    => date('Y-m-d H:i:s'),
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);

                (new IntegrationLogModel())->record([
                    'correlation_id'   => $correlationId,
                    'service_name'     => $provider,
                    'direction'        => 'inbound_webhook',
                    'endpoint'         => '/' . ltrim($uri, '/'),
                    'http_method'      => 'POST',
                    'status_code'      => 401,
                    'latency_ms'       => 1.20,
                    'retry_count'      => 0,
                    'circuit_state'    => 'CLOSED',
                    'error_message'    => 'HMAC-SHA256 signature verification failed or timestamp expired',
                    'request_payload'  => substr($rawBody, 0, 1500),
                    'response_payload' => '{"error":{"code":"INVALID_SIGNATURE","message":"Webhook HMAC-SHA256 signature verification failed."}}',
                ]);
            } catch (\Throwable $e) {
                log_message('error', 'WebhookSignatureFilter log error: ' . $e->getMessage());
            }

            return service('response')
                ->setStatusCode(401)
                ->setHeader('X-Correlation-ID', $correlationId)
                ->setHeader('X-API-Version', '2026-11-01')
                ->setJSON([
                    'status'         => 'error',
                    'correlation_id' => $correlationId,
                    'error'          => [
                        'code'    => 'INVALID_WEBHOOK_SIGNATURE',
                        'message' => 'Webhook HMAC-SHA256 signature verification failed or timestamp outside 300s replay window.',
                    ],
                ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('X-API-Version', '2026-11-01');
        return $response;
    }
}
