<?php

namespace App\Actions\Pos;

use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use App\Support\CartMath;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Menyimpan satu transaksi POS: menulis transaksi + itemnya dan memotong
 * stok, seluruhnya dalam satu DB transaction.
 *
 * Aturan yang dipegang di sini, bukan di klien:
 *
 * - HARGA diambil dari record produk di database, BUKAN dari payload.
 *   `items.*.price` yang dikirim klien hanya echo tampilan; memercayainya
 *   berarti membuka price-tampering.
 * - Produk harus milik toko yang sedang dibuka (dijaga juga oleh aturan
 *   `exists` di CheckoutRequest) dan harus aktif.
 * - Stok harus cukup. Baris produk dikunci (lockForUpdate) supaya dua kasir
 *   tidak bisa menjual unit terakhir yang sama.
 * - Nominal bayar tidak boleh kurang dari total.
 */
class ProcessCheckout
{
    /**
     * @param  array{items: array<int, array<string, mixed>>, discount: int, payment_method: string, paid: int}  $data
     */
    public function handle(Store $store, array $data, User $cashier): Transaction
    {
        return DB::transaction(function () use ($store, $data, $cashier): Transaction {
            // Mengunci baris toko menyerialkan checkout per toko: penomoran
            // struk berikutnya dan pemotongan stok tidak berebut dengan
            // kasir lain di toko yang sama.
            $store = Store::query()->whereKey($store->getKey())->lockForUpdate()->firstOrFail();

            $lines = $this->buildLines($store, $data['items']);

            $totals = CartMath::totals(
                (int) array_sum(array_column($lines, 'subtotal')),
                (int) $data['discount'],
                $store->tax_percent,
                $store->rounding,
            );

            $paid = (int) $data['paid'];

            if ($paid < $totals['total']) {
                throw ValidationException::withMessages([
                    'paid' => 'Nominal bayar kurang dari total tagihan.',
                ]);
            }

            $transaction = Transaction::create([
                'store_id' => $store->id,
                'user_id' => $cashier->id,
                // Snapshot: struk yang sudah tercetak tidak boleh berubah
                // ketika nama kasir diganti atau akunnya dihapus.
                'cashier_name' => $cashier->name,
                'number' => $this->nextNumber($store),
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'tax' => $totals['tax'],
                'total' => $totals['total'],
                'paid' => $paid,
                'change' => $paid - $totals['total'],
                'payment_method' => $data['payment_method'],
            ]);

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];

                $transaction->items()->create([
                    'product_id' => $product->id,
                    // Snapshot nama & harga saat transaksi terjadi.
                    'name' => $product->name,
                    'qty' => $line['qty'],
                    'price' => $line['price'],
                    'discount' => $line['discount'],
                    'subtotal' => $line['subtotal'],
                ]);

                $product->decrement('stock', $line['qty']);
            }

            return $transaction;
        });
    }

    /**
     * Menyusun baris keranjang dari payload: qty digabung per produk, harga
     * diambil dari database, stok & status aktif diperiksa.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{product: Product, qty: int, price: int, discount: int, subtotal: int}>
     */
    private function buildLines(Store $store, array $items): array
    {
        /** @var array<int, array{qty: int, discount: int}> $merged */
        $merged = [];

        // Klien bisa mengirim produk yang sama lebih dari sekali; digabung
        // dulu supaya pemeriksaan stok memakai total qty yang sebenarnya.
        foreach ($items as $item) {
            $productId = (int) $item['product_id'];

            $merged[$productId] = [
                'qty' => ($merged[$productId]['qty'] ?? 0) + (int) $item['qty'],
                'discount' => ($merged[$productId]['discount'] ?? 0) + max(0, (int) $item['discount']),
            ];
        }

        $products = Product::query()
            ->where('store_id', $store->id)
            ->whereIn('id', array_keys($merged))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $lines = [];

        foreach ($merged as $productId => $row) {
            $product = $products->get($productId);

            if (! $product instanceof Product) {
                throw ValidationException::withMessages([
                    'items' => 'Ada produk di keranjang yang bukan milik toko ini.',
                ]);
            }

            if (! $product->is_active) {
                throw ValidationException::withMessages([
                    'items' => "Produk {$product->name} sudah tidak aktif.",
                ]);
            }

            if ($product->stock < $row['qty']) {
                throw ValidationException::withMessages([
                    'items' => "Stok {$product->name} tinggal {$product->stock} {$product->unit}.",
                ]);
            }

            $price = $product->price;
            $discount = min($row['discount'], $price * $row['qty']);

            $lines[] = [
                'product' => $product,
                'qty' => $row['qty'],
                'price' => $price,
                'discount' => $discount,
                'subtotal' => CartMath::lineSubtotal($price, $row['qty'], $discount),
            ];
        }

        return $lines;
    }

    /**
     * Nomor struk berikutnya untuk toko ini: KODE-1001, KODE-1002, …
     *
     * Dihitung dari nomor terbesar yang sudah ada (bukan dari jumlah baris),
     * supaya transaksi yang dihapus tidak membuat nomor terpakai ulang.
     * Dipanggil hanya di dalam transaksi yang sudah mengunci baris toko.
     */
    private function nextNumber(Store $store): string
    {
        $prefix = $store->code.'-';

        $last = Transaction::query()
            ->where('store_id', $store->id)
            ->where('number', 'like', $prefix.'%')
            ->max('number');

        $sequence = $last === null
            ? 1001
            : ((int) mb_substr((string) $last, mb_strlen($prefix))) + 1;

        return sprintf('%s-%04d', $store->code, $sequence);
    }
}
