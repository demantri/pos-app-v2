<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sumber data halaman toko, dibaca dari database.
 *
 * Pengganti App\Support\DemoData untuk jalur baca aplikasi (fase 2, T4).
 * Bentuk array yang dikembalikan sengaja dijaga sama persis dengan tipe di
 * resources/js/types/pos.ts, supaya seluruh halaman Vue tidak perlu diubah
 * ketika sumber datanya berpindah dari array statis ke Eloquent.
 *
 * DemoData sendiri BELUM dibuang: ia masih dipakai seeder sebagai sumber
 * data awal (database/seeders/StoreSeeder.php dan TransactionSeeder.php).
 */
class StoreData
{
    /**
     * Label untuk produk yang kategorinya sudah dihapus (`category_id` null).
     */
    public const UNCATEGORIZED = 'Tanpa kategori';

    /**
     * Berapa transaksi terakhir yang ditampilkan di halaman riwayat.
     */
    private const HISTORY_LIMIT = 50;

    /**
     * Berapa transaksi terakhir yang ditampilkan di kartu dashboard.
     */
    private const RECENT_LIMIT = 5;

    /**
     * Berapa produk menipis yang dirinci di kartu dashboard. Jumlah totalnya
     * tetap dilaporkan utuh — yang dibatasi hanya daftarnya.
     */
    private const LOW_STOCK_LIMIT = 8;

    /**
     * Toko yang boleh dilihat user ini: owner melihat semua, selain owner
     * hanya toko tempat ia terdaftar sebagai admin/kasir.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function stores(User $viewer, bool $archived = false): array
    {
        $query = self::visibleQuery($viewer);

        if ($archived) {
            $query->onlyTrashed();
        }

        return $query
            ->withCount('products')
            ->orderBy('id')
            ->get()
            ->map(static fn (Store $store): array => self::store($store, $viewer))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function storeOptions(User $viewer): array
    {
        return self::visibleQuery($viewer)
            ->orderBy('id')
            ->get(['stores.id', 'stores.ulid', 'stores.name', 'stores.code'])
            ->map(static fn (Store $store): array => [
                'id' => $store->ulid,
                'name' => $store->name,
                'code' => $store->code,
                // Perannya ikut supaya pemilih toko tahu halaman mana yang
                // boleh dibuka di toko tujuan — akar toko 403 bagi kasir
                // maupun owner.
                'role' => $viewer->roleLabelFor($store),
            ])
            ->all();
    }

    /**
     * @return Builder<Store>
     */
    private static function visibleQuery(User $viewer)
    {
        $query = Store::query();

        if (! $viewer->isOwner()) {
            $query->whereHas('users', static fn ($members) => $members->whereKey($viewer->getKey()));
        }

        return $query;
    }

