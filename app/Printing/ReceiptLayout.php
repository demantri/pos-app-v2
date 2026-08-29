<?php

namespace App\Printing;

use App\Models\Store;
use App\Models\Transaction;
use App\Models\TransactionItem;

/**
 * Menyusun isi nota sebagai teks polos, selebar kertas toko (58mm = 32
 * karakter, 80mm = 48).
 *
 * Sengaja tidak menyentuh printer sama sekali: yang keluar dari sini adalah
 * daftar baris siap cetak. Dengan begitu bentuk nota bisa diperiksa — dan
 * diuji — tanpa perangkat keras, dan ReceiptPrinter tinggal mengirimkannya.
 */
class ReceiptLayout
{
    /**
     * Baris penutup milik aplikasi, tercetak di SETIAP nota semua toko.
     *
     * Sengaja konstanta, bukan kolom setting: ini identitas aplikasi, bukan
     * teks toko. Admin toko mengatur kalimatnya sendiri lewat
     * `stores.receipt_footer`, dan baris ini selalu menyusul di bawahnya —
     * tidak ada field apa pun yang bisa mengubah atau menghapusnya.
     */
    public const BRANDING = 'Powered by DeePOS';

    public function __construct(private readonly Store $store) {}

    /**
     * Nota lengkap satu transaksi.
     *
     * @return array<int, string>
     */
    public function forTransaction(Transaction $transaction): array
    {
        return [
            ...$this->header(),
            ...$this->meta($transaction),
            ...$this->items($transaction),
            ...$this->totals($transaction),
            ...$this->footer(),
        ];
    }

