<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSystemsIntegrationTables extends Migration
{
    public function up()
    {
        // 1. Add integration columns to orders table (safely check existing fields)
        $existingFields = array_map('strtolower', $this->db->getFieldNames('orders'));

        $columnsToAdd = [];
        $candidateColumns = [
            'payment_reference'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'payment_checkout_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'idempotency_key'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'stock_deducted'       => ['type' => 'INT', 'constraint' => 1, 'default' => 0],
            'courier_code'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => 'cocoexpress'],
            'tracking_number'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tracking_status'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'tracking_history'     => ['type' => 'TEXT', 'null' => true],
            'tracking_synced_at'   => ['type' => 'DATETIME', 'null' => true],
        ];

        foreach ($candidateColumns as $colName => $colDef) {
            if (!in_array(strtolower($colName), $existingFields, true)) {
                $this->forge->addColumn('orders', [$colName => $colDef]);
            }
        }

        // 2. Create integration_logs table for telemetry, retry tracking, and security audit
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'correlation_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'service_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'direction' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'outbound', // outbound | inbound_webhook | inbound_api
            ],
            'endpoint' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'http_method' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => 'POST',
            ],
            'status_code' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 0,
            ],
            'latency_ms' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'retry_count' => [
                'type'       => 'INT',
                'constraint' => 3,
                'default'    => 0,
            ],
            'idempotency_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'circuit_state' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'CLOSED',
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'request_payload' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'response_payload' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('service_name');
        $this->forge->addKey('correlation_id');
        $this->forge->createTable('integration_logs', true);

        // 3. Create webhook_events table for webhook deduplication & replay protection
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'event_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'event_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'signature_valid' => [
                'type'       => 'INT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'default'    => 'processed', // processed | ignored_duplicate | rejected_malformed | rejected_signature
            ],
            'payload' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'processed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('event_id');
        $this->forge->createTable('webhook_events', true);
    }

    public function down()
    {
        $this->forge->dropTable('webhook_events', true);
        $this->forge->dropTable('integration_logs', true);

        $existingFields = array_map('strtolower', $this->db->getFieldNames('orders'));
        $cols = [
            'payment_reference',
            'payment_checkout_url',
            'idempotency_key',
            'stock_deducted',
            'courier_code',
            'tracking_number',
            'tracking_status',
            'tracking_history',
            'tracking_synced_at',
        ];
        $toDrop = array_filter($cols, fn($c) => in_array($c, $existingFields, true));
        if (!empty($toDrop)) {
            $this->forge->dropColumn('orders', $toDrop);
        }
    }
}
