#!/usr/bin/env python3
"""Kirim byte ESC/POS ke printer thermal Bluetooth lewat soket RFCOMM.

Dipanggil oleh App\\Printing\\BluetoothPrintConnector: data nota masuk lewat
stdin, alamat MAC dan kanal RFCOMM lewat argumen.

Alasan skrip ini ada: PHP tidak bisa membuka soket AF_BLUETOOTH, sedangkan
Python bisa — dan bisa TANPA root, tanpa `rfcomm bind`, dan tanpa /dev/rfcomm0
yang hilang tiap reboot. Alternatifnya (backend bluez-cups) terbukti gagal
pada printer SPP semacam RPP210A.

Keluar dengan status 0 bila seluruh data terkirim; selain itu pesan
kesalahan ditulis ke stderr dan dibaca kembali oleh aplikasi.
"""
import socket
import sys
import time

CONNECT_TIMEOUT = 15
SEND_TIMEOUT = 30

# Printer thermal Bluetooth punya buffer masuk kecil dan TIDAK punya kendali
# aliran: kalau seluruh nota ditembakkan sekali jalan, byte yang melewati
# kapasitas buffer hilang tanpa pesan apa pun — nota panjang tercetak
# terpotong di bawah, sementara nota pendek tampak baik-baik saja.
# Karena itu data dikirim per potong kecil dengan jeda, supaya printer sempat
# mencetak dan mengosongkan buffernya.
CHUNK_SIZE = 128
CHUNK_PAUSE = 0.04

# Jeda sebelum soket ditutup. Menutup RFCOMM tepat setelah byte terakhir
# dikirim bisa membuang isi buffer yang belum sempat dicetak.
SETTLE_DELAY = 0.6


def main() -> int:
    if len(sys.argv) < 3:
        print('pemakaian: bluetooth-print.py <MAC> <kanal>', file=sys.stderr)
        return 2

    mac = sys.argv[1]
    try:
        channel = int(sys.argv[2])
    except ValueError:
        print(f'kanal RFCOMM tidak valid: {sys.argv[2]}', file=sys.stderr)
        return 2

    data = sys.stdin.buffer.read()
    if not data:
        print('tidak ada data untuk dicetak', file=sys.stderr)
        return 2

    sock = socket.socket(socket.AF_BLUETOOTH, socket.SOCK_STREAM, socket.BTPROTO_RFCOMM)
    sock.settimeout(CONNECT_TIMEOUT)

    try:
        sock.connect((mac, channel))
    except OSError as error:
        print(
            f'tidak bisa menyambung ke {mac} kanal {channel}: {error}. '
            'Pastikan printer menyala, dalam jangkauan, dan kanalnya benar.',
            file=sys.stderr,
        )
        return 1
    finally:
        pass

    try:
        sock.settimeout(SEND_TIMEOUT)
        for start in range(0, len(data), CHUNK_SIZE):
            sock.sendall(data[start:start + CHUNK_SIZE])
            time.sleep(CHUNK_PAUSE)
        time.sleep(SETTLE_DELAY)
    except OSError as error:
        print(f'gagal mengirim data ke printer: {error}', file=sys.stderr)
        return 1
    finally:
        try:
            sock.close()
        except OSError:
            pass

    return 0


if __name__ == '__main__':
    sys.exit(main())
