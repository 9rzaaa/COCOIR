<?php

namespace App\Models;

use CodeIgniter\Model;

class WebhookEventModel extends Model
{
    protected $table      = 'webhook_events';
    protected $primaryKey = 'id';

    protected $useTimestamps = false;

    public function __construct()
    {
        parent::__construct();
        \App\Libraries\IntegrationSchemaGuard::ensure();
    }

    protected $allowedFields = [
        'event_id',
        'provider',
        'event_type',
        'signature_valid',
        'status',
        'payload',
        'processed_at',
        'created_at',
    ];

    public function hasProcessed(string $eventId): bool
    {
        return $this->where('event_id', $eventId)
            ->where('status', 'processed')
            ->countAllResults() > 0;
    }
}
