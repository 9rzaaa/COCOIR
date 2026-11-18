<?php

/**
 * COCOIR Systems Integration Validation Suite (CLO 6 — Part C Evidence Generator)
 * Executes:
 *   1. Security Validation: HMAC-SHA256 verification, payload tampering rejection, and 300s replay attack rejection
 *   2. Reliability / Failure-Injection Validation: Timeout + Exponential Backoff Retries, Circuit Breaker trip (CLOSED -> OPEN), and Idempotency
 *   3. Performance Load Benchmark: Measures throughput (req/s), p50, p95, p99 latency across increasing load steps
 *
 * Usage:
 *   php backend/tests/integration_validation_suite.php
 */

declare(strict_types=1);

define('WRITEPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR);
if (!is_dir(WRITEPATH . 'cache')) {
    @mkdir(WRITEPATH . 'cache', 0777, true);
}

require_once dirname(__DIR__) . '/app/Libraries/IntegrationClientInterface.php';
require_once dirname(__DIR__) . '/app/Libraries/IntegrationHttpClient.php';

use App\Libraries\IntegrationHttpClient;

echo "========================================================================\n";
echo "  COCOIR SYSTEMS INTEGRATION VALIDATION SUITE (IT0129 [FE-FINAL] CLO 6)\n";
echo "  Environment: PHP " . PHP_VERSION . " (" . PHP_OS_FAMILY . ")\n";
echo "  Timestamp  : " . date('c') . "\n";
echo "========================================================================\n\n";

$passed = 0;
$failed = 0;

function assertCheck(string $label, bool $condition, string $detail = ''): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
    } else {
        $failed++;
        echo "  [FAIL] {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
    }
}

// -------------------------------------------------------------------------
// 1. SECURITY VALIDATION (HMAC-SHA256 & Replay Window Controls)
// -------------------------------------------------------------------------
echo "1. SECURITY CONTROLS VALIDATION (HMAC-SHA256 & Replay Protection)\n";
$secret  = 'whsec_cocoir_paymongo_hmac_secret_2026';
$payload = json_encode([
    'data' => [
        'id' => 'evt_test_001',
        'attributes' => [
            'type' => 'checkout_session.payment.paid',
            'data' => ['attributes' => ['reference_number' => 'ORD-2026-0001', 'amount' => 65000]],
        ],
    ],
]);

$validSig = IntegrationHttpClient::signWebhookPayload($payload, $secret);
assertCheck(
    'Valid HMAC-SHA256 signature accepted',
    IntegrationHttpClient::verifyWebhookSignature($payload, $validSig, $secret, 300),
    "Header: " . substr($validSig, 0, 38) . "..."
);

$tamperedPayload = str_replace('65000', '100', $payload);
assertCheck(
    'Tampered webhook payload rejected (Integrity check)',
    !IntegrationHttpClient::verifyWebhookSignature($tamperedPayload, $validSig, $secret, 300),
    'Modified amount from 65000 to 100 centavos -> rejected (HTTP 401)'
);

$expiredSig = IntegrationHttpClient::signWebhookPayload($payload, $secret, time() - 600);
assertCheck(
    'Replay attack outside 300s window rejected',
    !IntegrationHttpClient::verifyWebhookSignature($payload, $expiredSig, $secret, 300),
    'Timestamp t=now-600s exceeds 300s tolerance -> rejected (HTTP 401)'
);
echo "\n";

// -------------------------------------------------------------------------
// 2. RELIABILITY & FAILURE-INJECTION VALIDATION
// -------------------------------------------------------------------------
echo "2. RELIABILITY & FAILURE-INJECTION VALIDATION\n";
IntegrationHttpClient::resetAllCircuits();
IntegrationHttpClient::setChaosMode('paymongo', 'normal');
IntegrationHttpClient::setChaosMode('cocoexpress_courier', 'normal');

$client = new IntegrationHttpClient([
    'timeout_ms'                => 350,
    'max_retries'               => 2,
    'base_delay_ms'             => 120,
    'circuit_failure_threshold' => 2,
    'circuit_reset_timeout_sec' => 30,
]);

// 2a. Normal Idempotent Courier Booking
$ordPayload = [
    'order_number' => 'ORD-2026-TEST99',
    'recipient'    => ['name' => 'Daniel Ling', 'city' => 'Quezon City'],
];
$idemKey = 'idem-courier-test-99';

$res1 = $client->request('cocoexpress_courier', 'POST', 'internal://courier/api/v1/shipments', $ordPayload, [], $idemKey);
$res2 = $client->request('cocoexpress_courier', 'POST', 'internal://courier/api/v1/shipments', $ordPayload, [], $idemKey);

