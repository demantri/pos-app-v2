export type Store = {
    id: number;
    name: string;
    code: string;
    address: string;
    phone: string;
    is_active: boolean;
    products_count: number;
};

export type StoreOption = {
    id: number;
    name: string;
    code: string;
};

export type Category = {
    id: number;
    name: string;
    description: string;
    products_count: number;
};

export type Product = {
    id: number;
    name: string;
    sku: string;
    barcode: string;
    category_id: number;
    category: string;
    price: number;
    stock: number;
    unit: string;
    is_active: boolean;
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
    id: number;
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
};

export type DashboardStats = {
    sales_today: number;
    transactions_today: number;
    items_sold: number;
    average_per_transaction: number;
    recent_transactions: Transaction[];
};

export type CartItem = {
    product_id: number;
    name: string;
    price: number;
    qty: number;
    discount: number;
};
