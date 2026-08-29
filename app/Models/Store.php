<?php

namespace App\Models;

use App\Concerns\HasUlidRouteKey;
use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory, HasUlidRouteKey, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'is_active',
        'currency',
        'tax_percent',
        'rounding',
        'receipt_header',
        'receipt_footer',
        'paper_size',
        'open_time',
        'close_time',
        'printer_connector',
        'printer_target',
        'printer_channel',
        'printer_feed_lines',
        'printer_auto_print',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'tax_percent' => 'integer',
            'rounding' => 'integer',
            'printer_channel' => 'integer',
            'printer_feed_lines' => 'integer',
            'printer_auto_print' => 'boolean',
        ];
    }

    /**
     * Berapa karakter yang muat dalam satu baris nota, mengikuti lebar
     * kertas toko ini (font A: 32 karakter di 58mm, 48 di 80mm).
     */
    public function receiptWidth(): int
    {
        return $this->paper_size === '80mm' ? 48 : 32;
    }

    /**
     * Toko ini punya printer yang dikonfigurasi.
     */
    public function hasPrinter(): bool
    {
        return $this->printer_connector !== 'none' && $this->printer_target !== '';
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * User yang menjadi admin/kasir di toko ini, beserta role-nya (per toko,
     * lewat kolom pivot `role` pada store_user). Owner tidak butuh baris
     * pivot di sini — wewenangnya global lewat users.is_owner.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }
}
