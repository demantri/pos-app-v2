<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Memakai kolom `ulid` sebagai kunci di URL, bukan primary key.
 *
 * Primary key tetap integer: lima tabel merujuknya lewat foreign key, dan
 * mengubah semuanya menjadi ULID hanya menambah risiko tanpa manfaat — angka
 * itu tidak pernah tampil ke klien.
 *
 * ULID dipilih ketimbang UUID v4 karena terurut waktu, sehingga index-nya
 * tetap rapi alih-alih menyebar acak.
 */
trait HasUlidRouteKey
{
    protected static function bootHasUlidRouteKey(): void
    {
        static::creating(function (Model $model): void {
            if (blank($model->getAttribute('ulid'))) {
                $model->setAttribute('ulid', (string) Str::ulid());
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