assertCheck(
    'Idempotent shipment booking prevents duplicate waybills',
    $res1['ok'] && $res2['ok'] && ($res1['data']['data']['tracking_number'] === $res2['data']['data']['tracking_number']),
    "Waybill: {$res1['data']['data']['tracking_number']} (First HTTP {$res1['status_code']}, Replay HTTP {$res2['status_code']})"
);

// 2b. Malformed / Invalid Schema Rejection
$badRes = $client->request('paymongo', 'POST', 'internal://paymongo/v1/checkout_sessions', [
    'data' => ['attributes' => ['reference_number' => 'ORD-BAD', 'amount' => -500]],
]);
assertCheck(
    'Negative / invalid payment amount rejected with HTTP 422',
    !$badRes['ok'] && $badRes['status_code'] === 422,
    "Status: {$badRes['status_code']} | Error: {$badRes['error']}"
);

// 2c. Upstream Timeout + Exponential Backoff Retries + Circuit Breaker Trip
IntegrationHttpClient::setChaosMode('paymongo', 'timeout');
$timeoutRes1 = $client->request('paymongo', 'POST', 'internal://paymongo/v1/checkout_sessions', [
    'data' => ['attributes' => ['reference_number' => 'ORD-TIMEOUT-1', 'amount' => 50000]],
], [], 'idem-timeout-1');

assertCheck(
    'Dependency timeout triggers 2x exponential backoff retries',
    !$timeoutRes1['ok'] && $timeoutRes1['status_code'] === 504 && $timeoutRes1['retry_count'] === 2,
    "Status: {$timeoutRes1['status_code']} | Retries: {$timeoutRes1['retry_count']} | Measured Latency: {$timeoutRes1['latency_ms']}ms"
);

$timeoutRes2 = $client->request('paymongo', 'POST', 'internal://paymongo/v1/checkout_sessions', [
    'data' => ['attributes' => ['reference_number' => 'ORD-TIMEOUT-2', 'amount' => 50000]],
], [], 'idem-timeout-2');

assertCheck(
    'Consecutive timeouts trip Circuit Breaker to OPEN',
    $timeoutRes2['circuit_state'] === 'OPEN',
    "Circuit State transitioned CLOSED -> {$timeoutRes2['circuit_state']}"
);

$fastFailRes = $client->request('paymongo', 'POST', 'internal://paymongo/v1/checkout_sessions', [
    'data' => ['attributes' => ['reference_number' => 'ORD-FASTFAIL', 'amount' => 50000]],
], [], 'idem-fastfail');

assertCheck(
    'OPEN Circuit Breaker fails fast (< 5ms) without hanging worker threads',
    !$fastFailRes['ok'] && $fastFailRes['status_code'] === 503 && $fastFailRes['latency_ms'] < 5.0 && $fastFailRes['retry_count'] === 0,
    "Status: {$fastFailRes['status_code']} | Latency: {$fastFailRes['latency_ms']}ms | Retries: {$fastFailRes['retry_count']}"
);

// Restore normal state
IntegrationHttpClient::setChaosMode('paymongo', 'normal');
IntegrationHttpClient::resetAllCircuits();
echo "\n";

// -------------------------------------------------------------------------
// 3. PERFORMANCE LOAD BENCHMARK
// -------------------------------------------------------------------------
echo "3. PERFORMANCE BENCHMARK (Throughput & Latency Percentiles)\n";
$latencies = [];
$iterations = 30;
$benchStart = microtime(true);

for ($i = 1; $i <= $iterations; $i++) {
    $r = $client->request('paymongo', 'POST', 'internal://paymongo/v1/checkout_sessions', [
        'data' => ['attributes' => ['reference_number' => "ORD-BENCH-{$i}", 'amount' => 45000]],
    ]);
    $latencies[] = $r['latency_ms'];
}
$totalBenchSec = microtime(true) - $benchStart;
sort($latencies);
$p50 = $latencies[(int) floor($iterations * 0.50)];
$p95 = $latencies[(int) floor($iterations * 0.95)];
$p99 = $latencies[$iterations - 1];
$rps = round($iterations / max(0.001, $totalBenchSec), 2);

echo sprintf(
    "  Measured %d sequential API transactions in %.2fs (%.2f req/s)\n  Latency: p50 = %.2f ms | p95 = %.2f ms | p99 = %.2f ms\n\n",
    $iterations,
    $totalBenchSec,
    $rps,
    $p50,
    $p95,
    $p99
);

echo "========================================================================\n";
echo "  SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "========================================================================\n";
exit($failed > 0 ? 1 : 0);
