<?php

namespace Tests\Unit\Printing;

use App\Models\Store;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Printing\ReceiptLayout;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Lebar kolom nota adalah tempat bug bersembunyi diam-diam: kalau satu baris
 * kelebihan satu karakter, printer 58mm membungkusnya dan struk jadi
 * berantakan — dan itu baru ketahuan dari kertas, bukan dari layar.
 *
 * Test ini murni teks: modelnya tidak pernah disimpan dan tidak ada printer
 * yang disentuh — aplikasi hanya di-boot supaya Eloquent bisa dipakai
 * sebagai wadah data.
 */
class ReceiptLayoutTest extends TestCase
{
    private function store(string $paperSize = '58mm'): Store
    {
        return new Store([
            'name' => 'Toko Sudirman',
            'code' => 'SDR',
            'address' => 'Jl. Jend. Sudirman No. 12, Jakarta Selatan',
            'phone' => '021-5550112',
            'tax_percent' => 11,
            'rounding' => 100,
            'receipt_header' => 'Toko Sudirman',
            'receipt_footer' => 'Terima kasih telah berbelanja',
            'paper_size' => $paperSize,
        ]);
    }

    private function transaction(): Transaction
    {
        $transaction = new Transaction([
            'number' => 'SDR-1011',
            'cashier_name' => 'Rani',
            'subtotal' => 18500,
            'discount' => 1000,
            'tax' => 1925,
            'total' => 19400,
            'paid' => 50000,
            'change' => 30600,
            'payment_method' => 'tunai',
        ]);

        $transaction->created_at = Carbon::parse('2026-08-29 06:05:00');

        $transaction->setRelation('items', collect([
            new TransactionItem(['name' => 'Teh Kotak Original', 'qty' => 2, 'price' => 5500, 'discount' => 0, 'subtotal' => 11000]),
            new TransactionItem(['name' => 'Air Mineral 600ml', 'qty' => 1, 'price' => 8000, 'discount' => 500, 'subtotal' => 7500]),
        ]));

        return $transaction;
    }

    public function test_no_line_is_wider_than_the_paper(): void
    {
        foreach (['58mm' => 32, '80mm' => 48] as $paperSize => $width) {
            $lines = (new ReceiptLayout($this->store($paperSize)))->forTransaction($this->transaction());

            foreach ($lines as $line) {
                $this->assertLessThanOrEqual(
                    $width,
                    mb_strlen($line),
                    "Baris '{$line}' melebihi {$width} kolom pada kertas {$paperSize}.",
                );
            }
        }
    }

    public function test_receipt_carries_the_numbers_a_customer_checks(): void
    {
        $text = implode("\n", (new ReceiptLayout($this->store()))->forTransaction($this->transaction()));

        $this->assertStringContainsString('SDR-1011', $text);
        $this->assertStringContainsString('Rani', $text);
        $this->assertStringContainsString('29/08/2026 06:05', $text);
        $this->assertStringContainsString('Teh Kotak Original', $text);
        $this->assertStringContainsString('2 x 5.500', $text);
        $this->assertStringContainsString('19.400', $text);
        $this->assertStringContainsString('30.600', $text);
        $this->assertStringContainsString('Terima kasih', $text);
        // Baris penutup aplikasi ikut di setiap nota, apa pun isi footer toko.
        $this->assertStringContainsString('Powered by DeePOS', $text);
    }

    public function test_long_product_name_wraps_instead_of_being_cut(): void
    {
        $transaction = $this->transaction();
        $transaction->setRelation('items', collect([
            new TransactionItem([
                'name' => 'Minyak Goreng Kelapa Sawit Kemasan Botol 2 Liter',
                'qty' => 1,
                'price' => 38000,
                'discount' => 0,
                'subtotal' => 38000,
            ]),
        ]));

        $lines = (new ReceiptLayout($this->store()))->forTransaction($transaction);
        $text = implode(' ', $lines);

        // Seluruh kata masih ada, hanya terpecah ke beberapa baris.
        $this->assertStringContainsString('Minyak Goreng Kelapa', $text);
        $this->assertStringContainsString('2 Liter', $text);

        foreach ($lines as $line) {
            $this->assertLessThanOrEqual(32, mb_strlen($line));
        }
    }
}
