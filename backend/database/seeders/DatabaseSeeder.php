<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\SharedKernel\Application\Contracts\OutboxPort;
use App\Models\User;
use App\Modules\Inventory\Domain\Contracts\InventoryReservationPort;
use App\Modules\Ordering\Application\Actions\PlaceOrderAction;
use App\Modules\Ordering\Application\DTOs\PlaceOrderCommand;
use App\Modules\Ordering\Domain\ValueObjects\CustomerSnapshot;
use App\Modules\Payment\Application\Actions\SimulateDevWebhookAction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Seed Users (Admin + Customer)
        User::query()->updateOrCreate(
            ['email' => 'admin@cleancommerce.vn'],
            [
                'name' => 'Quản Trị Viên CleanCommerce',
                'password' => 'Admin@123456',
                'role' => 'admin',
                'phone' => '0909888999',
                'email_verified_at' => now(),
            ]
        );

        $customer = User::query()->updateOrCreate(
            ['email' => 'khachhang@cleancommerce.vn'],
            [
                'name' => 'Nguyễn Minh Quân',
                'password' => 'Customer@123456',
                'role' => 'customer',
                'phone' => '0901234567',
                'email_verified_at' => now(),
            ]
        );

        // 2. Seed Categories
        $now = now();
        $categoryIdsBySlug = [];
        foreach (CatalogSeedData::categories() as $cat) {
            DB::table('catalog_categories')->updateOrInsert(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'sort_order' => $cat['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $categoryIdsBySlug[$cat['slug']] = (int) DB::table('catalog_categories')
                ->where('slug', $cat['slug'])
                ->value('id');
        }

        // 3. Seed Products, Variants & Initial Inventory Stocks
        /** @var InventoryReservationPort $inventoryPort */
        $inventoryPort = app(InventoryReservationPort::class);
        $variantIdsBySku = [];
        $productIdsBySlug = [];

        foreach (CatalogSeedData::products() as $prod) {
            $categoryId = $categoryIdsBySlug[$prod['category_slug']];

            DB::table('catalog_products')->updateOrInsert(
                ['slug' => $prod['slug']],
                [
                    'category_id' => $categoryId,
                    'name' => $prod['name'],
                    'brand' => $prod['brand'],
                    'short_description' => $prod['short_description'],
                    'description' => $prod['description'],
                    'image_url' => $prod['image_url'],
                    'currency' => 'VND',
                    'is_featured' => $prod['is_featured'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $productId = (int) DB::table('catalog_products')->where('slug', $prod['slug'])->value('id');
            $productIdsBySlug[$prod['slug']] = $productId;

            foreach ($prod['variants'] as $v) {
                DB::table('catalog_variants')->updateOrInsert(
                    ['sku' => $v['sku']],
                    [
                        'product_id' => $productId,
                        'color' => $v['color'],
                        'size' => $v['size'],
                        'price_minor' => $v['price_minor'],
                        'compare_at_minor' => $v['compare_at_minor'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                $variantId = (int) DB::table('catalog_variants')->where('sku', $v['sku'])->value('id');
                $variantIdsBySku[$v['sku']] = $variantId;

                $inventoryPort->initializeStock($variantId, (int) $v['stock'], 'Tồn kho khởi tạo hệ thống');
            }
        }

        // 4. Seed Coupons
        $coupons = [
            [
                'code' => 'WELCOME10',
                'label' => 'Giảm 10% tối đa 150.000đ',
                'type' => 'percentage',
                'value' => 10,
                'max_discount_minor' => 150000,
                'min_subtotal_minor' => 300000,
                'usage_limit' => 500,
            ],
            [
                'code' => 'VIP200K',
                'label' => 'Giảm 200.000đ cho đơn từ 1.500.000đ',
                'type' => 'fixed',
                'value' => 200000,
                'max_discount_minor' => null,
                'min_subtotal_minor' => 1500000,
                'usage_limit' => 200,
            ],
            [
                'code' => 'FREESHIP',
                'label' => 'Miễn phí vận chuyển cho đơn từ 200.000đ',
                'type' => 'freeship',
                'value' => 0,
                'max_discount_minor' => null,
                'min_subtotal_minor' => 200000,
                'usage_limit' => 1000,
            ],
        ];

        foreach ($coupons as $coupon) {
            DB::table('promotion_coupons')->updateOrInsert(
                ['code' => $coupon['code']],
                array_merge($coupon, [
                    'used_count' => 0,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        // 5. Seed 2 Realistic Sample Orders + Settle Payment + Dispatch Outbox
        if ((int) DB::table('ordering_orders')->count() === 0) {
            /** @var PlaceOrderAction $placeOrder */
            $placeOrder = app(PlaceOrderAction::class);
            /** @var SimulateDevWebhookAction $simulateWebhook */
            $simulateWebhook = app(SimulateDevWebhookAction::class);
            /** @var OutboxPort $outbox */
            $outbox = app(OutboxPort::class);

            $order1 = $placeOrder->execute(new PlaceOrderCommand(
                customer: new CustomerSnapshot(
                    userId: (int) $customer->id,
                    name: (string) $customer->name,
                    email: (string) $customer->email,
                    phone: (string) $customer->phone,
                    shippingAddress: '120 Nguyễn Huệ, Quận 1',
                    shippingProvince: 'TP. Hồ Chí Minh',
                    note: 'Giao giờ hành chính tại quầy lễ tân',
                ),
                paymentMethod: 'vietqr',
                couponCode: 'WELCOME10',
                items: [
                    ['variant_id' => $variantIdsBySku['CW-SHELL-BLK-M'], 'quantity' => 1],
                    ['variant_id' => $variantIdsBySku['EC-STAND-SLV-STD'], 'quantity' => 1],
                ],
            ));
            $simulateWebhook->execute($order1['order_number']);

            $order2 = $placeOrder->execute(new PlaceOrderCommand(
                customer: new CustomerSnapshot(
                    userId: (int) $customer->id,
                    name: (string) $customer->name,
                    email: (string) $customer->email,
                    phone: (string) $customer->phone,
                    shippingAddress: '45 Lý Thường Kiệt, Quận Hoàn Kiếm',
                    shippingProvince: 'Hà Nội',
                    note: 'Gọi trước khi giao 15 phút',
                ),
                paymentMethod: 'vietqr',
                couponCode: 'VIP200K',
                items: [
                    ['variant_id' => $variantIdsBySku['NG-PACK-OBS-24L'], 'quantity' => 1],
                    ['variant_id' => $variantIdsBySku['EC-WRIST-CMP-65'], 'quantity' => 1],
                ],
            ));
            $simulateWebhook->execute($order2['order_number']);

            $outbox->dispatchPending(50);

            // 6. Seed Verified Product Reviews
            $seedReviews = [
                [
                    'product_id' => $productIdsBySlug['ao-khoac-ky-thuat-urban-shell'],
                    'user_id' => (int) $customer->id,
                    'user_name' => (string) $customer->name,
                    'rating' => 5,
                    'comment' => 'Áo nhẹ bất ngờ, đi dưới mưa rào Sài Gòn 20 phút bên trong vẫn khô ráo hoàn toàn.',
                    'is_verified_purchase' => true,
                ],
                [
                    'product_id' => $productIdsBySlug['gia-do-laptop-hop-kim-nhom-aerostand-pro'],
                    'user_id' => (int) $customer->id,
                    'user_name' => (string) $customer->name,
                    'rating' => 5,
                    'comment' => 'Nhôm dày dặn chắc chắn, đặt MacBook Pro 16 inch gõ phím không hề rung.',
                    'is_verified_purchase' => true,
                ],
                [
                    'product_id' => $productIdsBySlug['balo-chong-nuoc-commuter-rolltop-24l'],
                    'user_id' => (int) $customer->id,
                    'user_name' => (string) $customer->name,
                    'rating' => 5,
                    'comment' => 'Khoá nam châm mở rất tiện, ngăn laptop đệm dày yên tâm khi đi làm mỗi ngày.',
                    'is_verified_purchase' => true,
                ],
            ];

            foreach ($seedReviews as $rev) {
                DB::table('catalog_reviews')->insert(array_merge($rev, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }
}
