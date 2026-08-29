<?php

namespace App\Printing;

use RuntimeException;

/**
 * Printer nota tidak bisa dipakai: belum dikonfigurasi, antrian CUPS-nya
 * tidak ada, device-nya tidak bisa ditulisi, dan seterusnya.
 *
 * Pesannya sengaja ditulis untuk dibaca kasir, bukan hanya untuk log —
 * exception ini berakhir sebagai notifikasi di layar POS.
 */
class PrinterUnavailable extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('Printer belum diatur untuk toko ini. Buka Setting Toko → Printer.');
    }

    public static function unknownConnector(string $connector): self
    {
        return new self("Jenis koneksi printer '{$connector}' tidak dikenal.");
    }

    public static function failed(string $target, string $reason): self
    {
        return new self("Printer '{$target}' tidak bisa dipakai: {$reason}");
    }
}
