<?php

namespace App\Printing;

use App\Models\Store;
use App\Models\Transaction;
use Mike42\Escpos\PrintConnectors\CupsPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\PrintConnector;
use Mike42\Escpos\Printer;
use Throwable;

/**
 * Mengirim nota ke printer thermal ESC/POS milik toko.
 *
 * Bentuk notanya bukan urusan kelas ini — itu ReceiptLayout. Di sini hanya
 * soal membuka koneksi sesuai setting toko, menuliskan baris demi baris,
 * memotong kertas, dan memastikan koneksinya selalu ditutup.
 */
class ReceiptPrinter
{
    public function printTransaction(Store $store, Transaction $transaction): void
    {
        $transaction->loadMissing('items');

        $this->send($store, (new ReceiptLayout($store))->forTransaction($transaction));
    }

    public function printTestPage(Store $store): void
    {
        $this->send($store, (new ReceiptLayout($store))->testPage());
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function send(Store $store, array $lines): void
    {
        if (! $store->hasPrinter()) {
            throw PrinterUnavailable::notConfigured();
        }

        $connector = $this->connector($store);

        try {
            // Konstruktor Printer sudah mengirim ESC @ (initialize) sendiri —
            // memanggilnya lagi hanya menggandakan byte itu di awal struk.
            $printer = new Printer($connector);

            foreach ($lines as $line) {
                $printer->text($line."\n");
            }

            // Sisa kertas supaya baris terakhir lolos dari tepi sobek.
            // Jumlahnya disetel per toko: jarak print head ke tepi sobek
            // berbeda antar model printer. 0 berarti tanpa baris kosong sama
            // sekali — feed(0) tidak sah di escpos-php, jadi dilewati.
            if ($store->printer_feed_lines > 0) {
                $printer->feed($store->printer_feed_lines);
            }

            $printer->cut();
            $printer->close();
        } catch (Throwable $e) {
            // Sengaja TIDAK ada pembersihan connector di sini. Versi
            // sebelumnya memanggil $connector->close(), method yang tidak
            // pernah ada di interface PrintConnector — jadi ia hanya
            // melempar Error yang langsung ditelan, seolah-olah bersih-bersih
            // padahal tidak melakukan apa pun. Dan finalize() bukan
            // penggantinya: pada CupsPrintConnector, finalize() justru
            // MENGIRIM job, sehingga nota separuh jadi bisa ikut keluar dari
            // printer. Sisa handle diserahkan ke destructor masing-masing
            // connector.
            throw PrinterUnavailable::failed($store->printer_target, $e->getMessage());
        }
    }

    private function connector(Store $store): PrintConnector
    {
        try {
            return match ($store->printer_connector) {
                // Antrian CUPS: `lpstat -p` menampilkan nama-nama yang sah.
                'cups' => new CupsPrintConnector($store->printer_target),
                // Device mentah, mis. /dev/usb/lp0. Pengguna web server harus
                // punya izin tulis ke sana (biasanya lewat grup `lp`).
                'file' => new FilePrintConnector($store->printer_target),
                // Printer thermal Bluetooth lewat profil Serial Port.
                // `printer_target` berisi alamat MAC, `printer_channel`
                // nomor kanal RFCOMM-nya.
                'bluetooth' => new BluetoothPrintConnector($store->printer_target, $store->printer_channel),
                default => throw PrinterUnavailable::unknownConnector($store->printer_connector),
            };
        } catch (PrinterUnavailable $e) {
            throw $e;
        } catch (Throwable $e) {
            throw PrinterUnavailable::failed($store->printer_target, $e->getMessage());
        }
    }
}
