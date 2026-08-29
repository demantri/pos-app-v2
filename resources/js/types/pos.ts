/**
 * Peran yang dimiliki user DI SEBUAH TOKO. 'owner' bukan baris pivot — itu
 * wewenang global (users.is_owner) yang berlaku di semua toko.
 */
export type StoreRole = 'owner' | 'admin' | 'kasir';

export type Store = {
    // ULID, bukan primary key berurut — dipakai apa adanya di URL.
    id: string;
    name: string;
    code: string;
    address: string;
    phone: string;
    is_active: boolean;
    products_count: number;
    // Peran user yang sedang login di toko ini; null bila ia bukan anggota.
    role: StoreRole | null;
    // Toko terarsip: hilang dari daftar biasa, datanya tetap utuh.
    is_archived: boolean;
};

export type StoreUser = {
    id: string;
    name: string;
    email: string;
    role: 'admin' | 'kasir' | null;
    joined_at: string;
};

export type Permissions = {
    is_owner: boolean;
    can_create_store: boolean;
    // Ubah identitas, status, dan arsip toko — wewenang tingkat aplikasi.
    can_administer_stores: boolean;
    // Isi toko: dashboard, produk, kategori, transaksi, setting.
    can_manage_current_store: boolean;
    // Satu-satunya pintu owner ke dalam scope toko.
    can_manage_current_store_users: boolean;
    can_operate_current_pos: boolean;
    can_create_admin: boolean;
};

export type StoreOption = {
    id: string;
    name: string;
    code: string;
};

export type Category = {
    id: string;
    name: string;
    description: string;
    products_count: number;
};

export type Product = {
    id: string;
    name: string;
    sku: string;
    barcode: string;
    // ULID kategori; null bila kategorinya sudah dihapus (`category_id`
    // nullable + nullOnDelete). `category` tetap string: server mengirim
    // 'Tanpa kategori'.
    category_id: string | null;
    category: string;
    price: number;
    stock: number;
    // Ambang peringatan stok menipis; 0 berarti produk ini tidak diawasi.
    min_stock: number;
    unit: string;
    is_active: boolean;
    // URL penuh gambar produk, null bila produk belum punya gambar.
    image_url: string | null;
};

export type PaymentMethod = 'tunai' | 'kartu' | 'qris';

export type TransactionItem = {
    name: string;
    qty: number;
    price: number;
    discount: number;
    subtotal: number;
};

export type Transaction = {
    id: string;
    number: string;
    created_at: string;
    cashier: string;
    items_count: number;
    total: number;
    payment_method: PaymentMethod;
    items: TransactionItem[];
};

export type StoreSettings = {
    name: string;
    code: string;
    address: string;
    phone: string;
    currency: string;
    tax_percent: number;
    rounding: number;
    receipt_header: string;
    receipt_footer: string;
    paper_size: '58mm' | '80mm';
    open_time: string;
    close_time: string;
    is_active: boolean;
    // Printer nota, per toko. 'none' mematikan fitur cetak untuk toko ini.
    printer_connector: PrinterConnector;
    // Nama antrian CUPS, atau path device untuk connector 'file'.
    printer_target: string;
    // Kanal RFCOMM untuk koneksi Bluetooth; diabaikan connector lain.
    printer_channel: number;
    // Baris kosong setelah footer nota, sebelum kertas disobek.
    printer_feed_lines: number;
    printer_auto_print: boolean;
};

export type PrinterConnector = 'none' | 'cups' | 'file' | 'bluetooth';

/**
 * Transaksi yang baru saja tersimpan, dikirim lewat flash oleh checkout.
 */
export type ReceiptFlash = {
    id: string;
    number: string;
};

export type LowStockProduct = {
    id: string;
    name: string;
    stock: number;
    min_stock: number;
    unit: string;
};

export type DashboardStats = {
    sales_today: number;
    transactions_today: number;
    items_sold: number;
    average_per_transaction: number;
    low_stock_count: number;
    low_stock: LowStockProduct[];
    recent_transactions: Transaction[];
};

export type CartItem = {
    product_id: string;
    name: string;
    price: number;
    qty: number;
    discount: number;
};
