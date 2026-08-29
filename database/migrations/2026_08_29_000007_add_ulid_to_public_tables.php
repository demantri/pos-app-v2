<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Kunci publik untuk URL, menggantikan id berurut.
     *
     * Primary key integer TIDAK diubah — lima tabel merujuknya lewat foreign
     * key, dan angkanya tidak pernah lagi tampil ke klien setelah ini.
     *
     * @var list<string>
     */
    private const TABLES = ['stores', 'categories', 'products', 'transactions', 'users'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->char('ulid', 26)->nullable()->after('id');
            });

            // Baris yang sudah ada perlu diisi sebelum kolomnya dikunci.
            DB::table($table)->orderBy('id')->select('id')->chunk(200, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->update(['ulid' => (string) Str::ulid()]);
                }
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->char('ulid', 26)->nullable(false)->change();
                $blueprint->unique('ulid');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropUnique($table.'_ulid_unique');
                $blueprint->dropColumn('ulid');
            });
        }
    }
};
