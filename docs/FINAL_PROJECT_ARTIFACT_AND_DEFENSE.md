# IT0129 — SYSTEMS INTEGRATION AND ARCHITECTURE 2
## FINAL PROJECT ARTIFACT AND DEFENSE (`[FE-FINAL]`)
**System**: **COCOIR** — Eco-Friendly Coconut Coir E-Commerce, Payment & Logistics Integration Platform

---

## PART A — WORKING INTEGRATED SYSTEM (Build & Run Instructions)

### 1. System Topology (3 Integrated Systems)
1. **COCOIR Core Platform (`backend/`)**: CodeIgniter 4 (`PHP 8.3/8.4`), MySQL 8.0 / SQLite3, Customer Storefront, Inventory Management, Order Processing, and Admin Integration & Chaos Control Center.
2. **PayMongo Payment Gateway Service (`PayMongoGatewayService` + Sandbox API v1)**: External/sandbox payment processor handling QR Ph, GCash, and Maya checkout sessions (`POST /v1/checkout_sessions`) and dispatching HMAC-SHA256 signed payment confirmation webhooks (`POST /api/v1/webhooks/paymongo`).
3. **CocoExpress Courier & Logistics Microservice (`courier-service/`)**: Standalone Dockerized microservice running on port `8095` with its own isolated SQLite database (`courier.db`), exposing REST API v1 (`POST /api/v1/shipments`, `GET /api/v1/shipments/{tracking_number}`) and dispatching HMAC-SHA256 signed shipment milestone webhooks (`POST /api/v1/webhooks/courier`).

### 2. How to Run the Integrated System
#### Option A: Docker Compose (Full Multi-Container Stack)
```bash
docker compose up -d --build
docker compose exec php php spark migrate --all
docker compose exec php php spark db:seed DatabaseSeeder
```
- **COCOIR Storefront**: `http://localhost:8090`
- **COCOIR Admin Dashboard**: `http://localhost:8090/admin/dashboard`
- **Admin Integration & Chaos Control Center**: `http://localhost:8090/admin/integrations`
- **API v1 Health Check**: `http://localhost:8090/api/v1/health`
- **CocoExpress Courier Microservice**: `http://localhost:8095/api/v1/health`

#### Option B: Run Automated Validation & Benchmark Suite (Part C Evidence)
```bash
php backend/tests/integration_validation_suite.php
```

---

## PART B — INTEGRATION DOCUMENTATION

### 1. End-to-End Business Process Across Systems
1. **Customer Checkout (`COCOIR -> PayMongo API v1`)**: Customer places an order on COCOIR (`/checkout/place`). COCOIR generates a deterministic `Idempotency-Key` and invokes `PayMongoGatewayService::createCheckoutSession()` via the reusable `IntegrationHttpClient`.
2. **Asynchronous Payment Confirmation (`PayMongo -> COCOIR Webhook`)**: PayMongo sends an HMAC-SHA256 signed `checkout_session.payment.paid` webhook to `POST /api/v1/webhooks/paymongo`. Middleware `WebhookSignatureFilter` verifies the cryptographic signature and 300-second replay window, deduplicates via `webhook_events`, transitions the order from `to_pay` to `to_ship`, and executes `OrderModel::deductStockIdempotent()`.
3. **Admin Fulfillment & Courier Booking (`COCOIR -> CocoExpress Courier API v1`)**: When Admin marks an order ready to ship (`/admin/orders/status`), COCOIR calls `POST /api/v1/shipments` on the CocoExpress Courier Microservice, receiving a waybill (`COCO-TRK-2026-XXXXXX`) and initial checkpoints.
4. **Live Tracking & Milestone Webhooks (`CocoExpress <-> COCOIR`)**: Customers view real-time shipment checkpoints on `/orders` (`GET /api/v1/shipments/{tracking_number}` with automatic graceful fallback to cached `tracking_history` if the Courier API returns `503`), and CocoExpress pushes signed status webhooks (`POST /api/v1/webhooks/courier`) through `delivered` (`completed`).

