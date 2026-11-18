<?php
/**
 * View: admin/integrations.php
 * Controller: Admin::integrations()
 * Route: GET /admin/integrations
 */
$chaosConfig = $chaosConfig ?? ['paymongo' => 'normal', 'cocoexpress_courier' => 'normal'];
$circuits    = $circuits ?? ['paymongo' => 'CLOSED', 'cocoexpress_courier' => 'CLOSED'];
$metrics     = $metrics ?? ['total_calls' => 0, 'success_calls' => 0, 'error_calls' => 0, 'total_retries' => 0, 'avg_latency_ms' => 0];
$logs        = $logs ?? [];
$events      = $events ?? [];
$orders      = $orders ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<?= $this->include('components/head', ['title' => 'Integrations & Chaos Center — COCOIR Admin']) ?>

<body class="site-admin bg-gray-50 font-body text-coco-brown">
<?= $this->include('components/admin_header') ?>

<main class="admin-main px-6 py-8">

    <!-- Flash Banners -->
    <?php if (session()->getFlashdata('success')): ?>
    <div class="mb-6 bg-green-50 border-2 border-green-300 text-green-800 px-5 py-4 rounded-2xl flex items-start gap-3 shadow-sm">
        <i class="fas fa-check-circle text-green-600 text-lg mt-0.5"></i>
        <div class="text-sm font-semibold"><?= esc(session()->getFlashdata('success')) ?></div>
    </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
    <div class="mb-6 bg-amber-50 border-2 border-amber-400 text-amber-900 px-5 py-4 rounded-2xl flex items-start gap-3 shadow-sm">
        <i class="fas fa-bolt text-amber-600 text-lg mt-0.5"></i>
        <div class="text-sm font-semibold"><?= esc(session()->getFlashdata('error')) ?></div>
    </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="bg-coco-orange/15 text-coco-orange text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">
                    CLO 5 &amp; CLO 6 · Enterprise Service Integration
                </span>
                <span class="bg-coco-green/15 text-coco-green text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">
                    API Version: 2026-11-01
                </span>
            </div>
            <h1 class="font-display font-black text-3xl text-coco-brown">Systems Integration &amp; Chaos Control Center</h1>
            <p class="text-coco-mid text-sm mt-0.5">
                Live middleware telemetry, Circuit Breaker status, Webhook HMAC-SHA256 verification ledger, and Failure-Injection controls.
            </p>
        </div>
        <div class="flex gap-2">
            <a href="<?= site_url('api/v1/health') ?>" target="_blank"
               class="border-2 border-gray-200 bg-white hover:border-coco-orange text-coco-brown text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-2 transition-colors">
                <i class="fas fa-heartbeat text-coco-orange"></i> GET /api/v1/health
            </a>
            <form action="<?= site_url('admin/integrations/chaos') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset_all">
                <button type="submit" class="bg-coco-green hover:bg-coco-dark text-white text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-2 transition-colors">
                    <i class="fas fa-sync-alt"></i> Reset All to Healthy
                </button>
            </form>
            <form action="<?= site_url('admin/integrations/chaos') ?>" method="POST" onsubmit="return confirm('Clear all orders, carts, webhook events, and integration logs for a fresh demo?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clear_demo_data">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-2 transition-colors">
                    <i class="fas fa-trash-alt"></i> Clear Orders &amp; Logs
                </button>
            </form>
        </div>
    </div>

    <!-- KPI Telemetry Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-coco-mid">Total API / Webhook Calls</div>
            <div class="font-display font-black text-2xl text-coco-brown mt-1"><?= (int) ($metrics['total_calls'] ?? 0) ?></div>
            <div class="text-[10px] text-coco-tan mt-0.5">Logged via IntegrationHttpClient</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-coco-mid">2xx Success Count</div>
            <div class="font-display font-black text-2xl text-green-600 mt-1"><?= (int) ($metrics['success_calls'] ?? 0) ?></div>
            <div class="text-[10px] text-coco-tan mt-0.5">Verified &amp; Idempotent</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-coco-mid">4xx / 5xx Faults Caught</div>
            <div class="font-display font-black text-2xl text-amber-600 mt-1"><?= (int) ($metrics['error_calls'] ?? 0) ?></div>
            <div class="text-[10px] text-coco-tan mt-0.5">Handled with safe fallback</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-coco-mid">Auto-Retries Executed</div>
            <div class="font-display font-black text-2xl text-coco-orange mt-1"><?= (int) ($metrics['total_retries'] ?? 0) ?></div>
            <div class="text-[10px] text-coco-tan mt-0.5">Exponential backoff + jitter</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-coco-mid">Avg Integration Latency</div>
            <div class="font-display font-black text-2xl text-coco-brown mt-1"><?= number_format((float) ($metrics['avg_latency_ms'] ?? 0), 1) ?> ms</div>
            <div class="text-[10px] text-coco-tan mt-0.5">Timeout threshold: 1500 ms</div>
        </div>
    </div>

    <!-- Connected External Systems & Chaos Toggles -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

        <!-- System 1: PayMongo Payment Gateway -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-black">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div>
                        <h2 class="font-display font-bold text-lg text-coco-brown">Integration Point 1: PayMongo Payment Gateway</h2>
                        <p class="text-xs text-coco-mid">REST JSON v1 (<code>POST /v1/checkout_sessions</code>) + Signed Webhook (<code>/api/v1/webhooks/paymongo</code>)</p>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1">
                    <?php $pmCircuit = $circuits['paymongo'] ?? 'CLOSED'; ?>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase <?= $pmCircuit === 'CLOSED' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                        Circuit: <?= esc($pmCircuit) ?>
                    </span>
                    <span class="text-[10px] text-coco-mid font-semibold">Mode: <strong><?= strtoupper($chaosConfig['paymongo'] ?? 'normal') ?></strong></span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs bg-gray-50 p-3.5 rounded-xl mb-4">
                <div><span class="text-coco-mid">Auth:</span> <strong>Basic Auth (Secret Key) + HMAC-SHA256</strong></div>
                <div><span class="text-coco-mid">Timeout / Retry:</span> <strong>1500ms · 2x Exp Backoff</strong></div>
                <div><span class="text-coco-mid">Idempotency:</span> <strong><code>Idempotency-Key</code> + <code>webhook_events</code></strong></div>
                <div><span class="text-coco-mid">Middleware:</span> <strong><code>WebhookSignatureFilter</code></strong></div>
            </div>

            <form action="<?= site_url('admin/integrations/chaos') ?>" method="POST" class="flex flex-wrap items-center gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="service" value="paymongo">
                <span class="text-xs font-bold text-coco-mid mr-1">Chaos Mode:</span>
                <?php foreach ([
                    'normal'             => ['label' => 'Normal (Healthy)', 'color' => 'bg-green-600 text-white'],
                    'timeout'            => ['label' => 'Simulate Timeout (504)', 'color' => 'bg-amber-500 text-white'],
                    'http_503'           => ['label' => 'Simulate 503 Outage', 'color' => 'bg-red-600 text-white'],
                    'malformed_response' => ['label' => 'Malformed JSON (502)', 'color' => 'bg-purple-600 text-white'],
                ] as $mKey => $mCfg):
                    $active = ($chaosConfig['paymongo'] ?? 'normal') === $mKey;
                ?>
                <button type="submit" name="mode" value="<?= $mKey ?>"
                        class="text-[11px] font-bold px-3 py-1.5 rounded-lg transition-all <?= $active ? $mCfg['color'] . ' shadow-sm ring-2 ring-offset-1 ring-gray-300' : 'bg-gray-100 text-coco-mid hover:bg-gray-200' ?>">
                    <?= $mCfg['label'] ?>
                </button>
                <?php endforeach; ?>
            </form>
        </div>

        <!-- System 2: CocoExpress Courier Microservice -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-orange-50 text-coco-orange flex items-center justify-center text-lg font-black">
                        <i class="fas fa-truck-fast"></i>
                    </div>
                    <div>
                        <h2 class="font-display font-bold text-lg text-coco-brown">Integration Point 2: CocoExpress Courier Service</h2>
                        <p class="text-xs text-coco-mid">Microservice API v1 (<code>POST/GET /api/v1/shipments</code>) + Webhook (<code>/api/v1/webhooks/courier</code>)</p>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1">
                    <?php $crCircuit = $circuits['cocoexpress_courier'] ?? 'CLOSED'; ?>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase <?= $crCircuit === 'CLOSED' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                        Circuit: <?= esc($crCircuit) ?>
                    </span>
                    <span class="text-[10px] text-coco-mid font-semibold">Mode: <strong><?= strtoupper($chaosConfig['cocoexpress_courier'] ?? 'normal') ?></strong></span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs bg-gray-50 p-3.5 rounded-xl mb-4">
                <div><span class="text-coco-mid">Auth:</span> <strong>Bearer API Key + HMAC-SHA256</strong></div>
                <div><span class="text-coco-mid">Fallback:</span> <strong>Cached <code>tracking_history</code> on 503</strong></div>
                <div><span class="text-coco-mid">Idempotency:</span> <strong>Waybill Deduplication</strong></div>
                <div><span class="text-coco-mid">Middleware:</span> <strong><code>ApiKeyAuthFilter</code> &amp; <code>WebhookSignatureFilter</code></strong></div>
            </div>

            <form action="<?= site_url('admin/integrations/chaos') ?>" method="POST" class="flex flex-wrap items-center gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="service" value="cocoexpress_courier">
                <span class="text-xs font-bold text-coco-mid mr-1">Chaos Mode:</span>
                <?php foreach ([
                    'normal'             => ['label' => 'Normal (Healthy)', 'color' => 'bg-green-600 text-white'],
                    'timeout'            => ['label' => 'Simulate Timeout (504)', 'color' => 'bg-amber-500 text-white'],
                    'http_503'           => ['label' => 'Simulate 503 Outage', 'color' => 'bg-red-600 text-white'],
                    'malformed_response' => ['label' => 'Malformed JSON (502)', 'color' => 'bg-purple-600 text-white'],
                ] as $mKey => $mCfg):
                    $active = ($chaosConfig['cocoexpress_courier'] ?? 'normal') === $mKey;
                ?>
                <button type="submit" name="mode" value="<?= $mKey ?>"
                        class="text-[11px] font-bold px-3 py-1.5 rounded-lg transition-all <?= $active ? $mCfg['color'] . ' shadow-sm ring-2 ring-offset-1 ring-gray-300' : 'bg-gray-100 text-coco-mid hover:bg-gray-200' ?>">
                    <?= $mCfg['label'] ?>
                </button>
                <?php endforeach; ?>
            </form>
        </div>
    </div>

    <!-- Live Defense Failure-Injection & Webhook Simulator Bar -->
    <div class="bg-white rounded-2xl border-2 border-coco-orange/30 shadow-sm p-6 mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
            <div>
                <h2 class="font-display font-black text-xl text-coco-brown flex items-center gap-2">
                    <i class="fas fa-flask text-coco-orange"></i>
                    Live Defense Failure-Injection &amp; Webhook Simulator
                </h2>
                <p class="text-xs text-coco-mid mt-0.5">
                    Execute any required failure scenario during the 25-minute live defense and inspect the measured recovery in the telemetry table below.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Demo 1: Payment Timeout -->
            <form action="<?= site_url('admin/integrations/demo') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="scenario" value="payment_timeout">
                <button type="submit" class="w-full h-full text-left p-4 rounded-xl border border-amber-200 bg-amber-50/70 hover:bg-amber-100 transition-colors">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-black uppercase text-amber-800 bg-amber-200/70 px-2 py-0.5 rounded">Demo 1 · Timeout</span>
                        <i class="fas fa-clock text-amber-600 text-xs"></i>
                    </div>
                    <div class="font-bold text-xs text-coco-brown">Payment Gateway Timeout</div>
                    <div class="text-[11px] text-coco-mid mt-1">Triggers 504 timeout + 2x exponential backoff retries with Idempotency-Key.</div>
                </button>
            </form>

            <!-- Demo 2: Malformed Webhook -->
            <form action="<?= site_url('admin/integrations/demo') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="scenario" value="webhook_malformed">
                <button type="submit" class="w-full h-full text-left p-4 rounded-xl border border-purple-200 bg-purple-50/70 hover:bg-purple-100 transition-colors">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-black uppercase text-purple-800 bg-purple-200/70 px-2 py-0.5 rounded">Demo 2 · Schema</span>
                        <i class="fas fa-bug text-purple-600 text-xs"></i>
                    </div>
                    <div class="font-bold text-xs text-coco-brown">Malformed Webhook Data</div>
                    <div class="text-[11px] text-coco-mid mt-1">Sends invalid negative amount &amp; missing reference; rejected with HTTP 422.</div>
                </button>
            </form>

            <!-- Demo 3: Invalid HMAC Signature -->
            <form action="<?= site_url('admin/integrations/demo') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="scenario" value="webhook_bad_hmac">
                <button type="submit" class="w-full h-full text-left p-4 rounded-xl border border-red-200 bg-red-50/70 hover:bg-red-100 transition-colors">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-black uppercase text-red-800 bg-red-200/70 px-2 py-0.5 rounded">Demo 3 · Security</span>
                        <i class="fas fa-shield-alt text-red-600 text-xs"></i>
                    </div>
                    <div class="font-bold text-xs text-coco-brown">Forged HMAC Signature</div>
                    <div class="text-[11px] text-coco-mid mt-1">Blocked by WebhookSignatureFilter middleware with HTTP 401 Unauthorized.</div>
                </button>
            </form>

            <!-- Demo 4: Courier 503 Outage & Fallback -->
            <form action="<?= site_url('admin/integrations/demo') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="scenario" value="courier_outage_fallback">
                <button type="submit" class="w-full h-full text-left p-4 rounded-xl border border-blue-200 bg-blue-50/70 hover:bg-blue-100 transition-colors">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-black uppercase text-blue-800 bg-blue-200/70 px-2 py-0.5 rounded">Demo 4 · Fallback</span>
                        <i class="fas fa-life-ring text-blue-600 text-xs"></i>
                    </div>
                    <div class="font-bold text-xs text-coco-brown">Courier 503 Graceful Fallback</div>
                    <div class="text-[11px] text-coco-mid mt-1">Courier returns 503; customer still sees cached tracking checkpoints cleanly.</div>
                </button>
            </form>

            <!-- Demo 5: Advance Courier Tracking via Signed Webhook -->
            <form action="<?= site_url('admin/integrations/demo') ?>" method="POST" class="flex flex-col justify-between p-4 rounded-xl border border-green-200 bg-green-50/70">
                <?= csrf_field() ?>
                <input type="hidden" name="scenario" value="courier_webhook_advance">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-black uppercase text-green-800 bg-green-200/70 px-2 py-0.5 rounded">Live Webhook Push</span>
                        <i class="fas fa-shipping-fast text-green-600 text-xs"></i>
                    </div>
                    <div class="font-bold text-xs text-coco-brown mb-1">Push Courier Milestone</div>
                    <select name="order_id" class="w-full text-[11px] border border-green-300 rounded-lg px-2 py-1 bg-white mb-2">
                        <?php foreach ($orders as $ord): ?>
                        <option value="<?= $ord['id'] ?>">
                            <?= esc($ord['order_number'] ?? ('#' . $ord['id'])) ?> (<?= esc($ord['status']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="w-full bg-coco-green hover:bg-coco-dark text-white font-bold text-[11px] py-1.5 rounded-lg transition-colors">
                    Dispatch Signed Webhook
                </button>
            </form>
        </div>
    </div>

    <!-- Live Integration Telemetry Log Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-display font-bold text-lg text-coco-brown">Integration Telemetry &amp; Audit Log (<code>integration_logs</code>)</h3>
                <p class="text-xs text-coco-mid">Every outbound API request and inbound webhook with correlation ID, latency, retry count, and circuit state.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 text-[10px] uppercase tracking-wider text-coco-mid border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left">Time</th>
                        <th class="px-4 py-3 text-left">Correlation ID</th>
                        <th class="px-4 py-3 text-left">Service / Direction</th>
                        <th class="px-4 py-3 text-left">Endpoint</th>
                        <th class="px-4 py-3 text-left">HTTP</th>
                        <th class="px-4 py-3 text-left">Latency</th>
                        <th class="px-4 py-3 text-left">Retries</th>
                        <th class="px-4 py-3 text-left">Circuit</th>
                        <th class="px-4 py-3 text-left">Outcome / Error</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="9" class="py-10 text-center text-coco-mid">No integration events recorded yet. Trigger a checkout, courier booking, or failure demo above!</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($logs as $log):
                        $code = (int) ($log['status_code'] ?? 0);
                        $ok   = $code >= 200 && $code < 300;
                    ?>
                    <tr class="hover:bg-gray-50/80">
                        <td class="px-4 py-3 whitespace-nowrap text-coco-mid"><?= esc($log['created_at'] ?? '') ?></td>
                        <td class="px-4 py-3 font-mono text-[11px] text-coco-brown"><?= esc($log['correlation_id'] ?? '—') ?></td>
                        <td class="px-4 py-3">
                            <span class="font-bold text-coco-brown"><?= esc($log['service_name']) ?></span>
                            <span class="block text-[10px] text-coco-mid"><?= esc($log['direction']) ?></span>
                        </td>
                        <td class="px-4 py-3 font-mono text-[11px]"><span class="font-bold"><?= esc($log['http_method']) ?></span> <?= esc($log['endpoint']) ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded font-black text-[10px] <?= $ok ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= $code ?: 'ERR' ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono whitespace-nowrap"><?= number_format((float) ($log['latency_ms'] ?? 0), 2) ?> ms</td>
                        <td class="px-4 py-3 font-bold <?= ((int)$log['retry_count']) > 0 ? 'text-amber-600' : 'text-coco-mid' ?>">
                            <?= (int) ($log['retry_count'] ?? 0) ?>x
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-[10px] font-bold <?= ($log['circuit_state'] ?? 'CLOSED') === 'CLOSED' ? 'text-green-600' : 'text-red-600' ?>">
                                <?= esc($log['circuit_state'] ?? 'CLOSED') ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 max-w-xs truncate" title="<?= esc($log['error_message'] ?: $log['response_payload']) ?>">
                            <?php if (!empty($log['error_message'])): ?>
                                <span class="text-red-600 font-semibold"><?= esc($log['error_message']) ?></span>
                            <?php else: ?>
                                <span class="text-coco-mid font-mono text-[10px]"><?= esc(substr((string) $log['response_payload'], 0, 80)) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>
</body>
</html>
