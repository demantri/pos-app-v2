<?php

namespace App\Support;

class DemoData
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function stores(): array
    {
        return array_map(static function (array $store): array {
            $store['products_count'] = count(self::products($store['id']));

            return $store;
        }, [
            [
                'id' => 1,
                'name' => 'Toko Sudirman',
                'code' => 'SDR',
                'address' => 'Jl. Jend. Sudirman No. 12, Jakarta Selatan',
                'phone' => '021-5550112',
                'is_active' => true,
                'products_count' => 0,
            ],
            [
                'id' => 2,
                'name' => 'Toko Kelapa Dua',
                'code' => 'KLD',
                'address' => 'Jl. Boulevard Raya No. 5, Tangerang',
                'phone' => '021-5550287',
                'is_active' => true,
                'products_count' => 0,
            ],
            [
                'id' => 3,
                'name' => 'Toko Serpong',
                'code' => 'SPG',
                'address' => 'Jl. Raya Serpong No. 88, Tangerang Selatan',
                'phone' => '021-5550431',
                'is_active' => false,
                'products_count' => 0,
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function storeOptions(): array
    {
        return array_map(
            static fn (array $store): array => [
                'id' => $store['id'],
                'name' => $store['name'],
                'code' => $store['code'],
            ],
            self::stores(),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function store(int $id): ?array
    {
        foreach (self::stores() as $store) {
            if ($store['id'] === $id) {
                return $store;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function categories(int $storeId): array
    {
        $names = self::categoryNames($storeId);

        if ($names === []) {
            return [];
        }

        $categories = [];

        foreach (array_values($names) as $index => $name) {
            $categories[] = [
                'id' => ($storeId * 100) + $index + 1,
                'name' => $name,
                'description' => 'Kelompok produk '.mb_strtolower($name),
                'products_count' => 0,
            ];
        }

        $products = self::buildProducts($storeId, $categories);

        return array_map(static function (array $category) use ($products): array {
            $category['products_count'] = count(array_filter(
                $products,
                static fn (array $product): bool => $product['category_id'] === $category['id'],
            ));

            return $category;
        }, $categories);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function products(int $storeId): array
    {
        $names = self::categoryNames($storeId);

        if ($names === []) {
            return [];
        }

        $categories = [];

        foreach (array_values($names) as $index => $name) {
            $categories[] = [
                'id' => ($storeId * 100) + $index + 1,
                'name' => $name,
            ];
        }

        return self::buildProducts($storeId, $categories);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function transactions(int $storeId): array
    {
        $products = self::products($storeId);

        if ($products === []) {
            return [];
        }

        $methods = ['tunai', 'kartu', 'qris'];
        $cashiers = ['Dede', 'Rani', 'Bagas'];
        $transactions = [];

        for ($i = 0; $i < 10; $i++) {
            $items = [];
            $itemCount = ($i % 3) + 1;

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[($i * 3 + $j) % count($products)];
                $qty = ($j % 2) + 1;
                $discount = $j === 2 ? 1000 : 0;

                $items[] = [
                    'name' => $product['name'],
                    'qty' => $qty,
                    'price' => $product['price'],
                    'discount' => $discount,
                    'subtotal' => ($product['price'] * $qty) - $discount,
                ];
            }

            $transactions[] = [
                'id' => $i + 1,
                'number' => sprintf('%s-%04d', self::storeCode($storeId), 1001 + $i),
                'created_at' => sprintf('2026-08-24 %02d:%02d:00', 9 + $i, ($i * 7) % 60),
                'cashier' => $cashiers[$i % count($cashiers)],
                'items_count' => count($items),
                'total' => array_sum(array_column($items, 'subtotal')),
                'payment_method' => $methods[$i % count($methods)],
                'items' => $items,
            ];
        }

        return $transactions;
    }

    /**
     * @return array<string, mixed>
     */
    public static function dashboard(int $storeId): array
    {
        $transactions = self::transactions($storeId);
        $total = array_sum(array_column($transactions, 'total'));
        $count = count($transactions);

        return [
            'sales_today' => $total,
            'transactions_today' => $count,
            'items_sold' => array_sum(array_map(
                static fn (array $transaction): int|float => array_sum(array_column($transaction['items'], 'qty')),
                $transactions,
            )),
            'average_per_transaction' => $count > 0 ? (int) round($total / $count) : 0,
            'recent_transactions' => array_slice(array_reverse($transactions), 0, 5),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function settings(int $storeId): array
    {
        $store = self::store($storeId) ?? [
            'name' => '',
            'code' => '',
            'address' => '',
            'phone' => '',
            'is_active' => false,
        ];

        return [
            'name' => $store['name'],
            'code' => $store['code'],
            'address' => $store['address'],
            'phone' => $store['phone'],
            'currency' => 'IDR',
            'tax_percent' => 11,
            'rounding' => 100,
            'receipt_header' => $store['name'],
            'receipt_footer' => 'Terima kasih telah berbelanja',
            'paper_size' => '58mm',
            'open_time' => '08:00',
            'close_time' => '21:00',
            'is_active' => $store['is_active'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function categoryNames(int $storeId): array
    {
        return match ($storeId) {
            1 => ['Minuman', 'Makanan', 'Snack', 'Rokok', 'Kebutuhan Rumah', 'Perawatan Diri'],
            2 => ['Minuman', 'Makanan', 'Snack', 'Alat Tulis', 'Mainan'],
            3 => ['Minuman', 'Makanan', 'Snack', 'Kebutuhan Rumah', 'Obat'],
            default => [],
        };
    }

    /**
     * Kode toko TIDAK boleh dibaca lewat self::store(), karena stores() sendiri
     * memanggil products() untuk menghitung products_count — itu akan menjadi
     * rekursi tak berujung. Karena itu kode toko dipetakan langsung di sini.
     */
    private static function storeCode(int $storeId): string
    {
        return match ($storeId) {
            1 => 'SDR',
            2 => 'KLD',
            3 => 'SPG',
            default => 'STR',
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $categories
     * @return array<int, array<string, mixed>>
     */
    private static function buildProducts(int $storeId, array $categories): array
    {
        $itemNames = [
            'Kopi Susu Gula Aren', 'Teh Kotak Original', 'Air Mineral 600ml', 'Susu UHT Cokelat',
            'Roti Bakar Cokelat', 'Mie Instan Goreng', 'Nasi Ayam Geprek', 'Sereal Madu',
            'Keripik Kentang', 'Biskuit Kelapa', 'Wafer Vanila', 'Permen Mint',
            'Sabun Cair 250ml', 'Pasta Gigi 120g', 'Tisu Wajah 200 lembar', 'Deterjen Bubuk 800g',
            'Pulpen Hitam', 'Buku Tulis 38 Lembar', 'Penghapus Karet', 'Spidol Papan Tulis',
            'Minyak Goreng 1L', 'Gula Pasir 1kg', 'Beras Premium 5kg', 'Kecap Manis 275ml',
            'Sambal Sachet', 'Kopi Hitam Sachet', 'Es Krim Cup', 'Yogurt Stroberi',
        ];

        $units = ['pcs', 'botol', 'bungkus', 'kotak', 'sachet'];
        $products = [];
        $total = 20 + $storeId * 2;

        for ($i = 0; $i < $total; $i++) {
            $category = $categories[$i % count($categories)];
            $name = $itemNames[$i % count($itemNames)];
            $sequence = $i + 1;

            $products[] = [
                'id' => ($storeId * 1000) + $sequence,
                'name' => $name,
                'sku' => sprintf('%s-%03d', self::storeCode($storeId), $sequence),
                'barcode' => (string) (8991000000000 + ($storeId * 1000) + $sequence),
                'category_id' => $category['id'],
                'category' => $category['name'],
                'price' => 3000 + (($i % 12) * 2500),
                'stock' => 4 + (($i * 7) % 120),
                'unit' => $units[$i % count($units)],
                'is_active' => $i % 11 !== 0,
            ];
        }

        return $products;
    }
}