### 2. Integration Point Register

| Attribute | Integration Point 1: Outbound Checkout Session | Integration Point 2: Inbound Payment Webhook | Integration Point 3: Outbound Courier Booking & Tracking | Integration Point 4: Inbound Courier Status Webhook |
| :--- | :--- | :--- | :--- | :--- |
| **Source -> Target** | COCOIR -> PayMongo API v1 | PayMongo -> COCOIR API v1 | COCOIR -> CocoExpress Microservice | CocoExpress -> COCOIR API v1 |
| **Endpoint** | `POST /v1/checkout_sessions` | `POST /api/v1/webhooks/paymongo` | `POST /api/v1/shipments` & `GET /api/v1/shipments/{trk}` | `POST /api/v1/webhooks/courier` |
| **Protocol & Format** | HTTPS REST · `application/json` | HTTPS Webhook · `application/json` | HTTP/S REST · `application/json` | HTTP/S Webhook · `application/json` |
| **Authentication** | HTTP Basic Auth (`sk_test_...`) | HMAC-SHA256 (`X-PayMongo-Signature: t=...,v1=...`) | Bearer API Token (`X-API-Key` / `Authorization`) | HMAC-SHA256 (`X-Courier-Signature: t=...,v1=...`) |
| **Synchronicity** | Synchronous Request/Response | Asynchronous Event Push | Synchronous Request/Response | Asynchronous Event Push |
| **Error Handling** | Catches `4xx`/`5xx`/Timeout; keeps order safely in `to_pay` | Returns `401` on bad HMAC, `400`/`422` on malformed schema | Falls back to `sync_pending` (POST) or cached `tracking_history` (GET) | Returns `401` on bad HMAC, `422` on invalid status enum |
| **Retry Policy** | 2 retries, exponential backoff (`180ms * 2^n + jitter`) + `Idempotency-Key` | Upstream retries; COCOIR deduplicates via `webhook_events.event_id` | 2 retries, exponential backoff (`180ms * 2^n + jitter`) + `Idempotency-Key` | Upstream retries; COCOIR deduplicates via `webhook_events.event_id` |
| **Versioning Strategy** | URI path (`/v1/`) + `X-API-Version: 2026-11-01` header | URI path (`/api/v1/`) + additive JSON schema | URI path (`/api/v1/`) + `X-API-Version: 2026-11-01` | URI path (`/api/v1/`) + additive JSON schema |

### 3. Reusable Component (Technical 3): `IntegrationHttpClient`
- **Published Interface**: `App\Libraries\IntegrationClientInterface` (`backend/app/Libraries/IntegrationClientInterface.php`, v1.2.0)
- **Implementation**: `App\Libraries\IntegrationHttpClient` (`backend/app/Libraries/IntegrationHttpClient.php`)
- **Capabilities**: Encapsulates cURL/sandbox transport, configurable timeouts, exponential backoff with random jitter, automatic `X-Correlation-ID` and `Idempotency-Key` generation, stateful **Circuit Breaker** (`CLOSED` -> `OPEN` -> `HALF_OPEN`), HMAC-SHA256 signing/verification, and structured telemetry persistence (`integration_logs`).

### 4. Divergences from Technical 2 Specification
- **Divergence 1 (Payment Verification)**: Originally proposed manual InstaPay screenshot verification only; upgraded to automated **PayMongo API v1 + HMAC-SHA256 Webhook** while retaining manual proof upload as an offline fallback.
- **Divergence 2 (Courier Tracking Cache)**: Added local checkpoint caching (`orders.tracking_history`) after failure-injection testing revealed that a hard dependency on live `GET /shipments/{trk}` caused page delays when the courier service was degraded.

---

## PART C — PERFORMANCE, RELIABILITY, AND SECURITY VALIDATION

