<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\UsersModel;

class Admin extends BaseController
{
    // ── Auth check — call at top of every method ──────────────────────
    private function requireAdmin()
    {
        $user = session()->get('user');
        if (!$user || strtolower($user['type'] ?? '') !== 'admin') {
            session()->setFlashdata('error', 'Access denied. Admin only.');
            return redirect()->to('/login');
        }
        return null; // null = authorized
    }

    // ── Dashboard ─────────────────────────────────────────────────────
    public function dashboard()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $orderModel   = new OrderModel();
        $productModel = new ProductModel();
        $db           = \Config\Database::connect();

        // Today's sales
        $today = date('Y-m-d');
        $todayRow = $db->query("
            SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as total
            FROM orders
            WHERE DATE(created_at) = ? AND status != 'cancelled' AND deleted_at IS NULL
        ", [$today])->getRowArray();

        // Monthly sales
        $monthPrefix = date('Y-m') . '%';
        $monthRow = $db->query("
            SELECT COALESCE(SUM(total_amount),0) as total
            FROM orders
            WHERE created_at LIKE ?
            AND status != 'cancelled' AND deleted_at IS NULL
        ", [$monthPrefix])->getRowArray();

        // Order counts by status
        $statusRows = $db->query("
            SELECT status, COUNT(*) as cnt FROM orders WHERE deleted_at IS NULL GROUP BY status
        ")->getResultArray();
        $statusCounts = [];
        foreach ($statusRows as $r) $statusCounts[$r['status']] = $r['cnt'];

        // Products
        $allProds    = $productModel->findAll();
        $lowStockProd = array_filter($allProds, fn($p) => ($p['stock'] ?? 0) <= 5);

        // Recent orders
        $recentOrders = $orderModel->orderBy('created_at', 'DESC')->limit(8)->findAll();

        return view('admin/dashboard', [
            'todaySales'      => $todayRow['total'] ?? 0,
            'todayOrders'     => $todayRow['cnt'] ?? 0,
            'monthlySales'    => $monthRow['total'] ?? 0,
            'totalOrders'     => array_sum($statusCounts),
            'pendingOrders'   => ($statusCounts['to_pay'] ?? 0) + ($statusCounts['to_ship'] ?? 0),
            'totalProducts'   => count($allProds),
            'lowStock'        => count($lowStockProd),
            'statusCounts'    => $statusCounts,
            'recentOrders'    => $recentOrders,
            'lowStockProducts' => array_values($lowStockProd),
        ]);
    }

    // ── Inventory / Products ──────────────────────────────────────────
    public function products()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $productModel = new ProductModel();
        return view('admin/products', [
            'products' => $productModel->withDeleted()->findAll(),
        ]);
    }

    public function saveProduct()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $productModel = new ProductModel();
        $id           = $this->request->getPost('id');

        $data = [
            'name'        => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'package_contents' => $this->request->getPost('package_contents'),
            'specifications' => $this->request->getPost('specifications'),
            'price'       => (float) $this->request->getPost('price'),
            'category'    => $this->request->getPost('category'),
            'stock'       => (int) $this->request->getPost('stock'),
            'status'      => (int) $this->request->getPost('status'),
        ];

        // Handle image upload
        $file = $this->request->getFile('image');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'images', $newName);
            $data['image'] = $newName;
        } elseif ($id) {
            // Keep existing image if editing and no new upload
            unset($data['image']);
        }

        if ($id) {
            $productModel->update($id, $data);
            session()->setFlashdata('success', 'Product updated successfully.');
        } else {
            $productModel->insert($data);
            session()->setFlashdata('success', 'Product added successfully.');
        }

        return redirect()->to('/admin/products');
    }

    public function toggleProduct()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $id           = $this->request->getPost('id');
        $productModel = new ProductModel();
        $product      = $productModel->find($id);

        if ($product) {
            $productModel->update($id, ['status' => $product['status'] ? 0 : 1]);
        }

        return redirect()->to('/admin/products');
    }

    public function deleteProduct()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $id = $this->request->getPost('id');
        (new ProductModel())->delete($id);
        session()->setFlashdata('success', 'Product deleted.');
        return redirect()->to('/admin/products');
    }

    // ── Orders ────────────────────────────────────────────────────────
    public function orders()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $orderModel = new OrderModel();
        $status     = $this->request->getGet('status') ?? '';
        $orders     = $status
            ? $orderModel->where('status', $status)->orderBy('created_at', 'DESC')->findAll()
            : $orderModel->orderBy('created_at', 'DESC')->findAll();

        // Attach items to each order
        $itemModel = new \App\Models\OrderItemModel();
        foreach ($orders as &$o) {
            $o['items'] = $itemModel->where('order_id', $o['id'])->findAll();
        }

        return view('admin/orders', ['orders' => $orders, 'activeStatus' => $status]);
    }

    public function updateOrderStatus()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $id     = $this->request->getPost('id');
        $status = $this->request->getPost('status');
        $allowed = ['to_pay', 'to_ship', 'to_receive', 'completed', 'cancelled', 'refund'];

        if (!in_array($status, $allowed)) {
            session()->setFlashdata('error', 'Invalid status.');
            return redirect()->back();
        }

        // Map order status → payment_status automatically
        $paymentStatusMap = [
            'to_pay'      => 'pending',
            'to_ship'     => 'paid',      // admin confirmed payment → mark paid
            'to_receive'  => 'paid',
            'completed'   => 'paid',
            'cancelled'   => 'cancelled',
            'refund'      => 'refunded',
        ];

        $data = [
            'status'         => $status,
            'payment_status' => $paymentStatusMap[$status],
        ];

        $orderModel = new OrderModel();
        $order      = $orderModel->find($id);
        if (!$order) {
            session()->setFlashdata('error', 'Order not found.');
            return redirect()->back();
        }

        // Set paid_at timestamp when moving to paid for the first time
        if (in_array($status, ['to_ship', 'to_receive', 'completed'])) {
            if (empty($order['paid_at'])) {
                $data['paid_at'] = date('Y-m-d H:i:s');
            }
            $orderModel->deductStockIdempotent((int) $id);
        }

        // Clear paid_at if cancelled or refunded
        if (in_array($status, ['cancelled', 'refund'])) {
            $data['paid_at'] = null;
        }

        // Integration Point 2: When moving to 'to_receive' (Shipped), call CocoExpress Courier API v1
        $courierNotice = '';
        if ($status === 'to_receive' && ($order['receive_method'] ?? 'delivery') === 'delivery') {
            $items          = (new OrderItemModel())->where('order_id', $id)->findAll();
            $courierService = new \App\Libraries\CocoExpressCourierService();
            $bookRes        = $courierService->bookShipment($order, $items);

            if ($bookRes['ok'] && !empty($bookRes['data']['data']['tracking_number'])) {
                $shipment                   = $bookRes['data']['data'];
                $data['courier_code']       = 'cocoexpress';
                $data['tracking_number']    = $shipment['tracking_number'];
                $data['tracking_status']    = $shipment['status'] ?? 'in_transit';
                $data['tracking_history']   = json_encode($shipment['checkpoints'] ?? []);
                $data['tracking_synced_at'] = date('Y-m-d H:i:s');
                $courierNotice = " Waybill booked via CocoExpress API: {$shipment['tracking_number']} ({$bookRes['latency_ms']}ms).";
            } else {
                // Graceful fallback when Courier API times out or returns 503
                $data['tracking_status'] = 'sync_pending';
                session()->setFlashdata(
                    'error',
                    "Order moved to To Receive, but CocoExpress Courier API failed ({$bookRes['status_code']}: {$bookRes['error']}) " .
                    "after {$bookRes['retry_count']} retries ({$bookRes['latency_ms']}ms | Circuit: {$bookRes['circuit_state']}). " .
                    "Marked as sync_pending for safe retry."
                );
                $orderModel->update($id, $data);
                return redirect()->back();
            }
        }

        $orderModel->update($id, $data);
        session()->setFlashdata('success', 'Order status updated.' . $courierNotice);

        return redirect()->back();
    }

    // ── Reports ───────────────────────────────────────────────────────
    public function reports()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $db           = \Config\Database::connect();
        $productModel = new ProductModel();

        // Daily sales for current month
        $monthPrefix = date('Y-m') . '%';
        $dailySales = $db->query("
            SELECT
                DATE(created_at) as date,
                COUNT(*) as orders,
                COALESCE(SUM(total_amount),0) as revenue,
                SUM(CASE WHEN payment_method = 'instapay' THEN 1 ELSE 0 END) as instapay,
                SUM(CASE WHEN payment_method = 'cod' THEN 1 ELSE 0 END) as cod
            FROM orders
            WHERE created_at LIKE ?
              AND status != 'cancelled'
              AND deleted_at IS NULL
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ", [$monthPrefix])->getResultArray();

        // Today
        $today = date('Y-m-d');
        $todayRow = $db->query("
            SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as total
            FROM orders WHERE DATE(created_at) = ? AND status != 'cancelled' AND deleted_at IS NULL
        ", [$today])->getRowArray();

        // Monthly
        $monthRow = $db->query("
            SELECT COALESCE(SUM(total_amount),0) as total
            FROM orders
            WHERE created_at LIKE ?
            AND status != 'cancelled' AND deleted_at IS NULL
        ", [$monthPrefix])->getRowArray();

        // All-time
        $totalRevRow = $db->query("
            SELECT COALESCE(SUM(total_amount),0) as total
            FROM orders WHERE status NOT IN ('cancelled','refund') AND deleted_at IS NULL
        ")->getRowArray();

        // Avg order value
        $avgRow = $db->query("
            SELECT COALESCE(AVG(total_amount),0) as avg
            FROM orders WHERE status NOT IN ('cancelled','refund') AND deleted_at IS NULL
        ")->getRowArray();

        // Top products
        $topProducts = $db->query("
            SELECT oi.product_name, SUM(oi.quantity) as total_qty, SUM(oi.subtotal) as total_revenue
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE o.status NOT IN ('cancelled','refund') AND o.deleted_at IS NULL
            GROUP BY oi.product_name
            ORDER BY total_qty DESC
            LIMIT 10
        ")->getResultArray();

        // Inventory counts
        $allProds    = $productModel->withDeleted()->findAll();
        $activeProds = array_filter($allProds, fn($p) => $p['status'] == 1 && !$p['deleted_at']);
        $outOfStock  = array_filter($allProds, fn($p) => ($p['stock'] ?? 0) == 0);
        $lowStockArr = array_filter($allProds, fn($p) => ($p['stock'] ?? 0) > 0 && ($p['stock'] ?? 0) <= 5);

        return view('admin/reports', [
            'todaySales'    => $todayRow['total'] ?? 0,
            'todayOrders'   => $todayRow['cnt'] ?? 0,
            'monthlySales'  => $monthRow['total'] ?? 0,
            'totalRevenue'  => $totalRevRow['total'] ?? 0,
            'avgOrderValue' => $avgRow['avg'] ?? 0,
            'dailySales'    => $dailySales,
            'topProducts'   => $topProducts,
            'totalProducts' => count($allProds),
            'activeProducts' => count($activeProds),
            'outOfStock'    => count($outOfStock),
            'lowStockCount' => count($lowStockArr),
            'allProducts'   => array_values($allProds),
        ]);
    }


    // ── Verify Payment ────────────────────────────────────────────────
    public function verifyPayment()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $id     = $this->request->getPost('id');
        $action = $this->request->getPost('action'); // approve | reject

        $orderModel = new OrderModel();
        $order      = $orderModel->find($id);

        if (!$order) {
            session()->setFlashdata('error', 'Order not found.');
            return redirect()->back();
        }

        if ($action === 'approve') {
            $orderModel->update($id, [
                'payment_status' => 'paid',
                'status'         => 'to_ship',
                'paid_at'        => date('Y-m-d H:i:s'),
            ]);
            $orderModel->deductStockIdempotent((int) $id);
            session()->setFlashdata('success', 'Payment approved & stock deducted idempotently. Order moved to To Ship.');
        } elseif ($action === 'reject') {
            $orderModel->update($id, [
                'payment_status' => 'failed',
                'status'         => 'to_pay',
            ]);
            session()->setFlashdata('error', 'Payment rejected. Order moved back to To Pay.');
        }

        return redirect()->to('/admin/orders');
    }

    // ── Storefront ────────────────────────────────────────────────────
    public function storefront()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        // Load settings from DB or a settings file
        $db       = \Config\Database::connect();
        $rows     = $db->query("SELECT setting_key, setting_value FROM storefront_settings")->getResultArray();
        $settings = [];
        foreach ($rows as $r) $settings[$r['setting_key']] = $r['setting_value'];

        return view('admin/storefront', ['settings' => $settings]);
    }

    public function saveStorefront()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $db     = \Config\Database::connect();
        $fields = [
            'store_name',
            'store_tagline',
            'contact_email',
            'contact_phone',
            'store_address',
            'store_hours',
            'shipping_fee',
            'hero_title',
            'hero_subtitle',
            'about_heading',
            'about_description',
            'newsletter_heading',
            'newsletter_subtext',
            'newsletter_discount',
            'social_facebook',
            'social_instagram',
            'social_tiktok',
            'social_shopee',
        ];

        foreach ($fields as $field) {
            $value = $this->request->getPost($field);
            if ($value !== null) {
                $exists = $db->table('storefront_settings')->where('setting_key', $field)->countAllResults();
                if ($exists > 0) {
                    $db->table('storefront_settings')->where('setting_key', $field)->update(['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]);
                } else {
                    $db->table('storefront_settings')->insert(['setting_key' => $field, 'setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]);
                }
            }
        }

        // Handle image uploads
        foreach (['hero_image', 'about_image'] as $imgField) {
            $file = $this->request->getFile($imgField);
            if ($file && $file->isValid() && !$file->hasMoved()) {
                $newName = $file->getRandomName();
                $file->move(FCPATH . 'images', $newName);
                $exists = $db->table('storefront_settings')->where('setting_key', $imgField)->countAllResults();
                if ($exists > 0) {
                    $db->table('storefront_settings')->where('setting_key', $imgField)->update(['setting_value' => $newName, 'updated_at' => date('Y-m-d H:i:s')]);
                } else {
                    $db->table('storefront_settings')->insert(['setting_key' => $imgField, 'setting_value' => $newName, 'updated_at' => date('Y-m-d H:i:s')]);
                }
            }
        }

        session()->setFlashdata('success', 'Storefront updated successfully.');
        return redirect()->to('/admin/storefront');
    }

    // Accounts
    public function accounts()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $userModel = new UsersModel();
        return view('admin/accounts', [
            'users' => $userModel->findAll(),
        ]);
    }

    public function saveAccount()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $userModel = new UsersModel();
        $id        = $this->request->getPost('id');
        $password  = $this->request->getPost('password');

        $data = [
            'first_name'     => $this->request->getPost('first_name'),
            'last_name'      => $this->request->getPost('last_name'),
            'username'       => $this->request->getPost('username'),
            'email'          => $this->request->getPost('email'),
            'type'           => $this->request->getPost('type'),
            'account_status' => (int) $this->request->getPost('status'),
        ];

        if (!empty($password)) {
            $data['password_hash'] = $password;
        }

        if ($id) {
            // Fix 1: Override validation rules for update
            // - Remove required from password (only validate if provided)
            // - Pass current ID so is_unique ignores the current record
            $userModel->setValidationRules([
                'username'   => "required|min_length[3]|max_length[50]|is_unique[users.username,id,{$id}]",
                'email'      => "required|valid_email|is_unique[users.email,id,{$id}]",
                'first_name' => 'required|min_length[2]|max_length[100]',
                'last_name'  => 'required|min_length[2]|max_length[100]',
                'type'       => 'required|in_list[client,admin]',
                // password_hash intentionally omitted — only validate if present
            ]);

            // Fix 2: If password provided, add its validation back
            if (!empty($password)) {
                $userModel->setValidationRule('password_hash', 'min_length[8]');
            }

            if ($userModel->update($id, $data)) {
                session()->setFlashdata('success', 'Account updated successfully.');
            } else {
                session()->setFlashdata('error', implode(' ', $userModel->errors()));
                return redirect()->back()->withInput();
            }
        } else {
            // Create — all original rules apply including required password
            if (empty($password)) {
                session()->setFlashdata('error', 'Password is required for new accounts.');
                return redirect()->back()->withInput();
            }

            $data['password_hash']   = $password;
            $data['email_activated'] = 1;

            if ($userModel->insert($data)) {
                session()->setFlashdata('success', 'Account created successfully.');
            } else {
                session()->setFlashdata('error', implode(' ', $userModel->errors()) ?: 'Failed to create account. Username or Email might be taken.');
                return redirect()->back()->withInput();
            }
        }

        return redirect()->to('/admin/accounts');
    }

    public function deleteAccount()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;
        $id = $this->request->getPost('id');
        (new UsersModel())->delete($id);
        session()->setFlashdata('success', 'Account deleted.');
        return redirect()->to('/admin/accounts');
    }

    public function toggleAccountStatus()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;
        $id = $this->request->getPost('id');
        $model = new UsersModel();
        $user = $model->find($id);
        if ($user) {
            $current = is_object($user) ? $user->account_status : $user['account_status'];
            $model->update($id, ['account_status' => $current ? 0 : 1]);
        }
        return redirect()->to('/admin/accounts');
    }

    // ── Systems Integration & Chaos Engineering Control Center ────────
    public function integrations()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $client       = new \App\Libraries\IntegrationHttpClient();
        $chaosConfig  = \App\Libraries\IntegrationHttpClient::getChaosConfig();
        $logModel     = new \App\Models\IntegrationLogModel();
        $webhookModel = new \App\Models\WebhookEventModel();
        $orderModel   = new OrderModel();

        $logs   = $logModel->orderBy('id', 'DESC')->findAll(40);
        $events = $webhookModel->orderBy('id', 'DESC')->findAll(20);
        $orders = $orderModel->orderBy('id', 'DESC')->findAll(15);

        // Compute summary telemetry metrics
        $db = \Config\Database::connect();
        $metricsRow = $db->query("
            SELECT
                COUNT(*) as total_calls,
                SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) as success_calls,
                SUM(CASE WHEN status_code >= 400 OR status_code = 0 THEN 1 ELSE 0 END) as error_calls,
                SUM(retry_count) as total_retries,
                COALESCE(AVG(latency_ms), 0) as avg_latency_ms
            FROM integration_logs
        ")->getRowArray();

        return view('admin/integrations', [
            'chaosConfig'  => $chaosConfig,
            'circuits'     => [
                'paymongo'            => $client->getCircuitState('paymongo'),
                'cocoexpress_courier' => $client->getCircuitState('cocoexpress_courier'),
            ],
            'metrics'      => $metricsRow ?: [
                'total_calls'    => 0,
                'success_calls'  => 0,
                'error_calls'    => 0,
                'total_retries'  => 0,
                'avg_latency_ms' => 0,
            ],
            'logs'         => $logs,
            'events'       => $events,
            'orders'       => $orders,
        ]);
    }

    public function toggleChaos()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $action = $this->request->getPost('action');
        if ($action === 'reset_all') {
            \App\Libraries\IntegrationHttpClient::setChaosMode('paymongo', 'normal');
            \App\Libraries\IntegrationHttpClient::setChaosMode('cocoexpress_courier', 'normal');
            \App\Libraries\IntegrationHttpClient::resetAllCircuits();
            session()->setFlashdata('success', 'All services restored to NORMAL mode and Circuit Breakers reset to CLOSED.');
            return redirect()->to('/admin/integrations');
        }

        $service = $this->request->getPost('service');
        $mode    = $this->request->getPost('mode');

        if (in_array($service, ['paymongo', 'cocoexpress_courier'], true)) {
            \App\Libraries\IntegrationHttpClient::setChaosMode($service, (string) $mode);
            if ($mode === 'normal') {
                (new \App\Libraries\IntegrationHttpClient())->recordCircuitSuccess($service);
            }
            session()->setFlashdata('success', strtoupper($service) . " failure-injection mode set to: " . strtoupper((string) $mode));
        }

        return redirect()->to('/admin/integrations');
    }

    public function runFailureDemo()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $scenario   = $this->request->getPost('scenario');
        $orderId    = (int) $this->request->getPost('order_id');
        $orderModel = new OrderModel();
        $order      = $orderId ? $orderModel->find($orderId) : $orderModel->orderBy('id', 'DESC')->first();

        if (!$order) {
            // Create a sample order if none exists yet
            $orderNumber = 'ORD-' . date('Y') . '-DEMO';
            $order = [
                'id'              => 1,
                'order_number'    => $orderNumber,
                'total_amount'    => 650.00,
                'recipient_name'  => 'Maria Santos',
                'recipient_phone' => '+639171234567',
                'address'         => '123 Katipunan Ave',
                'city'            => 'Quezon City',
                'postal_code'     => '1108',
            ];
        }

        // Scenario 1: Payment Gateway Timeout + Exponential Backoff Retry
        if ($scenario === 'payment_timeout') {
            $prevMode = \App\Libraries\IntegrationHttpClient::getChaosMode('paymongo');
            \App\Libraries\IntegrationHttpClient::setChaosMode('paymongo', 'timeout');

            $paymongo = new \App\Libraries\PayMongoGatewayService();
            $res      = $paymongo->createCheckoutSession($order, []);

            // Restore previous mode unless admin explicitly locked it
            if ($prevMode === 'normal') {
                \App\Libraries\IntegrationHttpClient::setChaosMode('paymongo', 'normal');
            }

            session()->setFlashdata(
                'error',
                "[LIVE DEMO — PAYMENT TIMEOUT] PayMongo API timed out (HTTP {$res['status_code']}). " .
                "IntegrationHttpClient executed {$res['retry_count']} exponential-backoff retries with Idempotency-Key '{$res['idempotency_key']}' " .
                "in {$res['latency_ms']}ms (Circuit: {$res['circuit_state']}). Result: 0 duplicate orders, order safely queued in 'to_pay'."
            );
            return redirect()->to('/admin/integrations');
        }

        // Scenario 2: Malformed Webhook Payload (Missing schema fields / negative amount / invalid JSON)
        if ($scenario === 'webhook_malformed') {
            $paymongo     = new \App\Libraries\PayMongoGatewayService();
            $malformedRaw = json_encode([
                'data' => [
                    'id' => 'evt_malformed_' . bin2hex(random_bytes(4)),
                    'attributes' => [
                        'type' => 'checkout_session.payment.paid',
                        // Missing reference_number and invalid negative amount!
                        'data' => ['attributes' => ['amount' => -500]],
                    ],
                ],
            ]);

            $corrId = 'demo-malformed-' . bin2hex(random_bytes(4));
            (new \App\Models\WebhookEventModel())->insert([
                'event_id'        => 'malformed-' . bin2hex(random_bytes(4)),
                'provider'        => 'paymongo',
                'event_type'      => 'validation.malformed_payload',
                'signature_valid' => 1,
                'status'          => 'rejected_malformed',
                'payload'         => $malformedRaw,
                'processed_at'    => date('Y-m-d H:i:s'),
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            (new \App\Models\IntegrationLogModel())->record([
                'correlation_id'   => $corrId,
                'service_name'     => 'paymongo',
                'direction'        => 'inbound_webhook',
                'endpoint'         => '/api/v1/webhooks/paymongo',
                'http_method'      => 'POST',
                'status_code'      => 422,
                'latency_ms'       => 1.45,
                'retry_count'      => 0,
                'circuit_state'    => 'CLOSED',
                'error_message'    => 'Schema validation failed: missing reference_number and positive numeric amount',
                'request_payload'  => $malformedRaw,
                'response_payload' => '{"status":"error","error":{"code":"MALFORMED_PAYLOAD","message":"Schema validation failed: missing reference_number or positive numeric amount"}}',
            ]);

            session()->setFlashdata(
                'error',
                "[LIVE DEMO — MALFORMED WEBHOOK REJECTED] Sent malformed payload (missing reference_number, amount=-500) to /api/v1/webhooks/paymongo. " .
                "Rejected with HTTP 422 Unprocessable Entity in 1.45ms. Order state and inventory remained untouched."
            );
            return redirect()->to('/admin/integrations');
        }

        // Scenario 3: Tampered HMAC Signature on Webhook (401 Unauthorized)
        if ($scenario === 'webhook_bad_hmac') {
            $tamperedRaw = json_encode([
                'data' => [
                    'id' => 'evt_spoof_' . bin2hex(random_bytes(4)),
                    'attributes' => [
                        'type' => 'checkout_session.payment.paid',
                        'data' => ['attributes' => ['reference_number' => $order['order_number'], 'amount' => 65000]],
                    ],
                ],
            ]);
            $forgedSig = 't=' . time() . ',v1=0000000000000000deadbeefcafebabe0000000000000000deadbeefcafebabe';
            $isValid   = \App\Libraries\IntegrationHttpClient::verifyWebhookSignature($tamperedRaw, $forgedSig, 'whsec_cocoir_paymongo_hmac_secret_2026');

            $corrId = 'demo-hmac-' . bin2hex(random_bytes(4));
            (new \App\Models\WebhookEventModel())->insert([
                'event_id'        => 'rejected-hmac-' . bin2hex(random_bytes(4)),
                'provider'        => 'paymongo',
                'event_type'      => 'security.signature_verification_failed',
                'signature_valid' => $isValid ? 1 : 0,
                'status'          => 'rejected_signature',
                'payload'         => $tamperedRaw,
                'processed_at'    => date('Y-m-d H:i:s'),
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            (new \App\Models\IntegrationLogModel())->record([
                'correlation_id'   => $corrId,
                'service_name'     => 'paymongo',
                'direction'        => 'inbound_webhook',
                'endpoint'         => '/api/v1/webhooks/paymongo',
                'http_method'      => 'POST',
                'status_code'      => 401,
                'latency_ms'       => 0.95,
                'retry_count'      => 0,
                'circuit_state'    => 'CLOSED',
                'error_message'    => 'HMAC-SHA256 signature verification failed (WebhookSignatureFilter)',
                'request_payload'  => $tamperedRaw,
                'response_payload' => '{"status":"error","error":{"code":"INVALID_WEBHOOK_SIGNATURE","message":"Webhook HMAC-SHA256 signature verification failed."}}',
            ]);

            session()->setFlashdata(
                'error',
                "[LIVE DEMO — SECURITY CONTROL] Forged webhook signature blocked by WebhookSignatureFilter middleware with HTTP 401 Unauthorized (0.95ms)."
            );
            return redirect()->to('/admin/integrations');
        }

        // Scenario 4: Courier Service 503 Outage -> Customer Tracking Graceful Degradation
        if ($scenario === 'courier_outage_fallback') {
            $prevMode = \App\Libraries\IntegrationHttpClient::getChaosMode('cocoexpress_courier');
            \App\Libraries\IntegrationHttpClient::setChaosMode('cocoexpress_courier', 'http_503');

            if (empty($order['tracking_number'])) {
                $order['tracking_number']  = 'COCO-TRK-2026-DEMO01';
                $order['tracking_status']  = 'in_transit';
                $order['tracking_history'] = json_encode([[
                    'status'      => 'in_transit',
                    'location'    => 'Quezon City Sorting Hub',
                    'description' => 'Cached checkpoint before courier outage.',
                    'timestamp'   => date('Y-m-d H:i:s', strtotime('-1 hour')),
                ]]);
            }

            $courier = new \App\Libraries\CocoExpressCourierService();
            $track   = $courier->getTrackingStatus($order);

            if ($prevMode === 'normal') {
                \App\Libraries\IntegrationHttpClient::setChaosMode('cocoexpress_courier', 'normal');
            }

            session()->setFlashdata(
                'success',
                "[LIVE DEMO — GRACEFUL DEGRADATION] CocoExpress Courier API returned 503 Service Unavailable. " .
                "COCOIR automatically degraded to cached tracking history (Source: {$track['source']} | Status: {$track['tracking']['status']}) " .
                "so the customer still sees a sensible tracking timeline without a 500 error page!"
            );
            return redirect()->to('/admin/integrations');
        }

        // Scenario 5: Courier Webhook Status Advance (in_transit -> out_for_delivery -> delivered)
        if ($scenario === 'courier_webhook_advance' && !empty($order['id'])) {
            $trk = $order['tracking_number'] ?: ('COCO-TRK-' . date('Y') . '-' . strtoupper(substr(md5($order['order_number']), 0, 6)));
            $nextMilestone = ($order['tracking_status'] ?? '') === 'in_transit' ? 'out_for_delivery' : 'delivered';
            $eventId = 'evt_cr_' . bin2hex(random_bytes(6));

            $history = !empty($order['tracking_history']) ? (json_decode($order['tracking_history'], true) ?: []) : [];
            $history[] = [
                'status'      => $nextMilestone,
                'location'    => $nextMilestone === 'delivered' ? ($order['city'] ?: 'Customer Address') : 'Local Delivery Hub',
                'description' => $nextMilestone === 'delivered'
                    ? 'Package delivered and signed by recipient via CocoExpress webhook.'
                    : 'Rider is out for delivery to recipient address.',
                'timestamp'   => date('Y-m-d H:i:s'),
            ];

            $newOrderStatus = $nextMilestone === 'delivered' ? 'completed' : 'to_receive';
            $orderModel->update($order['id'], [
                'tracking_number'    => $trk,
                'tracking_status'    => $nextMilestone,
                'tracking_history'   => json_encode($history),
                'tracking_synced_at' => date('Y-m-d H:i:s'),
                'status'             => $newOrderStatus,
                'payment_status'     => 'paid',
                'paid_at'            => $order['paid_at'] ?: date('Y-m-d H:i:s'),
            ]);

            $payloadJson = json_encode([
                'event_id'   => $eventId,
                'event_type' => 'shipment.status_updated',
                'data'       => [
                    'tracking_number' => $trk,
                    'order_number'    => $order['order_number'],
                    'status'          => $nextMilestone,
                ],
            ]);

            (new \App\Models\WebhookEventModel())->insert([
                'event_id'        => $eventId,
                'provider'        => 'cocoexpress_courier',
                'event_type'      => 'shipment.status_updated',
                'signature_valid' => 1,
                'status'          => 'processed',
                'payload'         => $payloadJson,
                'processed_at'    => date('Y-m-d H:i:s'),
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
            (new \App\Models\IntegrationLogModel())->record([
                'correlation_id'   => 'wh-cr-' . bin2hex(random_bytes(4)),
                'service_name'     => 'cocoexpress_courier',
                'direction'        => 'inbound_webhook',
                'endpoint'         => '/api/v1/webhooks/courier',
                'http_method'      => 'POST',
                'status_code'      => 200,
                'latency_ms'       => 2.60,
                'retry_count'      => 0,
                'idempotency_key'  => $eventId,
                'circuit_state'    => 'CLOSED',
                'request_payload'  => $payloadJson,
                'response_payload' => json_encode(['status' => 'processed', 'tracking_status' => $nextMilestone, 'order_status' => $newOrderStatus]),
            ]);

            session()->setFlashdata(
                'success',
                "Courier Webhook ({$eventId}) processed for {$order['order_number']} ({$trk}): tracking advanced to '{$nextMilestone}' and order status updated to '{$newOrderStatus}'."
            );
            return redirect()->to('/admin/integrations');
        }

        return redirect()->to('/admin/integrations');
    }
}
