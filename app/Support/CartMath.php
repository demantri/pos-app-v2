<?php

namespace App\Support;

/**
 * Rumus uang keranjang, sisi server.
 *
 * Ini adalah cerminan satu-satu dari resources/js/lib/cart.ts
 * (lineSubtotal/cartTotals/roundTo). Keduanya HARUS memberi angka yang sama:
 * klien memakainya untuk menampilkan total sebelum bayar, server memakainya
 * untuk menghitung angka yang benar-benar disimpan. Kalau salah satunya
 * berubah, ubah keduanya.
 *
 * Semua nilai adalah rupiah bulat (integer), sesuai keputusan skema fase 2.
 * PHP round() dan JS Math.round() sama-sama membulatkan .5 ke atas untuk
 * bilangan non-negatif — dan seluruh nilai di sini non-negatif — jadi tidak
 * ada selisih pembulatan antara kedua sisi.
 */
class CartMath
{
    /**
     * Subtotal satu baris keranjang: harga × qty dikurangi diskon baris,
     * diklem agar tidak pernah negatif.
     */
    public static function lineSubtotal(int $price, int $qty, int $discount): int
    {
        return max(0, ($price * $qty) - $discount);
    }

    /**
     * Pembulatan ke kelipatan `step` (setting `rounding` milik toko).
     */
    public static function roundTo(int $value, int $step): int
    {
        if ($step <= 1) {
            return $value;
        }

        return (int) (round($value / $step) * $step);
    }

    /**
     * Total transaksi dari subtotal keranjang.
     *
     * Diskon transaksi diklem ke [0, subtotal], PPN dihitung dari nilai
     * setelah diskon, lalu total dibulatkan ke kelipatan `rounding`.
     *
     * @return array{subtotal: int, discount: int, taxable: int, tax: int, total: int}
     */
    public static function totals(int $subtotal, int $discount, int $taxPercent, int $rounding): array
    {
        $discount = min(max(0, $discount), $subtotal);
        $taxable = $subtotal - $discount;
        $tax = (int) round($taxable * $taxPercent / 100);
        $total = $taxable === 0 ? 0 : self::roundTo($taxable + $tax, $rounding);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'taxable' => $taxable,
            'tax' => $tax,
            'total' => $total,
        ];
    }

    /**
     * Kembalian; tidak pernah negatif (sama dengan cart.ts::changeFor).
     */
    public static function changeFor(int $total, int $paid): int
    {
        return max(0, $paid - $total);
    }
}
