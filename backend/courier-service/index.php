<?php

/**
 * CocoExpress Eco-Logistics Microservice (Standalone System 3)
 * Own SQLite database: data/courier.db
 * Exposes REST API v1:
 *  - GET  /api/v1/health
 *  - POST /api/v1/shipments
 *  - GET  /api/v1/shipments/{tracking_number}
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Service-Name: cocoexpress-courier-microservice');
header('X-API-Version: 2026-11-01');

$correlationId = $_SERVER['HTTP_X_CORRELATION_ID'] ?? ('cr-svc-' . bin2hex(random_bytes(5)));
header('X-Correlation-ID: ' . $correlationId);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Initialize independent SQLite3 database for CocoExpress Courier Microservice
$dbDir = __DIR__ . '/data';
if (!is_dir($dbDir)) {
    @mkdir($dbDir, 0777, true);
}
$pdo = new PDO('sqlite:' . $dbDir . '/courier.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("
    CREATE TABLE IF NOT EXISTS shipments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tracking_number TEXT UNIQUE NOT NULL,
        order_number TEXT NOT NULL,
        idempotency_key TEXT UNIQUE,
        courier_code TEXT DEFAULT 'cocoexpress',
        status TEXT DEFAULT 'in_transit',
        recipient_json TEXT,
        checkpoints_json TEXT,
        estimated_delivery TEXT,
        webhook_url TEXT,
        created_at TEXT,
        updated_at TEXT
    );
");

if ($method === 'GET' && ($uri === '/' || $uri === '/api/v1/health')) {
    $count = (int) $pdo->query("SELECT COUNT(*) FROM shipments")->fetchColumn();
    echo json_encode([
        'status'          => 'healthy',
        'service'         => 'cocoexpress-courier-microservice',
        'version'         => '1.0.0',
        'database'        => 'sqlite:courier.db (isolated)',
        'total_shipments' => $count,
        'timestamp'       => date('c'),
    ]);
    exit;
}

// Verify API Key on protected endpoints
$expectedKey = getenv('COURIER_API_KEY') ?: 'ck_live_cocoexpress_2026_secret_token';
$providedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (empty($providedKey) && !empty($_SERVER['HTTP_AUTHORIZATION']) && str_starts_with($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ')) {
    $providedKey = trim(substr($_SERVER['HTTP_AUTHORIZATION'], 7));
}

if ($providedKey !== $expectedKey) {
    http_response_code(401);
    echo json_encode([
        'error' => [
            'code'    => 'UNAUTHORIZED',
            'message' => 'Invalid or missing Courier Service API Key.',
        ],
    ]);
    exit;
}

$rawBody = file_get_contents('php://input') ?: '';
$payload = $rawBody !== '' ? json_decode($rawBody, true) : [];

// POST /api/v1/shipments — Book a new shipment (Idempotent)
if ($method === 'POST' && $uri === '/api/v1/shipments') {
    if (!is_array($payload) || empty($payload['order_number']) || empty($payload['recipient']['name'])) {
        http_response_code(422);
        echo json_encode([
            'error' => [
                'code'    => 'VALIDATION_ERROR',
                'message' => 'Missing required fields: order_number and recipient.name',
            ],
        ]);
        exit;
    }

    $idempotencyKey = $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? ('idem-' . $payload['order_number']);

    $stmt = $pdo->prepare("SELECT * FROM shipments WHERE idempotency_key = :idem OR order_number = :ord LIMIT 1");
    $stmt->execute([':idem' => $idempotencyKey, ':ord' => $payload['order_number']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        http_response_code(200);
        echo json_encode([
            'data' => [
                'tracking_number'    => $existing['tracking_number'],
                'order_number'       => $existing['order_number'],
                'courier_code'       => $existing['courier_code'],
                'courier_name'       => 'CocoExpress Eco-Logistics',
                'status'             => $existing['status'],
                'estimated_delivery' => $existing['estimated_delivery'],
                'recipient'          => json_decode($existing['recipient_json'], true),
                'checkpoints'        => json_decode($existing['checkpoints_json'], true),
                'updated_at'         => $existing['updated_at'],
            ],
            'idempotent_replay' => true,
        ]);
        exit;
    }

    $trackingNumber = 'COCO-TRK-' . date('Y') . '-' . strtoupper(substr(md5($payload['order_number'] . $idempotencyKey), 0, 6));
    $now            = date('Y-m-d H:i:s');
    $eta            = date('Y-m-d', strtotime('+3 days'));
    $checkpoints    = [
        [
            'status'      => 'pickup_scheduled',
            'location'    => 'COCOIR Quezon City Hub',
            'description' => 'Shipment booked and waybill generated via CocoExpress API v1.',
            'timestamp'   => date('Y-m-d H:i:s', strtotime('-5 minutes')),
        ],
        [
            'status'      => 'in_transit',
            'location'    => 'Metro Manila Eco-Sorting Facility',
            'description' => 'Package picked up and in transit to destination city.',
            'timestamp'   => $now,
        ],
    ];

    $ins = $pdo->prepare("
        INSERT INTO shipments (tracking_number, order_number, idempotency_key, courier_code, status, recipient_json, checkpoints_json, estimated_delivery, webhook_url, created_at, updated_at)
        VALUES (:trk, :ord, :idem, 'cocoexpress', 'in_transit', :rec, :chk, :eta, :wh, :now, :now)
    ");
    $ins->execute([
        ':trk'  => $trackingNumber,
        ':ord'  => $payload['order_number'],
        ':idem' => $idempotencyKey,
        ':rec'  => json_encode($payload['recipient']),
        ':chk'  => json_encode($checkpoints),
        ':eta'  => $eta,
        ':wh'   => $payload['webhook_url'] ?? '',
        ':now'  => $now,
    ]);

    http_response_code(201);
    echo json_encode([
        'data' => [
            'tracking_number'    => $trackingNumber,
            'order_number'       => $payload['order_number'],
            'courier_code'       => 'cocoexpress',
            'courier_name'       => 'CocoExpress Eco-Logistics',
            'status'             => 'in_transit',
            'estimated_delivery' => $eta,
            'recipient'          => $payload['recipient'],
            'checkpoints'        => $checkpoints,
            'updated_at'         => $now,
        ],
    ]);
    exit;
}

// GET /api/v1/shipments/{tracking_number}
if ($method === 'GET' && preg_match('#^/api/v1/shipments/([A-Za-z0-9\-]+)$#', $uri, $m)) {
    $trk  = $m[1];
    $stmt = $pdo->prepare("SELECT * FROM shipments WHERE tracking_number = :trk LIMIT 1");
    $stmt->execute([':trk' => $trk]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode([
            'error' => [
                'code'    => 'NOT_FOUND',
                'message' => "Tracking number {$trk} not found in CocoExpress.",
            ],
        ]);
        exit;
    }

    echo json_encode([
        'data' => [
            'tracking_number'    => $row['tracking_number'],
            'order_number'       => $row['order_number'],
            'courier_code'       => $row['courier_code'],
            'courier_name'       => 'CocoExpress Eco-Logistics',
            'status'             => $row['status'],
            'estimated_delivery' => $row['estimated_delivery'],
            'recipient'          => json_decode($row['recipient_json'], true),
            'checkpoints'        => json_decode($row['checkpoints_json'], true),
            'updated_at'         => $row['updated_at'],
        ],
    ]);
    exit;
}

http_response_code(404);
echo json_encode(['error' => ['code' => 'ENDPOINT_NOT_FOUND', 'message' => "No route for {$method} {$uri}"]]);
