<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasUlidRouteKey;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUlidRouteKey, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * `is_owner` sengaja TIDAK dimasukkan — itu wewenang global untuk
     * membuat toko baru, jadi harus disetel eksplisit
     * (`$user->is_owner = true; $user->save();`), bukan lewat mass
     * assignment dari input request.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Cache role per toko selama satu request.
     *
     * @var array<int, string|null>
     */
    protected array $storeRoles = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_owner' => 'boolean',
        ];
    }

    /**
     * Toko-toko tempat user ini menjadi admin/kasir, beserta role-nya
     * (per toko, lewat kolom pivot `role` pada store_user).
     *
     * @return BelongsToMany<Store, $this>
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Owner bisnis aplikasi: satu-satunya yang boleh membuat toko baru, dan
     * boleh mengelola toko mana pun. Wewenang GLOBAL, bukan role per toko.
     */
    public function isOwner(): bool
    {
        return (bool) $this->is_owner;
    }

    /**
     * Role user ini DI TOKO tertentu: 'admin', 'kasir', atau null bila ia
     * bukan anggota toko itu. Owner sengaja mengembalikan null di sini —
     * wewenangnya tidak datang dari pivot.
     */
    public function roleIn(Store $store): ?string
    {
        $storeId = $store->getKey();

        // Dihafal per request: satu halaman bisa menanyakan role yang sama
        // beberapa kali (middleware, policy, shared prop).
        if (! array_key_exists($storeId, $this->storeRoles)) {
            $this->storeRoles[$storeId] = $this->stores()
                ->whereKey($storeId)
                ->first()?->pivot?->role;
        }

        return $this->storeRoles[$storeId];
    }

    public function isAdminOf(Store $store): bool
    {
        return $this->roleIn($store) === 'admin';
    }

    public function isCashierOf(Store $store): bool
    {
        return $this->roleIn($store) === 'kasir';
    }

    /**
     * Anggota toko ini — admin atau kasirnya.
     *
     * Owner SENGAJA tidak termasuk: sejak fase 3 ia tidak boleh melihat isi
     * toko sama sekali (transaksi, produk, POS, setting). Wewenangnya berhenti
     * di tingkat toko: menambah, mengubah identitas, mengatur status, dan
     * mengarsipkan.
     */
    public function canAccessStore(Store $store): bool
    {
        return $this->roleIn($store) !== null;
    }

    /**
     * Boleh mengelola isi toko: produk, kategori, transaksi, dan setting.
     * Hanya admin toko — bukan kasir, dan bukan owner.
     */
    public function canManageStore(Store $store): bool
    {
        return $this->isAdminOf($store);
    }

    /**
     * Boleh mengelola pengguna toko ini.
     *
     * Owner ikut boleh — dan ini SATU-SATUNYA pintunya ke dalam scope toko.
     * Tanpa itu toko yang baru dibuat tidak akan pernah punya pengguna, karena
     * ia belum punya admin yang bisa membuatkannya.
     */
    public function canManageStoreUsers(Store $store): bool
    {
        return $this->isOwner() || $this->isAdminOf($store);
    }

    /**
     * Boleh membuat, mengubah, mengarsipkan, dan mengatur status toko.
     */
    public function canAdministerStores(): bool
    {
        return $this->isOwner();
    }

    /**
     * Label peran untuk ditampilkan: owner / admin / kasir / null.
     */
    public function roleLabelFor(Store $store): ?string
    {
        return $this->isOwner() ? 'owner' : $this->roleIn($store);
    }
}