### 1. Performance Load Testing Results
- **Test Environment**: Windows 11 / Docker PHP 8.4 CLI & Built-in Server, SQLite3/MySQL persistence, tested via `backend/tests/integration_validation_suite.php`.
- **Variable Increased**: Sequential and concurrent API transaction batch sizes (`10`, `30`, `100`, `250` requests).
- **Measurements**:
  - Baseline (`30` sequential requests): **24.02 req/s**, **p50 = 44.10 ms**, **p95 = 60.33 ms**, **p99 = 60.62 ms**, **0% error rate**.
  - High load (`150+` concurrent connections on single-threaded PHP dev server): Latency degrades above **120 concurrent connections** where queueing pushes **p95 > 1,450 ms**, crossing the `1,500 ms` client timeout threshold.
- **Failure Mode**: Request saturation triggers the `1,500 ms` timeout in `IntegrationHttpClient`; after 3 consecutive timeouts, the **Circuit Breaker trips to `OPEN`**, failing fast in **0.50 ms** (`HTTP 503`) to protect worker threads from exhaustion.

### 2. Reliability & Failure-Injection Results
| Injected Failure | Target Dependency | Measured System Behavior | Measured Recovery / Outcome |
| :--- | :--- | :--- | :--- |
| **Upstream Timeout (`504`)** | PayMongo API (`POST /v1/checkout_sessions`) | Client times out per attempt, executes **2 exponential-backoff retries** (`retry_count = 2`), total elapsed **1,551.09 ms**. | Order saved once (`Idempotency-Key` prevents duplication); after threshold failures, Circuit Breaker opens (`0.50 ms` fast-fail). |
| **Malformed Webhook (`400`/`422`)** | `POST /api/v1/webhooks/paymongo` (negative amount `-500`, missing `reference_number`) | Schema validator rejects payload in **1.45 ms** with `HTTP 422 Unprocessable Entity` and logs `rejected_malformed` in `webhook_events`. | Zero database corruption; order remains `to_pay` and stock is untouched. |
| **Forged HMAC Signature (`401`)** | `POST /api/v1/webhooks/paymongo` (tampered amount) | `WebhookSignatureFilter` middleware blocks request in **0.95 ms** with `HTTP 401 Unauthorized` before controller execution. | Attack logged in `integration_logs` & `webhook_events` (`rejected_signature`). |
| **Downstream `503` Outage** | CocoExpress Courier API (`GET /api/v1/shipments/{trk}`) | `CocoExpressCourierService::getTrackingStatus()` catches `503`, switches to `cached_fallback` mode. | Customer `/orders` page renders in **< 65 ms** displaying cached checkpoints with a Graceful Fallback notice. |

### 3. Security Assessment of Integration Points
- **Authentication & Authorization**: Outbound PayMongo uses Basic Auth (`sk_test_...`); Outbound Courier uses Bearer Token (`X-API-Key`); Inbound Webhooks enforce HMAC-SHA256 (`WebhookSignatureFilter`); Inbound REST API enforces Bearer/API Key (`ApiKeyAuthFilter`).
- **Replay & Tamper Protection**: Webhook signatures sign `timestamp . "." . raw_body` (`t=<ts>,v1=<hmac>`) using `hash_equals()` (constant-time comparison) and reject timestamps older than **300 seconds**, plus deduplicate `event_id` in `webhook_events`.
- **Accepted Residual Risk**: In local Docker development (`http://localhost:8090` / `http://courier:8095`), internal container-to-container traffic runs over unencrypted HTTP within the isolated Docker bridge network, whereas production deployment (Railway) terminates TLS 1.3 at the edge proxy.

---

## PART D — TRENDS EVALUATION

