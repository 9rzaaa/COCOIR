<?php

namespace App\Libraries;

/**
 * Published Interface for COCOIR Reusable Service Integration Client (Technical 3 Artifact)
 * Version: 1.2.0
 */
interface IntegrationClientInterface
{
    /**
     * Execute an resilient, observable HTTP request to an external service.
     *
     * @param string      $serviceName    Logical service identifier (e.g., 'paymongo', 'cocoexpress_courier')
     * @param string      $method         HTTP verb ('GET', 'POST', 'PUT', 'PATCH', 'DELETE')
     * @param string      $url            Target endpoint URL
     * @param array       $payload        JSON-serializable associative array payload
     * @param array       $headers        Additional HTTP headers
     * @param string|null $idempotencyKey Optional idempotency token for safe retries
     *
     * @return array{
     *   ok: bool,
     *   status_code: int,
     *   data: array|null,
     *   raw_body: string,
     *   latency_ms: float,
     *   retry_count: int,
     *   correlation_id: string,
     *   idempotency_key: string,
     *   circuit_state: string,
     *   error: string|null
     * }
     */
    public function request(
        string $serviceName,
        string $method,
        string $url,
        array $payload = [],
        array $headers = [],
        ?string $idempotencyKey = null
    ): array;

    /**
     * Compute or verify an HMAC-SHA256 webhook signature with timestamp replay protection.
     */
    public static function signWebhookPayload(string $rawBody, string $secret, ?int $timestamp = null): string;

    /**
     * Verify an incoming webhook signature header (`t=<ts>,v1=<hmac>`).
     */
    public static function verifyWebhookSignature(string $rawBody, string $signatureHeader, string $secret, int $toleranceSeconds = 300): bool;
}