    /**
     * Anggota toko beserta rolenya (owner tidak muncul di sini — ia tidak
     * punya baris pivot; wewenangnya global).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function users(Store $store): array
    {
        return $store->users()
            ->orderBy('name')
            ->get()
            ->map(static fn (User $user): array => [
                'id' => $user->ulid,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot?->role,
                'joined_at' => $user->pivot?->created_at?->format('Y-m-d H:i:s') ?? '',
            ])
            ->all();
    }

    /**
     * Bentuk satu toko untuk shared prop `currentStore` dan daftar toko.
     *
     * @return array<string, mixed>
     */
    public static function store(Store $store, ?User $viewer = null): array
    {
        $viewer ??= auth()->user();

        return [
            // Yang keluar ke klien adalah ULID, bukan primary key berurut —
            // seluruh URL memakainya (lihat App\Concerns\HasUlidRouteKey).
            'id' => $store->ulid,
            'name' => $store->name,
            'code' => $store->code,
            'address' => $store->address,
            'phone' => $store->phone,
            'is_active' => $store->is_active,
            // products_count hanya ada bila query pemanggil memakai
            // withCount(); di jalur shared prop ia tidak ada, jadi dihitung
            // di tempat.
            'products_count' => (int) ($store->products_count ?? $store->products()->count()),
            // Peran user yang sedang melihat DI toko ini: owner (wewenang
            // global), admin, atau kasir. Dipakai UI untuk menyembunyikan
            // menu yang memang akan ditolak server.
            'role' => $viewer?->roleLabelFor($store),
            'is_archived' => $store->trashed(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function categories(Store $store): array
    {
        return $store->categories()
            ->withCount('products')
            ->orderBy('id')
            ->get()
            ->map(static fn (Category $category): array => [
                'id' => $category->ulid,
                'name' => $category->name,
                // Kolomnya nullable di database, tapi UI memperlakukan
                // deskripsi sebagai string biasa.
                'description' => $category->description ?? '',
                'products_count' => (int) $category->products_count,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function products(Store $store): array
    {
        return $store->products()
            ->with('category:id,ulid,name')
            ->orderBy('id')
            ->get()
            ->map(static fn (Product $product): array => [
                'id' => $product->ulid,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode ?? '',
                // Bisa null: menghapus kategori melepas pengelompokan produk
                // (nullOnDelete), tidak menghapus produknya.
                'category_id' => $product->category?->ulid,
                'category' => $product->category?->name ?? self::UNCATEGORIZED,
                'price' => $product->price,
                'stock' => $product->stock,
                'min_stock' => $product->min_stock,
                'unit' => $product->unit,
                'is_active' => $product->is_active,
                'image_url' => ProductImage::url($product->image_path),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function transactions(Store $store): array
    {
        return self::historyQuery($store)
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->map(static fn (Transaction $transaction): array => self::transaction($transaction))
            ->all();
    }

    /**
     * Dashboard kasir: angka yang sama dengan dashboard admin, tanpa grafik,
     * dengan sepuluh transaksi terakhir.
     *
     * @return array<string, mixed>
     */
    public static function cashierDashboard(Store $store): array
    {
        return [
            ...self::todayFigures($store),
            'low_stock_count' => self::lowStockQuery($store)->count(),
            'recent_transactions' => self::historyQuery($store)
                ->limit(10)
                ->get()
                ->map(static fn (Transaction $transaction): array => self::transaction($transaction))
                ->all(),
        ];
    }

    /**
     * Angka kartu dashboard.
     *
     * `*_today` benar-benar berarti hari ini — jadi kartu bernilai nol
     * sampai ada transaksi hari ini, termasuk pada database hasil seed yang
     * tanggal transaksinya sengaja tetap. `recent_transactions` tidak
     * dibatasi tanggal supaya dashboard tetap memperlihatkan riwayat.
     *
     * @return array<string, mixed>
     */
    public static function dashboard(Store $store): array
    {
        $lowStockQuery = self::lowStockQuery($store);

        return [
            ...self::todayFigures($store),
            'charts' => [
                'daily' => self::dailySeries($store),
                'top_products' => self::topProducts($store),
                'hourly' => self::hourlySeries($store),
            ],
            'low_stock_count' => (clone $lowStockQuery)->count(),
            'low_stock' => $lowStockQuery
                ->limit(self::LOW_STOCK_LIMIT)
                ->get(['id', 'ulid', 'name', 'stock', 'min_stock', 'unit'])
                ->map(static fn (Product $product): array => [
                    'id' => $product->ulid,
                    'name' => $product->name,
                    'stock' => $product->stock,
                    'min_stock' => $product->min_stock,
                    'unit' => $product->unit,
                ])
                ->all(),
            'recent_transactions' => self::historyQuery($store)
                ->limit(self::RECENT_LIMIT)
                ->get()
                ->map(static fn (Transaction $transaction): array => self::transaction($transaction))
                ->all(),
        ];
    }

    /**
     * Angka hari ini — dipakai dashboard admin maupun kasir.
     *
     * @return array<string, mixed>
     */
    private static function todayFigures(Store $store): array
    {
        /** @var Collection<int, Transaction> $today */
        $today = $store->transactions()
            ->whereDate('created_at', today())
            ->get(['id', 'total']);

        $count = $today->count();
        $total = (int) $today->sum('total');

        return [
            'sales_today' => $total,
            'transactions_today' => $count,
            'items_sold' => (int) TransactionItem::query()
                ->whereIn('transaction_id', $today->pluck('id'))
                ->sum('qty'),
            'average_per_transaction' => $count > 0 ? (int) round($total / $count) : 0,
        ];
    }

    /**
     * Produk yang stoknya sudah menyentuh ambang peringatan tokonya.
     *
     * min_stock 0 berarti tidak diawasi, jadi dikecualikan — kalau tidak,
     * semua produk habis ikut terhitung menipis.
     *
     * @return HasMany<Product, Store>
     */
    private static function lowStockQuery(Store $store)
    {
        return $store->products()
            ->where('is_active', true)
            ->where('min_stock', '>', 0)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock');
    }

    /**
     * Omzet dan jumlah transaksi per hari selama dua minggu terakhir.
     *
     * Hari tanpa transaksi tetap diisi nol — kalau dilewati, garisnya akan
     * melompati tanggal dan memberi kesan tren yang keliru.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function dailySeries(Store $store, int $days = 14): array
    {
        $start = today()->subDays($days - 1);

        $rows = $store->transactions()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, SUM(total) as total, COUNT(*) as jumlah')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $series = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->addDays($i);
            $row = $rows->get($date->toDateString());

            $series[] = [
                'label' => $date->format('d/m'),
                'total' => (int) ($row->total ?? 0),
                'count' => (int) ($row->jumlah ?? 0),
            ];
        }

        return $series;
    }

    /**
     * Sepuluh produk dengan item terjual terbanyak sebulan terakhir.
     *
     * Dikelompokkan berdasarkan NAMA snapshot di item transaksi, bukan
     * product_id: produk yang sudah dihapus tetap terhitung penjualannya.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function topProducts(Store $store, int $limit = 10, int $days = 30): array
    {
        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.store_id', $store->id)
            ->where('transactions.created_at', '>=', today()->subDays($days - 1))
            ->groupBy('transaction_items.name')
            ->orderByDesc('qty')
            ->limit($limit)
            ->get([
                DB::raw('transaction_items.name as name'),
                DB::raw('SUM(transaction_items.qty) as qty'),
            ])
            ->map(static fn ($row): array => [
                'label' => $row->name,
                'qty' => (int) $row->qty,
            ])
            ->all();
    }

    /**
     * Omzet hari ini per jam, sepanjang jam buka toko.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function hourlySeries(Store $store): array
    {
        $rows = $store->transactions()
            ->whereDate('created_at', today())
            ->selectRaw('HOUR(created_at) as jam, SUM(total) as total')
            ->groupBy('jam')
            ->pluck('total', 'jam');

        $open = (int) mb_substr($store->open_time, 0, 2);
        $close = (int) mb_substr($store->close_time, 0, 2);

        // Jam tutup yang lebih awal dari jam buka (atau setelan kosong) tidak
        // bisa dijadikan rentang; jatuh kembali ke sehari penuh.
        if ($close <= $open) {
            $open = 0;
            $close = 23;
        }

        // Penjualan di luar jam buka tetap harus muncul: kasir yang melayani
        // lewat jam tutup bukan alasan angkanya hilang dari grafik. Tanpa ini,
        // jumlah batangnya tidak akan cocok dengan kartu "penjualan hari ini".
        if ($rows->isNotEmpty()) {
            $open = min($open, (int) $rows->keys()->min());
            $close = max($close, (int) $rows->keys()->max());
        }

        $series = [];

        for ($hour = $open; $hour <= $close; $hour++) {
            $series[] = [
                'label' => sprintf('%02d', $hour),
                'total' => (int) ($rows[$hour] ?? 0),
            ];
        }

        return $series;
    }

    /**
     * @return array<string, mixed>
     */
    public static function settings(Store $store): array
    {
        return [
            'name' => $store->name,
            'code' => $store->code,
            'address' => $store->address,
            'phone' => $store->phone,
            'currency' => $store->currency,
            'tax_percent' => $store->tax_percent,
            'rounding' => $store->rounding,
            'receipt_header' => $store->receipt_header ?? '',
            'receipt_footer' => $store->receipt_footer ?? '',
            'paper_size' => $store->paper_size,
            'open_time' => $store->open_time,
            'close_time' => $store->close_time,
            'is_active' => $store->is_active,
            'printer_connector' => $store->printer_connector,
            'printer_target' => $store->printer_target,
            'printer_channel' => $store->printer_channel,
            'printer_feed_lines' => $store->printer_feed_lines,
            'printer_auto_print' => $store->printer_auto_print,
        ];
    }

    /**
     * Transaksi terbaru lebih dulu. Urutan kedua memakai id supaya transaksi
     * yang lahir pada detik yang sama tetap punya urutan yang pasti.
     *
     * @return HasMany<Transaction, Store>
     */
    private static function historyQuery(Store $store)
    {
        return $store->transactions()
            ->with('items')
            ->withCount('items')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * @return array<string, mixed>
     */
    private static function transaction(Transaction $transaction): array
    {
        return [
            'id' => $transaction->ulid,
            'number' => $transaction->number,
            // formatDateTime() di klien menerima 'Y-m-d H:i:s' maupun ISO.
            'created_at' => $transaction->created_at?->format('Y-m-d H:i:s') ?? '',
            'cashier' => $transaction->cashier_name,
            'items_count' => (int) $transaction->items_count,
            'total' => $transaction->total,
            'payment_method' => $transaction->payment_method,
            'items' => $transaction->items
                ->map(static fn (TransactionItem $item): array => [
                    'name' => $item->name,
                    'qty' => $item->qty,
                    'price' => $item->price,
                    'discount' => $item->discount,
                    'subtotal' => $item->subtotal,
                ])
                ->all(),
        ];
    }
}