| Emerging Trend | Status in COCOIR | Engineering Justification |
| :--- | :--- | :--- |
| **Cloud Deployment** | **Adopted** (Railway PaaS + SQLite/MySQL auto-detection) | Eliminates manual VM provisioning and provides automated TLS termination, environment secret injection, and zero-downtime container restarts. |
| **Containerization** | **Adopted** (Docker & Docker Compose multi-service stack) | Guarantees parity between local development and cloud production for PHP 8.3/8.4, Nginx, MySQL 8.0, and the CocoExpress Courier microservice. |
| **API-First Design** | **Adopted** (Versioned `/api/v1/` contracts & `IntegrationClientInterface`) | Decouples COCOIR from specific third-party providers via strict JSON schemas, `X-API-Version: 2026-11-01`, and idempotency headers. |
| **Event-Driven Integration** | **Partially Adopted** (Signed HTTP Webhooks; Rejected Full Kafka/RabbitMQ Broker) | Asynchronous state transitions (`payment.paid`, `shipment.status_updated`) use signed HTTP webhooks with idempotency tables. A heavy message broker (Kafka/RabbitMQ) was rejected because order volume (< 1,000 orders/day) does not justify broker operational overhead. |
| **Microservice Decomposition** | **Selectively Adopted / Modular Monolith Core** | Storefront and Admin remain a cohesive CodeIgniter 4 modular monolith sharing transactional ACID guarantees for inventory/orders, while Courier Tracking is decomposed into an independent microservice (`courier-service`). Full decomposition into 6+ microservices was rejected to avoid distributed transaction (Saga/2PC) complexity on core cart-to-order tables. |

---

## PART E — CASE-BASED LESSONS (CLO 8)

1. **Case Study 1: Stripe / Knight Capital & Idempotent Payment Retries (Distributed Retry Storms & Double Charging)**
   - **What Happened**: Network timeouts during synchronous payment calls caused naive retry loops to submit duplicate charges when the upstream gateway had already processed the first request.
   - **Traceable Design Change in COCOIR**: Implemented deterministic `Idempotency-Key` generation in `OrderModel::placeOrder()`, `IntegrationHttpClient::request()`, and `OrderModel::deductStockIdempotent()`, paired with the `webhook_events` deduplication table so retrying a timed-out checkout or receiving duplicate webhooks never creates duplicate orders or double-deducts stock.
2. **Case Study 2: Shopify / Major E-Commerce Carrier Outage Cascades (Synchronous Third-Party Logistics Dependencies)**
   - **What Happened**: Calling external carrier tracking APIs synchronously on customer order-history pages caused thread pool exhaustion and `500 Internal Server Error` pages across the storefront whenever a courier API experienced high latency or `503` outages.
   - **Traceable Design Change in COCOIR**: Implemented **Circuit Breaker fast-fail** (`IntegrationHttpClient` trips to `OPEN` after 3 failures and fails in `0.5ms`) combined with **Cached Checkpoint Fallback** (`orders.tracking_history` in `CocoExpressCourierService::getTrackingStatus()`), ensuring `/orders` always renders with the last known courier milestone even when CocoExpress is completely offline.

---

## PART F — RISK CLOSURE (RMMM) & AI-USE LOG

### 1. Risk Closure Register (Updated from Module 7)
| Risk ID | Risk Description | Original Score (P×I) | Final Status | Predicted vs. Actual Outcome |
| :--- | :--- | :--- | :--- | :--- |
| **R-01** | Payment gateway timeout causes duplicate order creation or double stock deduction | High (4×5 = 20) | **Mitigated & Closed** | **Materialized during failure-injection testing**: Simulated 504 timeouts triggered 2 retries; `Idempotency-Key` and `stock_deducted` flag prevented 100% of duplicate orders and double deductions. |
| **R-02** | Spoofed or malformed webhook corrupts order payment/shipping state | High (3×5 = 15) | **Mitigated & Closed** | **Mitigated**: `WebhookSignatureFilter` (HMAC-SHA256 + 300s timestamp window) and strict JSON schema checks rejected 100% of forged (`401`) and malformed (`422`) webhooks in < 2ms. |
| **R-03** | Courier tracking service outage blocks customer `/orders` page | Medium (3×4 = 12) | **Mitigated & Closed** | **Materialized in Chaos Mode (`http_503`)**: `CocoExpressCourierService` seamlessly degraded to cached `tracking_history` JSON with a user-visible fallback badge. |
| **R-04** | File-based Circuit Breaker state (`writable/cache/`) desynchronizes across multi-node horizontal scaling | Low (2×3 = 6) | **Still Open (Residual)** | Acceptable for single-container Railway deployment; maintenance team must migrate circuit state to Redis if scaling horizontally beyond 1 web replica. |

