<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EcommerceCleanArchFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_health_and_catalog_filtering_sorting_and_detail(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'architecture' => 'modular-monolith-clean-arch',
                'version' => '1.0.0',
            ]);

        $categories = $this->getJson('/api/v1/catalog/categories')
            ->assertOk()
            ->json('data');
        $this->assertCount(4, $categories);

        $search = $this->getJson('/api/v1/catalog/products?q=Urban')
            ->assertOk()
            ->json('data');
        $this->assertCount(1, $search);
        $this->assertSame('ao-khoac-ky-thuat-urban-shell', $search[0]['slug']);

        $byCategory = $this->getJson('/api/v1/catalog/products?category=balo-tui-chong-nuoc')
            ->assertOk()
            ->json('data');
        $this->assertCount(1, $byCategory);
        $this->assertSame('balo-tui-chong-nuoc', $byCategory[0]['category']['slug']);

        $sortedAsc = $this->getJson('/api/v1/catalog/products?sort=price_asc')
            ->assertOk()
            ->json('data');
        $pricesAsc = array_column($sortedAsc, 'min_price_minor');
        $expectedAsc = $pricesAsc;
        sort($expectedAsc);
        $this->assertSame($expectedAsc, $pricesAsc);

        $sortedDesc = $this->getJson('/api/v1/catalog/products?sort=price_desc')
            ->assertOk()
            ->json('data');
        $pricesDesc = array_column($sortedDesc, 'min_price_minor');
        $expectedDesc = $pricesDesc;
        rsort($expectedDesc);
        $this->assertSame($expectedDesc, $pricesDesc);

        $detail = $this->getJson('/api/v1/catalog/products/ao-khoac-ky-thuat-urban-shell')
            ->assertOk()
            ->json('data');
        $this->assertNotEmpty($detail['variants']);
        $this->assertNotEmpty($detail['reviews']);
        $this->assertTrue($detail['reviews'][0]['is_verified_purchase']);
    }

    public function test_identity_register_login_me_logout_and_clean_401_without_redirect(): void
    {
        // Even without Accept: application/json header, API returns JSON 401 cleanly
        $this->get('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);

        $registerRes = $this->postJson('/api/v1/auth/register', [
            'name' => 'Trần Thanh Hà',
            'email' => 'thanhha@cleancommerce.vn',
            'password' => 'Secret@123',
            'phone' => '0988777666',
        ])->assertCreated();

        $token = $registerRes->json('data.token');
        $this->assertNotEmpty($token);
        $this->assertSame('customer', $registerRes->json('data.user.role'));

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'thanhha@cleancommerce.vn');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJson(['message' => 'Đã đăng xuất']);

        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'thanhha@cleancommerce.vn',
            'password' => 'Secret@123',
        ])->assertOk();
        $this->assertNotEmpty($loginRes->json('data.token'));

        $this->postJson('/api/v1/auth/login', [
            'email' => 'thanhha@cleancommerce.vn',
            'password' => 'WrongPassword',
        ])->assertStatus(401);
    }

    public function test_cart_quote_calculates_exact_minor_units_and_coupon_caps(): void
    {
        $variantId = (int) DB::table('catalog_variants')
            ->where('sku', 'CW-SHELL-BLK-M')
            ->value('id');

        // 1 item = 890,000 VND -> WELCOME10 = 89,000 discount + 30,000 shipping = 831,000 VND
        $this->postJson('/api/v1/cart/quote', [
            'items' => [['variant_id' => $variantId, 'quantity' => 1]],
            'coupon_code' => 'WELCOME10',
        ])
            ->assertOk()
            ->assertJsonPath('data.subtotal_minor', 890000)
            ->assertJsonPath('data.discount_minor', 89000)
            ->assertJsonPath('data.shipping_fee_minor', 30000)
            ->assertJsonPath('data.grand_total_minor', 831000)
            ->assertJsonPath('data.applied_coupon.code', 'WELCOME10')
            ->assertJsonPath('data.coupon_error', null);

        // 2 items = 1,780,000 VND -> WELCOME10 capped at 150,000 + free shipping (>= 1M) = 1,630,000 VND
        $this->postJson('/api/v1/cart/quote', [
            'items' => [['variant_id' => $variantId, 'quantity' => 2]],
            'coupon_code' => 'WELCOME10',
        ])
            ->assertOk()
            ->assertJsonPath('data.subtotal_minor', 1780000)
            ->assertJsonPath('data.discount_minor', 150000)
            ->assertJsonPath('data.shipping_fee_minor', 0)
            ->assertJsonPath('data.grand_total_minor', 1630000);
    }

    public function test_idempotent_checkout_prevents_duplicate_orders_and_enforces_anti_bola(): void
    {
        $customerLogin = $this->postJson('/api/v1/auth/login', [
            'email' => 'khachhang@cleancommerce.vn',
            'password' => 'Customer@123456',
        ])->assertOk();
        $customerToken = (string) $customerLogin->json('data.token');

        $variantId = (int) DB::table('catalog_variants')
            ->where('sku', 'CW-SHELL-BLK-L')
            ->value('id');
        $stockBefore = (int) DB::table('inventory_stocks')
            ->where('variant_id', $variantId)
            ->value('available_stock');
        $ordersBefore = (int) DB::table('ordering_orders')->count();

        $payload = [
            'customer_name' => 'Nguyễn Minh Quân',
            'customer_email' => 'khachhang@cleancommerce.vn',
            'customer_phone' => '0901234567',
            'shipping_address' => '120 Nguyễn Huệ, Quận 1',
            'shipping_province' => 'TP. Hồ Chí Minh',
            'payment_method' => 'vietqr',
            'coupon_code' => 'WELCOME10',
            'note' => 'Giao giờ hành chính',
            'items' => [['variant_id' => $variantId, 'quantity' => 1]],
        ];

        $idemKey = 'idem-checkout-uuid-0001';

        $first = $this->withHeaders([
            'Authorization' => 'Bearer '.$customerToken,
            'Idempotency-Key' => $idemKey,
        ])->postJson('/api/v1/checkout', $payload)->assertCreated();

        $second = $this->withHeaders([
            'Authorization' => 'Bearer '.$customerToken,
            'Idempotency-Key' => $idemKey,
        ])->postJson('/api/v1/checkout', $payload)->assertCreated();

        $this->assertSame('true', $second->headers->get('X-Idempotent-Replay'));
        $this->assertSame($first->json('data.order_number'), $second->json('data.order_number'));
        $this->assertSame($ordersBefore + 1, (int) DB::table('ordering_orders')->count());
        $this->assertSame(
            $stockBefore - 1,
            (int) DB::table('inventory_stocks')->where('variant_id', $variantId)->value('available_stock')
        );

        // Same Idempotency-Key with mutated payload -> 409 Conflict
        $mutated = $payload;
        $mutated['items'] = [['variant_id' => $variantId, 'quantity' => 2]];
        $this->withHeaders([
            'Authorization' => 'Bearer '.$customerToken,
            'Idempotency-Key' => $idemKey,
        ])->postJson('/api/v1/checkout', $mutated)->assertStatus(409);

        // Anti-BOLA: another registered customer cannot view this user's order
        $intruder = $this->postJson('/api/v1/auth/register', [
            'name' => 'Người Dùng Khác',
            'email' => 'other@cleancommerce.vn',
            'password' => 'Secret@123',
        ])->assertCreated();
        $intruderToken = (string) $intruder->json('data.token');

        $orderNumber = (string) $first->json('data.order_number');

        $this->withHeader('Authorization', 'Bearer '.$intruderToken)
            ->getJson('/api/v1/orders/'.$orderNumber)
            ->assertStatus(403);

        $this->withHeader('Authorization', 'Bearer '.$customerToken)
            ->getJson('/api/v1/orders/'.$orderNumber)
            ->assertOk()
            ->assertJsonPath('data.order_number', $orderNumber);
    }

    public function test_stock_oversell_is_rejected_atomically_leaving_stock_untouched(): void
    {
        $lowVariantId = (int) DB::table('catalog_variants')
            ->where('sku', 'CW-SHELL-OLV-L')
            ->value('id');
        $availableBefore = (int) DB::table('inventory_stocks')
            ->where('variant_id', $lowVariantId)
            ->value('available_stock');
        $this->assertSame(3, $availableBefore);

        $ordersBefore = (int) DB::table('ordering_orders')->count();

        $this->withHeader('Idempotency-Key', 'idem-oversell-test-001')
            ->postJson('/api/v1/checkout', [
                'customer_name' => 'Khách Vãng Lai',
                'customer_email' => 'guest@cleancommerce.vn',
                'customer_phone' => '0911222333',
                'shipping_address' => '88 Lê Lợi, Quận 1',
                'shipping_province' => 'TP. Hồ Chí Minh',
                'payment_method' => 'cod',
                'items' => [['variant_id' => $lowVariantId, 'quantity' => 5]],
            ])
            ->assertStatus(422);

        $this->assertSame(
            3,
            (int) DB::table('inventory_stocks')->where('variant_id', $lowVariantId)->value('available_stock')
        );
        $this->assertSame($ordersBefore, (int) DB::table('ordering_orders')->count());
    }

    public function test_hmac_payment_webhook_verification_inbox_deduplication_and_dev_simulator(): void
    {
        $variantId = (int) DB::table('catalog_variants')
            ->where('sku', 'DL-BAR-BLK-CRI97')
            ->value('id');

        $checkoutRes = $this->withHeader('Idempotency-Key', 'idem-webhook-flow-001')
            ->postJson('/api/v1/checkout', [
                'customer_name' => 'Lê Hoàng Nam',
                'customer_email' => 'nam@cleancommerce.vn',
                'customer_phone' => '0933444555',
                'shipping_address' => '10 Trần Phú, Hải Châu',
                'shipping_province' => 'Đà Nẵng',
                'payment_method' => 'vietqr',
                'items' => [['variant_id' => $variantId, 'quantity' => 1]],
            ])
            ->assertCreated();

        $orderNumber = (string) $checkoutRes->json('data.order_number');
        $grandTotalMinor = (int) $checkoutRes->json('data.grand_total_minor');

        $webhookBody = [
            'event_id' => 'evt_sepay_unique_99001',
            'order_number' => $orderNumber,
            'amount_minor' => $grandTotalMinor,
            'transaction_ref' => 'FT26321998877',
        ];

        // 1. Invalid HMAC signature -> 401
        $this->withHeader('X-Webhook-Signature', 'deadbeef_invalid_hmac')
            ->postJson('/api/v1/webhooks/payment/sepay', $webhookBody)
            ->assertStatus(401);

        $this->assertSame(
            'unpaid',
            DB::table('ordering_orders')->where('order_number', $orderNumber)->value('payment_status')
        );

        // 2. Valid HMAC signature -> 200 & settles order
        $rawJson = json_encode($webhookBody, JSON_THROW_ON_ERROR);
        $secret = (string) config('services.webhooks.sepay');
        $validHmac = hash_hmac('sha256', $rawJson, $secret);

        $this->call(
            'POST',
            '/api/v1/webhooks/payment/sepay',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_WEBHOOK_SIGNATURE' => $validHmac,
            ],
            $rawJson
        )
            ->assertOk()
            ->assertJsonPath('data.acknowledged', true)
            ->assertJsonPath('data.duplicate', false)
            ->assertJsonPath('data.order_status', 'paid')
            ->assertJsonPath('data.payment_status', 'paid');

        $txCountAfterFirst = (int) DB::table('payment_transactions')
            ->where('order_number', $orderNumber)
            ->count();
        $this->assertSame(1, $txCountAfterFirst);

        // 3. Duplicate external event_id -> 200 duplicate=true, no double settlement
        $this->call(
            'POST',
            '/api/v1/webhooks/payment/sepay',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_WEBHOOK_SIGNATURE' => $validHmac,
            ],
            $rawJson
        )
            ->assertOk()
            ->assertJsonPath('data.duplicate', true)
            ->assertJsonPath('data.payment_status', 'paid');

        $this->assertSame(
            1,
            (int) DB::table('payment_transactions')->where('order_number', $orderNumber)->count()
        );

        // 4. Dev webhook simulator settles another pending order in 1 click
        $secondCheckout = $this->withHeader('Idempotency-Key', 'idem-dev-sim-002')
            ->postJson('/api/v1/checkout', [
                'customer_name' => 'Phạm Thu Trang',
                'customer_email' => 'trang@cleancommerce.vn',
                'customer_phone' => '0977666555',
                'shipping_address' => '22 Nguyễn Trãi',
                'shipping_province' => 'Hà Nội',
                'payment_method' => 'vietqr',
                'items' => [['variant_id' => $variantId, 'quantity' => 1]],
            ])
            ->assertCreated();

        $secondOrderNumber = (string) $secondCheckout->json('data.order_number');

        $this->postJson('/api/v1/payments/dev-simulate-webhook', [
            'order_number' => $secondOrderNumber,
        ])
            ->assertOk()
            ->assertJsonPath('data.order_number', $secondOrderNumber)
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.payment_status', 'paid');
    }

    public function test_admin_rbac_analytics_inventory_adjustment_product_creation_and_order_cancel_restock(): void
    {
        $customerToken = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'khachhang@cleancommerce.vn',
            'password' => 'Customer@123456',
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$customerToken)
            ->getJson('/api/v1/admin/analytics/overview')
            ->assertStatus(403);

        $adminToken = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@cleancommerce.vn',
            'password' => 'Admin@123456',
        ])->json('data.token');

        // 1. Admin analytics overview
        $overview = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/v1/admin/analytics/overview')
            ->assertOk()
            ->json('data');

        $this->assertGreaterThan(0, $overview['gmv_minor']);
        $this->assertSame(2, $overview['paid_orders_count']);
        $this->assertNotEmpty($overview['low_stock_variants']);
        $this->assertSame('CW-SHELL-OLV-L', $overview['low_stock_variants'][0]['sku']);

        // 2. Admin inventory adjust
        $lowVariantId = (int) $overview['low_stock_variants'][0]['variant_id'];
        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/v1/admin/inventory/adjust', [
                'variant_id' => $lowVariantId,
                'delta' => 7,
                'reason' => 'Nhập bổ sung lô hàng mới từ xưởng',
            ])
            ->assertOk()
            ->assertJsonPath('data.available_stock', 10);

        // 3. Admin create product
        $categoryId = (int) DB::table('catalog_categories')->value('id');
        $createdProduct = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->postJson('/api/v1/admin/products', [
                'category_id' => $categoryId,
                'name' => 'Áo Thun Merino Khử Mùi CleanTee',
                'brand' => 'CleanWear',
                'short_description' => 'Sợi len Merino 18.5 micron điều hoà thân nhiệt tự nhiên.',
                'description' => 'Mặc liên tục nhiều ngày không bám mùi, thoáng khí vượt trội.',
                'image_url' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1000&q=80',
                'variants' => [
                    [
                        'sku' => 'CW-MERINO-BLK-M',
                        'color' => 'Đen Tuyền',
                        'size' => 'M',
                        'price_minor' => 590000,
                        'initial_stock' => 15,
                    ],
                ],
            ])
            ->assertCreated()
            ->json('data');

        $this->assertSame(15, $createdProduct['total_available_stock']);
        $newVariantId = (int) $createdProduct['variants'][0]['id'];

        // 4. Checkout then Admin cancels order -> reserved stock released back to 15!
        $orderRes = $this->withHeader('Idempotency-Key', 'idem-cancel-restock-001')
            ->postJson('/api/v1/checkout', [
                'customer_name' => 'Vũ Minh Đức',
                'customer_email' => 'duc@cleancommerce.vn',
                'customer_phone' => '0944555666',
                'shipping_address' => '99 Hai Bà Trưng',
                'shipping_province' => 'TP. Hồ Chí Minh',
                'payment_method' => 'vietqr',
                'items' => [['variant_id' => $newVariantId, 'quantity' => 3]],
            ])
            ->assertCreated();

        $this->assertSame(
            12,
            (int) DB::table('inventory_stocks')->where('variant_id', $newVariantId)->value('available_stock')
        );

        $orderNumber = (string) $orderRes->json('data.order_number');

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->patchJson('/api/v1/admin/orders/'.$orderNumber.'/status', [
                'status' => 'cancelled',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(
            15,
            (int) DB::table('inventory_stocks')->where('variant_id', $newVariantId)->value('available_stock')
        );
    }

    public function test_outbox_relay_command_dispatches_pending_events_and_sweeps_expired_reservations(): void
    {
        $variantId = (int) DB::table('catalog_variants')
            ->where('sku', 'EC-WRIST-TKL-80')
            ->value('id');
        $initialStock = (int) DB::table('inventory_stocks')
            ->where('variant_id', $variantId)
            ->value('available_stock');

        $orderRes = $this->withHeader('Idempotency-Key', 'idem-outbox-sweep-001')
            ->postJson('/api/v1/checkout', [
                'customer_name' => 'Đặng Quốc Bảo',
                'customer_email' => 'bao@cleancommerce.vn',
                'customer_phone' => '0966777888',
                'shipping_address' => '15 Cầu Giấy',
                'shipping_province' => 'Hà Nội',
                'payment_method' => 'vietqr',
                'items' => [['variant_id' => $variantId, 'quantity' => 2]],
            ])
            ->assertCreated();

        $orderNumber = (string) $orderRes->json('data.order_number');

        $this->assertGreaterThan(
            0,
            (int) DB::table('outbox_messages')->whereNull('dispatched_at')->count()
        );
        $this->assertSame(
            $initialStock - 2,
            (int) DB::table('inventory_stocks')->where('variant_id', $variantId)->value('available_stock')
        );

        // Simulate reservation TTL expiration
        DB::table('inventory_reservations')
            ->where('order_number', $orderNumber)
            ->update(['expires_at' => now()->subMinutes(5)]);

        $this->artisan('outbox:relay', ['--once' => true])
            ->assertExitCode(0);

        $this->assertSame(
            0,
            (int) DB::table('outbox_messages')->whereNull('dispatched_at')->count()
        );
        $this->assertSame(
            'expired',
            DB::table('inventory_reservations')->where('order_number', $orderNumber)->value('status')
        );
        $this->assertSame(
            $initialStock,
            (int) DB::table('inventory_stocks')->where('variant_id', $variantId)->value('available_stock')
        );
    }

    public function test_webhook_rejects_underpayment_and_guards_late_payment_after_expired_hold_against_phantom_oversell(): void
    {
        // Use low-stock SKU CW-SHELL-OLV-L (available_stock = 3)
        $variantId = (int) DB::table('catalog_variants')
            ->where('sku', 'CW-SHELL-OLV-L')
            ->value('id');

        // Buyer A reserves all 3 units -> available_stock becomes 0
        $buyerACheckout = $this->withHeader('Idempotency-Key', 'idem-late-buyer-a')
            ->postJson('/api/v1/checkout', [
                'customer_name' => 'Đặng Quốc Bảo',
                'customer_email' => 'bao@cleancommerce.vn',
                'customer_phone' => '0911222333',
                'shipping_address' => '88 Lý Tự Trọng, Quận 1',
                'shipping_province' => 'TP. Hồ Chí Minh',
                'payment_method' => 'vietqr',
                'items' => [['variant_id' => $variantId, 'quantity' => 3]],
            ])
            ->assertCreated();

        $orderA = (string) $buyerACheckout->json('data.order_number');
        $totalAMinor = (int) $buyerACheckout->json('data.grand_total_minor');

        // 1. Tampered underpayment webhook (10,000 VND < grand_total_minor) -> rejected 422, remains unpaid
        $tamperedBody = json_encode([
            'event_id' => 'evt_tampered_underpay_01',
            'order_number' => $orderA,
            'amount_minor' => 10000,
            'transaction_ref' => 'FT_UNDERPAY_01',
        ], JSON_THROW_ON_ERROR);
        $secret = (string) config('services.webhooks.sepay');
        $tamperedHmac = hash_hmac('sha256', $tamperedBody, $secret);

        $this->call('POST', '/api/v1/webhooks/payment/sepay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => $tamperedHmac,
        ], $tamperedBody)->assertStatus(422);

        $this->assertSame('unpaid', DB::table('ordering_orders')->where('order_number', $orderA)->value('payment_status'));

        // 2. Buyer A takes > 30 minutes -> reservation expires and outbox:relay returns all 3 units to pool
        DB::table('inventory_reservations')
            ->where('order_number', $orderA)
            ->update(['expires_at' => now()->subMinutes(5)]);

        $this->artisan('outbox:relay', ['--once' => true])->assertExitCode(0);
        $this->assertSame(3, (int) DB::table('inventory_stocks')->where('variant_id', $variantId)->value('available_stock'));

        // 3. Buyer B legitimately buys all 3 units -> available_stock == 0
        $this->withHeader('Idempotency-Key', 'idem-buyer-b-takes-stock')
            ->postJson('/api/v1/checkout', [
                'customer_name' => 'Trần Khánh Linh',
                'customer_email' => 'linh@cleancommerce.vn',
                'customer_phone' => '0988777666',
                'shipping_address' => '15 Hai Bà Trưng',
                'shipping_province' => 'Hà Nội',
                'payment_method' => 'cod',
                'items' => [['variant_id' => $variantId, 'quantity' => 3]],
            ])
            ->assertCreated();

        $this->assertSame(0, (int) DB::table('inventory_stocks')->where('variant_id', $variantId)->value('available_stock'));

        // 4. Buyer A's bank finally sends a valid late webhook -> NO phantom negative stock; routed to refund_required!
        $lateBody = json_encode([
            'event_id' => 'evt_late_after_expiry_01',
            'order_number' => $orderA,
            'amount_minor' => $totalAMinor,
            'transaction_ref' => 'FT_LATE_VALID_01',
        ], JSON_THROW_ON_ERROR);
        $lateHmac = hash_hmac('sha256', $lateBody, $secret);

        $this->call('POST', '/api/v1/webhooks/payment/sepay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => $lateHmac,
        ], $lateBody)
            ->assertOk()
            ->assertJsonPath('data.order_status', 'refund_required')
            ->assertJsonPath('data.payment_status', 'paid_pending_refund');

        // Stock remains strictly 0 (zero phantom overselling!) and OrderRefundRequired outbox event is recorded
        $this->assertSame(0, (int) DB::table('inventory_stocks')->where('variant_id', $variantId)->value('available_stock'));
        $this->assertTrue(
            DB::table('outbox_messages')
                ->where('aggregate_id', $orderA)
                ->where('event_type', 'OrderRefundRequired')
                ->exists()
        );
    }

    public function test_product_review_marks_verified_purchase_accurately(): void
    {
        // Seeded customer purchased Urban Shell in DatabaseSeeder -> is_verified_purchase = true
        $buyerToken = (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'khachhang@cleancommerce.vn',
            'password' => 'Customer@123456',
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$buyerToken)
            ->postJson('/api/v1/catalog/products/ao-khoac-ky-thuat-urban-shell/reviews', [
                'rating' => 5,
                'comment' => 'Mình đặt thêm chiếc thứ hai tặng em trai, chất lượng xuất sắc!',
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_verified_purchase', true);

        // Newly registered user with zero orders -> is_verified_purchase = false
        $newUserToken = (string) $this->postJson('/api/v1/auth/register', [
            'name' => 'Ngô Gia Huy',
            'email' => 'giahuy@cleancommerce.vn',
            'password' => 'Secret@123',
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$newUserToken)
            ->postJson('/api/v1/catalog/products/ao-khoac-ky-thuat-urban-shell/reviews', [
                'rating' => 4,
                'comment' => 'Thiết kế tối giản rất đẹp mắt.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_verified_purchase', false);
    }
}
