import { describe, expect, it } from 'vitest';
import {
    cartSubtotal,
    cartTotals,
    changeFor,
    lineSubtotal,
    roundTo,
} from '@/lib/cart';
import type { CartItem } from '@/types';

function item(overrides: Partial<CartItem> = {}): CartItem {
    return {
        product_id: 'PRODUK-1',
        name: 'Kopi Susu',
        price: 12000,
        qty: 1,
        discount: 0,
        ...overrides,
    };
}

describe('lineSubtotal', () => {
    it('mengalikan harga dengan qty', () => {
        expect(lineSubtotal(item({ qty: 3 }))).toBe(36000);
    });

    it('mengurangi diskon per item', () => {
        expect(lineSubtotal(item({ qty: 2, discount: 4000 }))).toBe(20000);
    });

    it('tidak pernah negatif', () => {
        expect(lineSubtotal(item({ qty: 1, discount: 99000 }))).toBe(0);
    });
});

describe('cartSubtotal', () => {
    it('menjumlahkan seluruh baris', () => {
        expect(
            cartSubtotal([
                item({ qty: 2 }),
                item({ product_id: 'PRODUK-2', price: 5000, qty: 3 }),
            ]),
        ).toBe(39000);
    });

    it('bernilai nol untuk keranjang kosong', () => {
        expect(cartSubtotal([])).toBe(0);
    });
});

describe('roundTo', () => {
    it('membulatkan ke kelipatan terdekat', () => {
        expect(roundTo(43290, 100)).toBe(43300);
        expect(roundTo(43240, 100)).toBe(43200);
    });

    it('membulatkan ke bilangan bulat bila step 1 atau kurang', () => {
        expect(roundTo(43290.4, 1)).toBe(43290);
        expect(roundTo(43290.6, 0)).toBe(43291);
    });
});

describe('cartTotals', () => {
    const options = { discount: 0, taxPercent: 11, rounding: 100 };

    it('menghitung subtotal, pajak, dan total', () => {
        const totals = cartTotals(
            [item({ qty: 2 }), item({ product_id: 'PRODUK-2', price: 15000 })],
            options,
        );

        expect(totals.subtotal).toBe(39000);
        expect(totals.discount).toBe(0);
        expect(totals.taxable).toBe(39000);
        expect(totals.tax).toBe(4290);
        expect(totals.total).toBe(43300);
    });

    it('menerapkan diskon transaksi sebelum pajak', () => {
        const totals = cartTotals([item({ qty: 2 })], {
            ...options,
            discount: 4000,
        });

        expect(totals.taxable).toBe(20000);
        expect(totals.tax).toBe(2200);
        expect(totals.total).toBe(22200);
    });

    it('membatasi diskon transaksi maksimal sebesar subtotal', () => {
        const totals = cartTotals([item()], { ...options, discount: 99000 });

        expect(totals.discount).toBe(12000);
        expect(totals.taxable).toBe(0);
        expect(totals.tax).toBe(0);
        expect(totals.total).toBe(0);
    });

    it('menghasilkan nol untuk keranjang kosong', () => {
        expect(cartTotals([], options)).toEqual({
            subtotal: 0,
            discount: 0,
            taxable: 0,
            tax: 0,
            total: 0,
        });
    });

    it('tidak menambahkan pajak bila persen pajak nol', () => {
        const totals = cartTotals([item()], {
            discount: 0,
            taxPercent: 0,
            rounding: 100,
        });

        expect(totals.tax).toBe(0);
        expect(totals.total).toBe(12000);
    });
});

describe('changeFor', () => {
    it('menghitung kembalian', () => {
        expect(changeFor(43300, 50000)).toBe(6700);
    });

    it('mengembalikan nol bila uang belum cukup', () => {
        expect(changeFor(43300, 40000)).toBe(0);
    });
});
