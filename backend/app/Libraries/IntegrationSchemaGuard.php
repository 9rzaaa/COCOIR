<?php

namespace App\Libraries;

/**
 * Self-Healing Schema Guard for Systems Integration Tables & Columns
 * Ensures that whether running on MySQL 8.0 or Railway SQLite3 fallback,
 * all required integration columns on `orders` and the `integration_logs`
 * and `webhook_events` tables exist before any query or update runs.
 */
class IntegrationSchemaGuard
{
    private static bool $checked = false;

    public static function ensure(): void
    {
        if (self::$checked) {
            return;
        }
        self::$checked = true;

        try {
            $db    = \Config\Database::connect();
            $forge = \Config\Database::forge();

            if ($db->tableExists('orders')) {
                $existing = array_map('strtolower', $db->getFieldNames('orders'));
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

                foreach ($candidateColumns as $col => $def) {
                    if (!in_array(strtolower($col), $existing, true)) {
                        $forge->addColumn('orders', [$col => $def]);
                    }
                }
            }

            if (!$db->tableExists('integration_logs')) {
                $forge->addField([
                    'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                    'correlation_id'   => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                    'service_name'     => ['type' => 'VARCHAR', 'constraint' => 50],
                    'direction'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'outbound'],
                    'endpoint'         => ['type' => 'VARCHAR', 'constraint' => 255],
                    'http_method'      => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'POST'],
                    'status_code'      => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
                    'latency_ms'       => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
                    'retry_count'      => ['type' => 'INT', 'constraint' => 3, 'default' => 0],
                    'idempotency_key'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                    'circuit_state'    => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'CLOSED'],
                    'error_message'    => ['type' => 'TEXT', 'null' => true],
                    'request_payload'  => ['type' => 'TEXT', 'null' => true],
                    'response_payload' => ['type' => 'TEXT', 'null' => true],
                    'created_at'       => ['type' => 'DATETIME', 'null' => true],
                ]);
                $forge->addKey('id', true);
                $forge->createTable('integration_logs', true);
            }

            if (!$db->tableExists('webhook_events')) {
                $forge->addField([
                    'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                    'event_id'        => ['type' => 'VARCHAR', 'constraint' => 100],
                    'provider'        => ['type' => 'VARCHAR', 'constraint' => 50],
                    'event_type'      => ['type' => 'VARCHAR', 'constraint' => 100],
                    'signature_valid' => ['type' => 'INT', 'constraint' => 1, 'default' => 0],
                    'status'          => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'processed'],
                    'payload'         => ['type' => 'TEXT', 'null' => true],
                    'processed_at'    => ['type' => 'DATETIME', 'null' => true],
                    'created_at'      => ['type' => 'DATETIME', 'null' => true],
                ]);
                $forge->addKey('id', true);
                $forge->createTable('webhook_events', true);
            }
        } catch (\Throwable $e) {
            if (function_exists('log_message')) {
                log_message('error', 'IntegrationSchemaGuard error: ' . $e->getMessage());
            }
        }
    }
}
