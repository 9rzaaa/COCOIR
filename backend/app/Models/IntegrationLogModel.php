<?php

namespace App\Models;

use CodeIgniter\Model;

class IntegrationLogModel extends Model
{
    protected $table      = 'integration_logs';
    protected $primaryKey = 'id';

    protected $useTimestamps = false;

    protected $allowedFields = [
        'correlation_id',
        'service_name',
        'direction',
        'endpoint',
        'http_method',
        'status_code',
        'latency_ms',
        'retry_count',
        'idempotency_key',
        'circuit_state',
        'error_message',
        'request_payload',
        'response_payload',
        'created_at',
    ];

    public function record(array $entry): int|false
    {
        $entry['created_at'] = $entry['created_at'] ?? date('Y-m-d H:i:s');
        try {
            return $this->insert($entry);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to write integration_log: ' . $e->getMessage());
            return false;
        }
    }
}
