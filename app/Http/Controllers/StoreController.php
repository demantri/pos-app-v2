<?php

namespace App\Http\Controllers;

use App\Http\Requests\Store\StoreFormRequest;
use App\Models\Store;
use App\Models\User;
use App\Support\StoreData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Daftar toko — sekaligus pusat kerja owner.
 *
 * Sejak fase 3, owner mengurus toko HANYA dari sini: menambah, mengubah
 * identitasnya, mengatur status buka/tutup, mengarsipkan, dan memulihkan. Ia
 * tidak lagi bisa masuk ke dalam toko (dashboard, POS, produk, transaksi,
 * setting) — lihat App\Policies\StorePolicy.
 */
class StoreController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $viewer Rute ini selalu di bawah middleware auth. */
        $viewer = $request->user();

        // Arsip hanya relevan bagi yang boleh mengurusnya; permintaan dari
        // user lain diabaikan diam-diam, bukan dijadikan error.
        $showArchived = $viewer->canAdministerStores() && $request->boolean('archived');

        return Inertia::render('stores/Index', [
            'stores' => StoreData::stores($viewer, $showArchived),
            'showingArchived' => $showArchived,
        ]);
    }

    public function store(StoreFormRequest $request): RedirectResponse
    {
        $store = Store::create([
            ...$request->validated(),
            // Setting toko memakai default kolom (lihat migration stores);
            // header struk diisi nama toko supaya tidak kosong sejak awal.
            'receipt_header' => $request->validated('name'),
        ]);

        return back()->with('success', "Toko {$store->name} tersimpan. Tambahkan admin tokonya lewat menu Pengguna.");
    }

    public function update(StoreFormRequest $request, Store $store): RedirectResponse
    {
        $store->update($request->validated());

        return back()->with('success', "Toko {$store->name} diperbarui.");
    }

    /**
     * Mengarsipkan toko — soft delete, bukan hapus permanen.
     *
     * Foreign key `store_id` memakai cascade: hapus sungguhan akan ikut
     * menghapus kategori, produk, transaksi, dan item transaksinya. Riwayat
     * transaksi adalah catatan keuangan, jadi datanya dibiarkan utuh dan
     * tokonya hanya disembunyikan.
     */
    public function destroy(Store $store): RedirectResponse
    {
        $store->delete();

        return back()->with('success', "Toko {$store->name} diarsipkan. Datanya tetap tersimpan dan bisa dipulihkan.");
    }

    public function restore(Store $archivedStore): RedirectResponse
    {
        $archivedStore->restore();

        return back()->with('success', "Toko {$archivedStore->name} dipulihkan.");
    }
}
