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
            ->get(['stores.id', 'stores.name', 'stores.code'])
            ->map(static fn (Store $store): array => [
                'id' => $store->id,
                'name' => $store->name,
                'code' => $store->code,
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
                'id' => $user->id,
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
            'id' => $store->id,
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
                'id' => $category->id,
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
            ->with('category:id,name')
            ->orderBy('id')
            ->get()
            ->map(static fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode ?? '',
                // Bisa null: menghapus kategori melepas pengelompokan produk
                // (nullOnDelete), tidak menghapus produknya.
                'category_id' => $product->category_id,
                'category' => $product->category?->name ?? self::UNCATEGORIZED,
                'price' => $product->price,
                'stock' => $product->stock,
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
        /** @var Collection<int, Transaction> $today */
        $today = $store->transactions()
            ->whereDate('created_at', today())
            ->get(['id', 'total']);

        $count = $today->count();
        $total = (int) $today->sum('total');

        $itemsSold = (int) TransactionItem::query()
            ->whereIn('transaction_id', $today->pluck('id'))
            ->sum('qty');

        return [
            'sales_today' => $total,
            'transactions_today' => $count,
            'items_sold' => $itemsSold,
            'average_per_transaction' => $count > 0 ? (int) round($total / $count) : 0,
            'recent_transactions' => self::historyQuery($store)
                ->limit(self::RECENT_LIMIT)
                ->get()
                ->map(static fn (Transaction $transaction): array => self::transaction($transaction))
                ->all(),
        ];
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
            'id' => $transaction->id,
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
