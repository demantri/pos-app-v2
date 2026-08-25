import type { CartItem } from '@/types';

export type CartOptions = {
    discount: number;
    taxPercent: number;
    rounding: number;
};

export type CartTotals = {
    subtotal: number;
    discount: number;
    taxable: number;
    tax: number;
    total: number;
};

export function lineSubtotal(item: CartItem): number {
    return Math.max(0, item.price * item.qty - item.discount);
}

export function cartSubtotal(items: CartItem[]): number {
    return items.reduce((sum, item) => sum + lineSubtotal(item), 0);
}

export function roundTo(value: number, step: number): number {
    if (step <= 1) {
        return Math.round(value);
    }

    return Math.round(value / step) * step;
}

export function cartTotals(
    items: CartItem[],
    options: CartOptions,
): CartTotals {
    const subtotal = cartSubtotal(items);
    const discount = Math.min(Math.max(0, options.discount), subtotal);
    const taxable = subtotal - discount;
    const tax = Math.round((taxable * options.taxPercent) / 100);
    const total = taxable === 0 ? 0 : roundTo(taxable + tax, options.rounding);

    return { subtotal, discount, taxable, tax, total };
}

export function changeFor(total: number, paid: number): number {
    return Math.max(0, paid - total);
}
