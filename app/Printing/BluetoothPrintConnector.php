<?php

namespace App\Printing;

use Illuminate\Support\Facades\Process;
use Mike42\Escpos\PrintConnectors\PrintConnector;

/**
 * Connector escpos-php untuk printer thermal Bluetooth (profil Serial Port).
 *
 * Seluruh byte nota ditampung dulu, lalu dikirim sekali jalan lewat
 * scripts/bluetooth-print.py saat finalize(). Skrip Python itu ada karena PHP
 * tidak bisa membuka soket AF_BLUETOOTH — dan pendekatan ini tidak menuntut
 * root, tidak butuh `rfcomm bind`, dan tidak bergantung pada /dev/rfcomm0
 * yang hilang setiap reboot.
 *
 * Menampung dulu (bukan mengirim per potong) juga membuat printer menerima
 * satu aliran utuh: sambungan Bluetooth yang putus di tengah tidak
 * menyisakan setengah nota tercetak.
 */
class BluetoothPrintConnector implements PrintConnector
{
    private string $buffer = '';

    private bool $finalized = false;

    public function __construct(
        private readonly string $address,
        private readonly int $channel,
    ) {}

    public function __destruct()
    {
        // Sengaja tidak mengirim apa pun di destructor: kalau finalize()
        // tidak pernah dipanggil, berarti pencetakan gagal di tengah jalan
        // dan nota separuh jadi tidak boleh diam-diam ikut keluar.
        $this->buffer = '';
    }

    public function write(string $data): void
    {
        $this->buffer .= $data;
    }

    public function read(int $len): bool|string
    {
        // Printer Bluetooth di jalur ini tidak dibaca balik (status query
        // tidak dipakai), jadi tidak ada data yang bisa dikembalikan.
        return false;
    }

    public function finalize(): void
    {
        if ($this->finalized) {
            return;
        }

        $this->finalized = true;

        if ($this->buffer === '') {
            return;
        }

        $result = Process::timeout(60)
            ->input($this->buffer)
            // Argumen dioper sebagai array — tidak lewat shell, jadi alamat
            // MAC dari database tidak bisa menjadi injeksi perintah.
            ->run(['python3', base_path('scripts/bluetooth-print.py'), $this->address, (string) $this->channel]);

        $this->buffer = '';

        if ($result->failed()) {
            $reason = trim($result->errorOutput()) !== ''
                ? trim($result->errorOutput())
                : trim($result->output());

            throw PrinterUnavailable::failed(
                $this->address,
                $reason !== '' ? $reason : 'perintah pengirim Bluetooth gagal.',
            );
        }
    }
}
