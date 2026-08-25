<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Support\DemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Memindahkan transaksi + item dari App\Support\DemoData ke database.
 *
 * DemoData::transactions() hanya menyimpan `total` per transaksi, sedangkan
 * tabel `transactions` juga punya subtotal/discount/tax/paid/change (semua
 * NOT NULL). Kolom-kolom itu DIHITUNG ULANG di sini memakai rumus yang
 * SAMA PERSIS dengan resources/js/lib/cart.ts (cartTotals()/roundTo()),
 * supaya angka di dashboard/struk hasil seed konsisten dengan transaksi
 * yang nanti dibuat lewat checkout sungguhan:
 *
 *   1. subtotal   = jumlah subtotal tiap baris, dan subtotal baris
 *                   di-clamp max(0, price*qty - discount) — BUKAN rumus
 *                   DemoData yang tidak di-clamp.
 *   2. discount   = 0 (DemoData tidak punya diskon tingkat transaksi),
 *                   diklem ke [0, subtotal] sesuai cart.ts (hasilnya tetap 0).
 *   3. taxable    = subtotal - discount
 *   4. tax        = round(taxable * tax_percent / 100)
 *   5. total      = taxable === 0 ? 0 : roundTo(taxable + tax, rounding)
 *   6. paid       = total dibulatkan ke atas ke kelipatan Rp 5.000
 *                   (>= total, jadi change tidak pernah negatif)
 *   7. change     = paid - total
 *
 * Idempoten: transaksi di-upsert lewat (store_id, number); item transaksi
 * dihapus lalu ditulis ulang setiap kali seeder jalan (komposisinya
 * deterministik dari DemoData, jadi tidak pernah menggandakan baris).
 */
class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Store::query()->orderBy('id')->get() as $store) {
            $demoStoreId = $this->demoStoreIdFor($store->code);

            if ($demoStoreId === null) {
                continue;
            }

            /** @var array<string, int> $productIdsByName */
            $productIdsByName = Product::query()
                ->where('store_id', $store->id)
                ->pluck('id', 'name')
                ->all();

            foreach (DemoData::transactions($demoStoreId) as $txData) {
                $this->seedTransaction($store, $txData, $productIdsByName);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $txData
     * @param  array<string, int>  $productIdsByName
     */
    private function seedTransaction(Store $store, array $txData, array $productIdsByName): void
    {
        $items = array_map(static function (array $item): array {
            return [
                'name' => $item['name'],
                'qty' => $item['qty'],
                'price' => $item['price'],
                'discount' => $item['discount'],
                // max(0, price*qty - discount) — versi cart.ts::lineSubtotal(),
                // bukan versi DemoData yang tidak di-clamp.
                'subtotal' => max(0, ($item['price'] * $item['qty']) - $item['discount']),
            ];
        }, $txData['items']);

        $subtotal = array_sum(array_column($items, 'subtotal'));
        // cart.ts::cartTotals() mengklem diskon transaksi ke [0, subtotal].
        // DemoData tidak punya diskon transaksi, jadi rawDiscount = 0 dan
        // hasil klemnya selalu 0 juga — ditulis eksplisit di sini supaya
        // tetap terbaca sebagai "rumus yang sama", bukan angka ajaib.
        $rawDiscount = 0;
        $discount = min(max(0, $rawDiscount), $subtotal);
        $taxable = $subtotal - $discount;
        $tax = (int) round($taxable * $store->tax_percent / 100);
        $total = $taxable === 0 ? 0 : $this->roundTo($taxable + $tax, $store->rounding);
        $paid = $total === 0 ? 0 : (int) (ceil($total / 5000) * 5000);
        $change = $paid - $total;

        $transaction = Transaction::updateOrCreate(
            ['store_id' => $store->id, 'number' => $txData['number']],
            [
                'user_id' => null,
                'cashier_name' => $txData['cashier'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'paid' => $paid,
                'change' => $change,
                'payment_method' => $txData['payment_method'],
            ],
        );

        // created_at bukan bagian dari $fillable Transaction (bukan disengaja
        // diisi lewat mass assignment saat runtime aplikasi), jadi disetel
        // eksplisit di sini supaya waktu transaksi seed sama dengan DemoData.
        $transaction->created_at = Carbon::parse($txData['created_at']);
        $transaction->save();

        $transaction->items()->delete();

        $transaction->items()->createMany(array_map(
            static fn (array $item): array => [
                'product_id' => $productIdsByName[$item['name']] ?? null,
                'name' => $item['name'],
                'qty' => $item['qty'],
                'price' => $item['price'],
                'discount' => $item['discount'],
                'subtotal' => $item['subtotal'],
            ],
            $items,
        ));
    }

    private function roundTo(int $value, int $step): int
    {
        if ($step <= 1) {
            return (int) round($value);
        }

        return (int) (round($value / $step) * $step);
    }

    private function demoStoreIdFor(string $code): ?int
    {
        return match ($code) {
            'SDR' => 1,
            'KLD' => 2,
            'SPG' => 3,
            default => null,
        };
    }
}
