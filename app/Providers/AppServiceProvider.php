<?php

namespace App\Providers;

use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRouteBindings();
    }

    /**
     * Parameter rute {store} selalu diselesaikan menjadi model Store.
     *
     * Dipasang eksplisit (bukan mengandalkan implicit binding lewat type
     * hint) supaya SEMUA rute di bawah prefix stores/{store} ikut terlindungi
     * — termasuk yang controller-nya tidak menuliskan Store di signature —
     * dan id yang tidak ada otomatis menjadi 404.
     */
    protected function configureRouteBindings(): void
    {
        Route::model('store', Store::class);

        // Toko terarsip sengaja tidak terjangkau lewat {store}: seluruh rute
        // toko harus memperlakukannya seolah tidak ada. Hanya rute pemulihan
        // yang memakai parameter ini.
        Route::bind('archivedStore', fn (string $ulid): Store => Store::withTrashed()
            ->where('ulid', $ulid)
            ->firstOrFail());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
