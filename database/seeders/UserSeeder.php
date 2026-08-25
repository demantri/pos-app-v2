<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Pengguna demo:
 *
 * - demo@pos.test — user demo yang sudah dipakai sejak fase 1, dijadikan
 *   owner (wewenang global bikin toko baru).
 * - Satu admin + satu kasir per toko, lewat pivot store_user (role per
 *   toko, BUKAN role global).
 * - Satu orang ("multirole@pos.test") yang admin di satu toko sekaligus
 *   kasir di toko lain, supaya jalur role-per-toko benar-benar teruji bisa
 *   dipakai satu akun untuk dua peran berbeda.
 *
 * Idempoten: user di-upsert lewat email, pivot di-upsert lewat pasangan
 * (store_id, user_id) — sesuai unique key di migration store_user.
 */
class UserSeeder extends Seeder
{
    private const PASSWORD = 'password';

    public function run(): void
    {
        $this->seedOwner();

        $stores = Store::query()->orderBy('id')->get();

        /** @var array<string, Store> $storesByCode */
        $storesByCode = $stores->keyBy('code')->all();

        foreach ($stores as $store) {
            $suffix = mb_strtolower($store->code);

            $admin = $this->upsertUser(
                email: "admin.{$suffix}@pos.test",
                name: "Admin {$store->name}",
            );
            $this->assignRole($store, $admin, 'admin');

            $kasir = $this->upsertUser(
                email: "kasir.{$suffix}@pos.test",
                name: "Kasir {$store->name}",
            );
            $this->assignRole($store, $kasir, 'kasir');
        }

        // Bonus: satu akun berperan berbeda di dua toko sekaligus (admin di
        // toko pertama, kasir di toko kedua), supaya role per-toko terbukti
        // bisa dipakai satu user untuk dua peran — bukan cuma ada di skema.
        if (isset($storesByCode['SDR'], $storesByCode['KLD'])) {
            $multirole = $this->upsertUser(
                email: 'multirole@pos.test',
                name: 'Bagas (Admin SDR / Kasir KLD)',
            );
            $this->assignRole($storesByCode['SDR'], $multirole, 'admin');
            $this->assignRole($storesByCode['KLD'], $multirole, 'kasir');
        }
    }

    private function seedOwner(): void
    {
        $owner = $this->upsertUser(
            email: 'demo@pos.test',
            name: 'Demo Owner',
        );

        // is_owner sengaja tidak fillable (lihat App\Models\User) — harus
        // disetel eksplisit di luar mass assignment.
        $owner->is_owner = true;
        $owner->save();
    }

    private function upsertUser(string $email, string $name): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => self::PASSWORD,
                'email_verified_at' => now(),
            ],
        );

        return $user;
    }

    private function assignRole(Store $store, User $user, string $role): void
    {
        $pivot = DB::table('store_user')
            ->where('store_id', $store->id)
            ->where('user_id', $user->id);

        if ($pivot->exists()) {
            $pivot->update(['role' => $role, 'updated_at' => now()]);

            return;
        }

        DB::table('store_user')->insert([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
