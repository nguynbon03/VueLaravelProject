<?php

declare(strict_types=1);

namespace Database\Seeders;

final class CatalogSeedData
{
    /**
     * @return array<int, array{name: string, slug: string, description: string, sort_order: int}>
     */
    public static function categories(): array
    {
        return [
            [
                'name' => 'Thời trang kỹ thuật',
                'slug' => 'thoi-trang-ky-thuat',
                'description' => 'Trang phục tối giản chống thấm, thoáng khí cho nhịp sống đô thị hiện đại.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Phụ kiện công thái học',
                'slug' => 'phu-kien-cong-thai-hoc',
                'description' => 'Giải pháp nâng đỡ tư thế làm việc bền bỉ suốt ngày dài.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Balo & Túi chống nước',
                'slug' => 'balo-tui-chong-nuoc',
                'description' => 'Balo Cordura và vải phủ TPU bảo vệ thiết bị điện tử trong mọi thời tiết.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Thiết bị bàn làm việc',
                'slug' => 'thiet-bi-ban-lam-viec',
                'description' => 'Thiết bị chiếu sáng chuẩn màu và tổ chức góc làm việc tối ưu.',
                'sort_order' => 4,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function products(): array
    {
        return [
            [
                'category_slug' => 'thoi-trang-ky-thuat',
                'name' => 'Áo Khoác Kỹ Thuật Chống Nước Urban Shell',
                'slug' => 'ao-khoac-ky-thuat-urban-shell',
                'brand' => 'CleanWear',
                'short_description' => 'Áo khoác màng thở 3 lớp kháng nước 10.000mm, đường may ép nhiệt toàn phần.',
                'description' => 'Thiết kế dành riêng cho người di chuyển hàng ngày tại đô thị nhiệt đới. Vải dệt ripstop siêu nhẹ kết hợp khóa kéo chống nước YKK AquaGuard và mũ trùm chỉnh đa hướng.',
                'image_url' => 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?auto=format&fit=crop&w=1000&q=80',
                'is_featured' => true,
                'variants' => [
                    ['sku' => 'CW-SHELL-BLK-M', 'color' => 'Đen Than', 'size' => 'M', 'price_minor' => 890000, 'compare_at_minor' => 1090000, 'stock' => 15],
                    ['sku' => 'CW-SHELL-BLK-L', 'color' => 'Đen Than', 'size' => 'L', 'price_minor' => 890000, 'compare_at_minor' => 1090000, 'stock' => 12],
                    ['sku' => 'CW-SHELL-OLV-L', 'color' => 'Xanh Rêu', 'size' => 'L', 'price_minor' => 920000, 'compare_at_minor' => 1120000, 'stock' => 3],
                ],
            ],
            [
                'category_slug' => 'thoi-trang-ky-thuat',
                'name' => 'Quần Cargo Cơ Động Stretch Ripstop',
                'slug' => 'quan-cargo-co-dong-stretch-ripstop',
                'brand' => 'CleanWear',
                'short_description' => 'Quần dài co giãn 4 chiều trượt nước, 6 túi ẩn gọn gàng không phồng cộm.',
                'description' => 'Phom tapered hiện đại kết hợp sợi Nylon-Spandex thoáng mát, chống nhăn và khô nhanh trong 30 phút. Phù hợp đi làm lẫn dã ngoại cuối tuần.',
                'image_url' => 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?auto=format&fit=crop&w=1000&q=80',
                'is_featured' => false,
                'variants' => [
                    ['sku' => 'CW-CARGO-CHA-30', 'color' => 'Xám Chì', 'size' => '30', 'price_minor' => 690000, 'compare_at_minor' => 820000, 'stock' => 18],
                    ['sku' => 'CW-CARGO-CHA-32', 'color' => 'Xám Chì', 'size' => '32', 'price_minor' => 690000, 'compare_at_minor' => 820000, 'stock' => 14],
                ],
            ],
            [
                'category_slug' => 'phu-kien-cong-thai-hoc',
                'name' => 'Giá Đỡ Laptop Hợp Kim Nhôm AeroStand Pro',
                'slug' => 'gia-do-laptop-hop-kim-nhom-aerostand-pro',
                'brand' => 'ErgoCraft',
                'short_description' => 'Nhôm nguyên khối CNC anod hóa, nâng màn hình chuẩn tầm mắt giảm mỏi cổ vai gáy.',
                'description' => 'Bản lề lực siết kép chịu tải 8kg không rung lắc khi gõ phím nhanh, khe thoáng tản nhiệt đối lưu tự nhiên cho MacBook và Ultrabook 13–16 inch.',
                'image_url' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?auto=format&fit=crop&w=1000&q=80',
                'is_featured' => true,
                'variants' => [
                    ['sku' => 'EC-STAND-SLV-STD', 'color' => 'Bạc Ánh Kim', 'size' => 'Tiêu chuẩn', 'price_minor' => 650000, 'compare_at_minor' => 790000, 'stock' => 20],
                    ['sku' => 'EC-STAND-GRY-PRO', 'color' => 'Xám Không Gian', 'size' => 'Xoay 360', 'price_minor' => 790000, 'compare_at_minor' => 950000, 'stock' => 11],
                ],
            ],
            [
                'category_slug' => 'phu-kien-cong-thai-hoc',
                'name' => 'Kê Tay Bàn Phím Gỗ Óc Chó Bắc Mỹ',
                'slug' => 'ke-tay-ban-phim-go-oc-cho-bac-my',
                'brand' => 'ErgoCraft',
                'short_description' => 'Gỗ óc chó tự nhiên phủ dầu sáp ong thực vật, độ dốc 7 độ nâng đỡ cổ tay.',
                'description' => 'Gia công tỉ mỉ từng vân gỗ độc bản, đệm cao su chống trượt EPDM bên dưới bám chắc mặt bàn gỗ hoặc thảm nỉ.',
                'image_url' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=1000&q=80',
                'is_featured' => false,
                'variants' => [
                    ['sku' => 'EC-WRIST-CMP-65', 'color' => 'Nâu Óc Chó', 'size' => 'Layout 65%', 'price_minor' => 390000, 'compare_at_minor' => 450000, 'stock' => 19],
                    ['sku' => 'EC-WRIST-TKL-80', 'color' => 'Nâu Óc Chó', 'size' => 'Layout TKL', 'price_minor' => 420000, 'compare_at_minor' => 490000, 'stock' => 15],
                ],
            ],
            [
                'category_slug' => 'balo-tui-chong-nuoc',
                'name' => 'Balo Chống Nước Commuter Rolltop 24L',
                'slug' => 'balo-chong-nuoc-commuter-rolltop-24l',
                'brand' => 'NomadGear',
                'short_description' => 'Vải Cordura 900D phủ TPU chống mưa lớn, ngăn laptop 16 inch treo chống sốc đáy.',
                'description' => 'Nắp cuộn nam châm Fidlock thao tác một tay nhanh chóng, đệm lưng EVA rãnh thoáng khí và ngăn lấy nhanh thẻ xe, bình giữ nhiệt hai bên.',
                'image_url' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=1000&q=80',
                'is_featured' => true,
                'variants' => [
                    ['sku' => 'NG-PACK-OBS-24L', 'color' => 'Đen Obsidian', 'size' => '24L', 'price_minor' => 1450000, 'compare_at_minor' => 1690000, 'stock' => 11],
                    ['sku' => 'NG-PACK-SLT-24L', 'color' => 'Xám Đá Phiến', 'size' => '24L', 'price_minor' => 1450000, 'compare_at_minor' => 1690000, 'stock' => 8],
                ],
            ],
            [
                'category_slug' => 'thiet-bi-ban-lam-viec',
                'name' => 'Đèn Treo Màn Hình Chống Chói LumenBar Halo',
                'slug' => 'den-treo-man-hinh-chong-choi-lumenbar-halo',
                'brand' => 'DeskLab',
                'short_description' => 'Quang học bất đối xứng không hắt sáng màn hình, chỉ số hoàn màu Ra97 chuẩn thiết kế.',
                'description' => 'Điểm kèm núm xoay không dây điều chỉnh vô cấp nhiệt độ màu 2700K–6500K và độ sáng, tích hợp đèn nền hắt tường dịu mắt khi làm việc ban đêm.',
                'image_url' => 'https://images.unsplash.com/photo-1593640408182-31c70c8268f5?auto=format&fit=crop&w=1000&q=80',
                'is_featured' => true,
                'variants' => [
                    ['sku' => 'DL-BAR-BLK-CRI97', 'color' => 'Đen Mờ', 'size' => '50cm', 'price_minor' => 1190000, 'compare_at_minor' => 1390000, 'stock' => 14],
                    ['sku' => 'DL-BAR-WHT-CRI97', 'color' => 'Trắng Sương', 'size' => '50cm', 'price_minor' => 1250000, 'compare_at_minor' => 1450000, 'stock' => 9],
                ],
            ],
        ];
    }
}