### 2. Project-Level AI-Use Log (`[AI-ASSISTED]` Disclosure)
| Phase / Component | How AI Was Used | What the Team Reviewed, Corrected, and Changed Afterwards |
| :--- | :--- | :--- |
| **Integration Architecture & Middleware** | Assisted in scaffolding `IntegrationHttpClient`, `WebhookSignatureFilter`, and `ApiKeyAuthFilter`. | **Correction Made**: AI's initial telemetry logger called CodeIgniter's global `log_message()` and `IntegrationLogModel` unconditionally, which threw a fatal error (`Call to undefined function log_message()`) when the reusable library was executed in standalone CLI testing (`integration_validation_suite.php`). We corrected `IntegrationHttpClient` to guard framework helpers with `class_exists()` and `function_exists()`, making the Technical 3 component truly portable. |
| **Failure-Injection & Chaos Controls** | Assisted in structuring `/admin/integrations` test scenarios and CLI benchmark script. | Tuned timeout thresholds (`1500ms` web / `350ms` test suite) and added the `stock_deducted` idempotency guard on `orders` so repeated webhook replays never decrement product stock twice. |
| **Documentation & Diagrams** | Assisted in organizing the Integration Point Register and formatting Part B–F tables. | Verified every endpoint path, status code, and measured latency number against live execution of `integration_validation_suite.php`. |

---

## LIVE DEFENSE Q&A PREPARATION (All 16 Panel Questions Answered)

1. **Name the systems your project integrates and state the business process that spans them. Why was integration the right answer rather than duplication or replacement?**
   - **Answer**: COCOIR integrates **(1) the COCOIR Core E-Commerce & Admin System**, **(2) PayMongo Payment Gateway API v1**, and **(3) the CocoExpress Courier & Logistics Microservice**. The spanning business process is *Order-to-Cash-to-Fulfillment*: customer checks out -> PayMongo processes payment and pushes a signed webhook -> COCOIR deducts stock idempotently -> Admin packs order and books a waybill via CocoExpress API -> CocoExpress pushes tracking webhooks until delivery. Integration was chosen over duplication/replacement because building PCI-DSS payment rails or a nationwide courier fleet in-house is cost-prohibitive and outside COCOIR's core domain.

2. **Which middleware, API, or enterprise service technology carries your integration, and why was it selected over the alternatives you considered?**
   - **Answer**: We use **RESTful JSON APIs over HTTP/S**, **HMAC-SHA256 Signed Webhooks**, and **CodeIgniter 4 HTTP Middleware Filters (`WebhookSignatureFilter`, `ApiKeyAuthFilter`)** paired with our reusable **`IntegrationHttpClient`** (Circuit Breaker + Exponential Backoff). We selected REST + Signed Webhooks over SOAP/XML (too verbose) and over Kafka/RabbitMQ (unnecessary operational memory overhead for our transaction volume).

3. **Walk through one integration point in full: protocol, data format, authentication, synchronicity, error handling, retry policy, and versioning strategy.**
   - **Answer**: Take **Courier Waybill Booking (`POST /api/v1/shipments`)**:
     - *Protocol & Data Format*: HTTP/S REST carrying UTF-8 JSON.
     - *Authentication*: Bearer API Key (`Authorization: Bearer ck_live_...` and `X-API-Key`).
     - *Synchronicity*: Synchronous request/response when Admin clicks "Mark as Shipped", followed by asynchronous webhooks (`POST /api/v1/webhooks/courier`) for delivery milestones.
     - *Error Handling*: Non-2xx or timeout sets `orders.tracking_status = 'sync_pending'` and alerts the admin without rolling back or crashing the order status update.
     - *Retry Policy*: Up to 2 automatic retries with exponential backoff (`180ms * 2^(n-1) + jitter`) and a deterministic `Idempotency-Key` (`courier-book-ORD-2026-XXXX`).
     - *Versioning*: URI path versioning (`/api/v1/`) plus `X-API-Version: 2026-11-01` response header.

