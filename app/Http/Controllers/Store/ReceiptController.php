<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Transaction;
use App\Printing\PrinterUnavailable;
use App\Printing\ReceiptPrinter;
use Illuminate\Http\RedirectResponse;

/**
 * Cetak (atau cetak ulang) nota satu transaksi.
 *
 * Rutenya berada di grup `can:operatePos` — kasir boleh mencetak ulang nota
 * toko tempat ia bekerja, tanpa perlu hak kelola.
 */
class ReceiptController extends Controller
{
    public function __invoke(Store $store, Transaction $transaction, ReceiptPrinter $printer): RedirectResponse
    {
        try {
            $printer->printTransaction($store, $transaction);
        } catch (PrinterUnavailable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Nota {$transaction->number} dikirim ke printer.");
    }
}