    /**
     * Nota uji coba — dipakai tombol "Uji cetak" di Setting Toko untuk
     * memastikan printer dan lebar kertasnya benar tanpa perlu membuat
     * transaksi sungguhan.
     *
     * @return array<int, string>
     */
    public function testPage(): array
    {
        return [
            ...$this->header(),
            $this->center('UJI CETAK'),
            '',
            $this->twoColumns('Lebar kertas', $this->store->paper_size),
            $this->twoColumns('Kolom per baris', (string) $this->width()),
            $this->twoColumns('Koneksi', $this->store->printer_connector),
            'Tujuan:',
            // Tujuan printer (path device / nama antrian) bisa panjang;
            // dibungkus ke baris sendiri supaya labelnya tidak tergusur.
            ...$this->wrap($this->store->printer_target),
            $this->rule(),
            // Penggaris kolom: kalau angka terakhir terpotong atau baris
            // membungkus, berarti lebar kertas di setting tidak cocok
            // dengan printer yang terpasang.
            $this->ruler(),
            ...$this->footer(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function header(): array
    {
        $lines = [$this->center($this->store->receipt_header !== '' ? $this->store->receipt_header : $this->store->name)];

        foreach ($this->wrap($this->store->address) as $line) {
            $lines[] = $this->center($line);
        }

        if ($this->store->phone !== '') {
            $lines[] = $this->center($this->store->phone);
        }

        $lines[] = $this->rule();

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function meta(Transaction $transaction): array
    {
        return [
            $this->twoColumns('No', $transaction->number),
            $this->twoColumns('Tanggal', $transaction->created_at?->format('d/m/Y H:i') ?? '-'),
            $this->twoColumns('Kasir', $transaction->cashier_name),
            $this->rule(),
        ];
    }

    /**
     * Tiap item memakai dua baris: nama produk di baris sendiri (boleh
     * membungkus), lalu "qty x harga" di kiri dan subtotalnya rata kanan.
     * Pola dua baris ini yang membuat nama panjang tetap terbaca utuh di
     * kertas 58mm.
     *
     * @return array<int, string>
     */
    private function items(Transaction $transaction): array
    {
        $lines = [];

        foreach ($transaction->items as $item) {
            /** @var TransactionItem $item */
            foreach ($this->wrap($item->name) as $namePart) {
                $lines[] = $namePart;
            }

            $lines[] = $this->twoColumns(
                sprintf('  %d x %s', $item->qty, $this->money($item->price)),
                $this->money($item->price * $item->qty),
            );

            if ($item->discount > 0) {
                $lines[] = $this->twoColumns('  Diskon', '-'.$this->money($item->discount));
            }
        }

        $lines[] = $this->rule();

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function totals(Transaction $transaction): array
    {
        $lines = [
            $this->twoColumns('Subtotal', $this->money($transaction->subtotal)),
        ];

        if ($transaction->discount > 0) {
            $lines[] = $this->twoColumns('Diskon', '-'.$this->money($transaction->discount));
        }

        if ($transaction->tax > 0) {
            $lines[] = $this->twoColumns('PPN '.$this->store->tax_percent.'%', $this->money($transaction->tax));
        }

        $lines[] = $this->twoColumns('TOTAL', $this->money($transaction->total));
        $lines[] = $this->twoColumns(ucfirst($transaction->payment_method), $this->money($transaction->paid));
        $lines[] = $this->twoColumns('Kembali', $this->money($transaction->change));
        $lines[] = $this->rule();

        return $lines;
    }

    /**
     * Footer bebas tulis, boleh banyak baris.
     *
     * Baris baru yang diketik pengguna DIPERTAHANKAN, termasuk baris kosong —
     * itu bagian dari tata letak yang ia rancang. Tiap baris di-trim dulu lalu
     * ditengahkan, jadi pengguna tidak perlu menghitung spasi sendiri: teks
     * yang ia ketik rata kiri tetap keluar rapi di tengah kertas, sementara
     * garis pemisah selebar kertas (mis. 32 tanda '=') tetap penuh karena
     * memang tidak menyisakan ruang untuk digeser.
     *
     * @return array<int, string>
     */
    private function footer(): array
    {
        $footer = trim((string) $this->store->receipt_footer);
        $lines = [];

        // preg_split atas string kosong mengembalikan [''] — satu baris kosong
        // palsu yang akan menambah celah menganggur di atas baris penutup.
        $rawLines = $footer === '' ? [] : (preg_split('/\r\n|\r|\n/', $footer) ?: []);

        foreach ($rawLines as $rawLine) {
            $line = trim($rawLine);

            if ($line === '') {
                $lines[] = '';

                continue;
            }

            // Baris yang lebih panjang dari kertas dibungkus, bukan dipotong —
            // kalimat toko tidak boleh hilang separuh.
            foreach ($this->wrap($line) as $wrapped) {
                $lines[] = $this->center($wrapped);
            }
        }

        // Pemisah satu baris kosong hanya bila toko memang menulis footer;
        // kalau tidak, baris penutup aplikasi berdiri sendiri tanpa celah
        // menganggur di atasnya.
        if ($lines !== []) {
            $lines[] = '';
        }

        $lines[] = $this->center(self::BRANDING);

        return $lines;
    }

    public function width(): int
    {
        return $this->store->receiptWidth();
    }

    /**
     * Label di kiri, nilai rata kanan. Bila keduanya tidak muat dalam satu
     * baris, label yang dipotong — nilainya (angka uang) tidak boleh hilang.
     */
    private function twoColumns(string $label, string $value): string
    {
        $space = $this->width() - mb_strlen($value);

        if ($space < 1) {
            return mb_substr($value, 0, $this->width());
        }

        $label = mb_substr($label, 0, $space - 1);

        return $label.str_repeat(' ', $this->width() - mb_strlen($label) - mb_strlen($value)).$value;
    }

    private function center(string $text): string
    {
        $text = mb_substr($text, 0, $this->width());
        $padding = intdiv($this->width() - mb_strlen($text), 2);

        return str_repeat(' ', max(0, $padding)).$text;
    }

    private function rule(): string
    {
        return str_repeat('-', $this->width());
    }

    /**
     * Penggaris kolom "....5...10...15..." selebar kertas.
     */
    private function ruler(): string
    {
        $ruler = '';

        for ($column = 1; $column <= $this->width(); $column++) {
            $ruler .= $column % 10 === 0 ? (string) (intdiv($column, 10) % 10) : ($column % 5 === 0 ? '+' : '.');
        }

        return $ruler;
    }

    /**
     * @return array<int, string>
     */
    private function wrap(string $text): array
    {
        if (trim($text) === '') {
            return [];
        }

        return explode("\n", wordwrap($text, $this->width(), "\n", true));
    }

    /**
     * Rupiah tanpa "Rp" dan tanpa desimal — di kertas 32 kolom setiap
     * karakter berharga.
     */
    private function money(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }
}