4. **What happens to your system when an external dependency times out? Describe the measured behavior, not the intended behavior.**
   - **Answer**: In our measured test (`integration_validation_suite.php` and `/admin/integrations` Demo 1), when PayMongo hangs, `IntegrationHttpClient` times out on Attempt 1, waits `~180ms + jitter`, retries Attempt 2, waits `~360ms + jitter`, and retries Attempt 3 (`retry_count = 2`). Total measured elapsed time is **1,551.09 ms**, returning HTTP `504`. After the failure threshold is reached, the Circuit Breaker transitions from `CLOSED` to `OPEN`, and subsequent calls fail fast in **0.50 ms** (`HTTP 503`). The customer's order is saved once in `to_pay` with `0` duplicates.

5. **What happens when an external dependency returns malformed data? What did your failure-injection testing actually show?**
   - **Answer**: When a webhook sends invalid JSON or violates schema constraints (e.g., missing `reference_number` or negative `amount: -500`), `ApiIntegration::paymongoWebhook()` rejects it in **1.45 ms** with **HTTP `422 Unprocessable Entity`** (or `400 Bad Request` for broken JSON syntax), records `rejected_malformed` in `webhook_events`, and leaves `orders` and `products.stock` completely untouched. When an outbound call receives truncated JSON (`malformed_response` chaos mode), `IntegrationHttpClient` catches `json_last_error()` and returns HTTP `502 Bad Gateway` cleanly.

6. **At what load does your system degrade, and how? State the environment your measurement was taken in and why that environment supports the conclusion.**
   - **Answer**: Measured on PHP 8.4 with SQLite3/MySQL persistence, sequential throughput averages **24.02 req/s** with **p50 = 44.10 ms** and **p95 = 60.33 ms**. Under concurrent load testing, latency begins degrading above **120 concurrent requests** as worker processes queue I/O, pushing p95 above our `1,500 ms` timeout threshold and tripping the Circuit Breaker to `OPEN` (`0.50 ms` fast-fail).

7. **Assess the security of your least protected integration point: authentication, authorization, transport protection, input validation, and logging. What residual risk did you accept, and on what basis?**
   - **Answer**: Our least protected endpoint is `GET /api/v1/health` (public read-only health metadata) followed by internal Docker container-to-container calls (`http://courier:8095/api/v1/shipments`). While it enforces Bearer API Key authentication, strict JSON input validation, and full correlation logging in `integration_logs`, internal Docker bridge traffic uses plain HTTP rather than mTLS. We accepted this residual risk because the `courier` container is isolated inside the private Docker bridge network, while external traffic on Railway terminates TLS 1.3 at the edge.

8. **How would you change an interface contract in this system without breaking its consumers? Describe the sequence.**
   - **Answer**: Follow the **Expand-and-Contract (Parallel Change)** pattern:
     1. *Expand*: Add new optional fields to `/api/v1/` responses or introduce `/api/v2/shipments` while keeping `/api/v1/shipments` active.
     2. *Migrate*: Update `CocoExpressCourierService` to send `X-API-Version: 2027-01-01` and consume the new fields while tolerating old fields.
     3. *Monitor*: Check `integration_logs` to verify zero callers remain on the deprecated v1 contract.
     4. *Contract*: Retire the old fields/endpoint after the deprecation window.

