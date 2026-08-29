<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\SettingRequest;
use App\Models\Store;
use App\Printing\PrinterUnavailable;
use App\Printing\ReceiptPrinter;
use App\Support\StoreData;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(Store $store): Response
    {
        return Inertia::render('stores/settings/Edit', [
            'settings' => StoreData::settings($store),
        ]);
    }

    public function update(SettingRequest $request, Store $store): RedirectResponse
    {
        $store->update($request->validated());

        return back()->with('success', 'Setting toko tersimpan.');
    }

    /**
     * Mencetak nota uji ke printer toko ini — cara tercepat memastikan
     * koneksi dan lebar kertasnya benar tanpa membuat transaksi sungguhan.
     */
    public function printTest(Store $store, ReceiptPrinter $printer): RedirectResponse
    {
        try {
            $printer->printTestPage($store);
        } catch (PrinterUnavailable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Nota uji dikirim ke printer.');
    }
}
