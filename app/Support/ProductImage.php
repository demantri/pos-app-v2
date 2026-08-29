<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Penyimpanan gambar produk di disk `public` (dilayani lewat `storage:link`).
 *
 * Kolom `products.image_path` menyimpan path relatif (mis. products/abc.jpg),
 * bukan URL penuh — supaya isinya tidak ikut basi ketika domain aplikasi
 * berubah.
 */
class ProductImage
{
    private const DIRECTORY = 'products';

    public static function store(UploadedFile $file): string
    {
        return $file->store(self::DIRECTORY, 'public');
    }

    /**
     * Menghapus berkas lama. Aman dipanggil dengan null atau dengan path
     * yang berkasnya sudah tidak ada.
     */
    public static function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    public static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
