import { computed, ref } from 'vue';
import type { Ref } from 'vue';
import { cartTotals } from '@/lib/cart';
import type { CartTotals } from '@/lib/cart';
import type { CartItem, Product } from '@/types';

type UseCartOptions = {
    taxPercent: number;
    rounding: number;
};

export function useCart(options: UseCartOptions): {
    items: Ref<CartItem[]>;
    discount: Ref<number>;
    totals: Ref<CartTotals>;
    itemCount: Ref<number>;
    addProduct: (product: Product) => void;
    increase: (productId: string) => void;
    decrease: (productId: string) => void;
    remove: (productId: string) => void;
    setItemDiscount: (productId: string, value: number) => void;
    clear: () => void;
} {
    const items = ref<CartItem[]>([]);
    const discount = ref(0);

    const totals = computed<CartTotals>(() =>
        cartTotals(items.value, {
            discount: discount.value,
            taxPercent: options.taxPercent,
            rounding: options.rounding,
        }),
    );

    const itemCount = computed(() =>
        items.value.reduce((sum, item) => sum + item.qty, 0),
    );

    function find(productId: string): CartItem | undefined {
        return items.value.find((item) => item.product_id === productId);
    }

    function addProduct(product: Product): void {
        const existing = find(product.id);

        if (existing) {
            existing.qty += 1;

            return;
        }

        items.value.push({
            product_id: product.id,
            name: product.name,
            price: product.price,
            qty: 1,
            discount: 0,
        });
    }

    function increase(productId: string): void {
        const item = find(productId);

        if (item) {
            item.qty += 1;
        }
    }

    function decrease(productId: string): void {
        const item = find(productId);

        if (!item) {
            return;
        }

        if (item.qty <= 1) {
            remove(productId);

            return;
        }

        item.qty -= 1;
    }

    function remove(productId: string): void {
        items.value = items.value.filter(
            (item) => item.product_id !== productId,
        );
    }

    function setItemDiscount(productId: string, value: number): void {
        const item = find(productId);

        if (item) {
            item.discount = Math.max(0, value);
        }
    }

    function clear(): void {
        items.value = [];
        discount.value = 0;
    }

    return {
        items,
        discount,
        totals,
        itemCount,
        addProduct,
        increase,
        decrease,
        remove,
        setItemDiscount,
        clear,
    };
}