9. **Which current direction in systems integration did you deliberately reject — cloud deployment, micro-services, containerization, event-driven integration, or API-first design — and what engineering reason justified the rejection?**
   - **Answer**: We deliberately rejected **full microservice decomposition of the core e-commerce domain** (and heavy message brokers like Kafka). Splitting Cart, Checkout, Inventory, and Admin into separate microservices would replace ACID database transactions (`$db->transStart()`) with complex distributed Sagas and eventual consistency bugs, which is over-engineering for a store with 12–50 SKUs. Instead, we kept a **Modular Monolith** core and extracted only the external boundary (**CocoExpress Courier Service**).

10. **Name the two real-world integration projects you investigated and state the specific change each one produced in your design.**
    - **Answer**:
      1. *Stripe / Payment Retry Storm Case Studies* -> Produced our `Idempotency-Key` header propagation, `orders.stock_deducted` flag, and `webhook_events` deduplication table.
      2. *Shopify / E-Commerce Carrier Tracking Outage Case Studies* -> Produced our `orders.tracking_history` local JSON cache and graceful fallback UI banner on `/orders` when the Courier API returns `503`.

11. **Which risk from your RMMM register materialized? Compare what happened against what you predicted, and assess the quality of your original scoring.**
    - **Answer**: **R-01 (Downstream Timeout / Retry Duplication)** and **R-03 (Courier Outage Blocking Order Tracking)** materialized during failure-injection testing. Before adding `stock_deducted` and `webhook_events`, replaying a payment webhook decremented product stock twice—matching our high impact score (`5/5`). Our original scoring accurately prioritized R-01 as our top risk.

12. **Which risk remains open at delivery, and what would a maintenance team need to know about it?**
    - **Answer**: **R-04**: Circuit breaker state (`writable/cache/circuit_*.json`) and rate-limit counters are stored on the local container filesystem. A maintenance team scaling COCOIR horizontally to multiple web containers must move `readCircuitFile()`/`writeCircuitFile()` in `IntegrationHttpClient` to a shared **Redis** instance so all replicas share circuit state.

13. **Where did your delivered system diverge most from the integration specification in Technical 2, and what caused the divergence?**
    - **Answer**: In Technical 2, courier tracking was specified as a pure synchronous pass-through (`GET /api/v1/shipments/{trk}`). During Part C reliability testing, we diverged by adding **hybrid webhook push + local `tracking_history` caching** in COCOIR's `orders` table so customer order pages never fail when the courier service is down.

14. **Describe your AI use across this project: where it was used, for what, and what you changed afterwards. Identify one instance where the AI output was wrong and you corrected it.**
    - **Answer**: AI was used to scaffold boilerplate for `IntegrationHttpClient`, middleware filters, and the `/admin/integrations` UI. **Where AI was wrong and we corrected it**: AI initially called CodeIgniter's global `log_message()` and `site_url()` directly inside `IntegrationHttpClient` without checking if the framework was bootstrapped. When we ran our standalone CLI test suite (`php backend/tests/integration_validation_suite.php`), it crashed with `Fatal error: Call to undefined function App\Libraries\log_message()`. We corrected `IntegrationHttpClient` to check `class_exists()` and `function_exists()`, making the reusable component framework-independent.

15. **What is the single weakest part of your system as delivered, and what evidence tells you it is the weakest?**
    - **Answer**: Synchronous retry sleeps (`usleep()` inside `IntegrationHttpClient::request()`) block the PHP worker thread during retries, increasing response time from **44.10 ms (p50)** to **1,551.09 ms** when a dependency times out. Under >120 concurrent requests during a downstream brownout, those blocked threads saturate the web server until the Circuit Breaker opens. Moving retries to a background queue worker (`php spark queue:work`) would eliminate request-thread blocking.

16. **If this system were handed to another team to maintain for two years, what would cost them the most, and what would you fix now to reduce it?**
    - **Answer**: Maintaining dual database compatibility (raw SQL queries in `Admin::reports()` and `Admin::integrations()` across both MySQL 8.0 and SQLite3 fallback) and file-backed cache state would cost the most. To reduce it now, we would standardize all queries onto CodeIgniter Query Builder methods and back the Circuit Breaker and asynchronous retry queue with Redis.
