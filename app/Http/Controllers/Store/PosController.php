<?php

namespace App\Http\Controllers\Store;

use App\Actions\Pos\ProcessCheckout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CheckoutRequest;
use App\Models\Store;
use App\Models\User;
use App\Printing\PrinterUnavailable;
use App\Printing\ReceiptPrinter;
use App\Support\StoreData;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(Store $store): Response
    {
        return Inertia::render('stores/pos/Index', [
            'products' => StoreData::products($store),
            'categories' => StoreData::categories($store),
            'settings' => StoreData::settings($store),
        ]);
    }

    public function checkout(
        CheckoutRequest $request,
        Store $store,
        ProcessCheckout $checkout,
        ReceiptPrinter $printer,
    ): RedirectResponse {
        /** @var User $cashier Rute ini selalu di bawah middleware auth. */
        $cashier = $request->user();

        $transaction = $checkout->handle($store, $request->validated(), $cashier);

        $response = back()
            ->with('success', "Transaksi {$transaction->number} tersimpan.")
            // Dipakai layar POS untuk menampilkan nomor struk sungguhan dan
            // menyediakan tombol cetak ulang bila printer tadi gagal.
            ->with('receipt', ['id' => $transaction->id, 'number' => $transaction->number]);

        if (! $store->printer_auto_print || ! $store->hasPrinter()) {
            return $response;
        }

        try {
            $printer->printTransaction($store, $transaction);
        } catch (PrinterUnavailable $e) {
            // Printer TIDAK PERNAH membatalkan penjualan: transaksinya sudah
            // tersimpan, kasir hanya diberi tahu bahwa notanya gagal keluar
            // dan bisa mencetak ulang dari dialog struk.
            return $response->with('error', $e->getMessage());
        }

        return $response;
    }
}
