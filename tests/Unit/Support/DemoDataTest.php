<?php

namespace Tests\Unit\Support;

use App\Support\DemoData;
use PHPUnit\Framework\TestCase;

class DemoDataTest extends TestCase
{
    public function test_it_provides_three_stores_with_required_keys(): void
    {
        $stores = DemoData::stores();

        $this->assertCount(3, $stores);

        foreach ($stores as $store) {
            $this->assertSame(
                ['id', 'name', 'code', 'address', 'phone', 'is_active', 'products_count'],
                array_keys($store),
            );
        }
    }

    public function test_store_returns_null_for_unknown_id(): void
    {
        $this->assertNull(DemoData::store(999));
        $this->assertNotNull(DemoData::store(1));
    }

    public function test_store_options_only_expose_id_name_and_code(): void
    {
        foreach (DemoData::storeOptions() as $option) {
            $this->assertSame(['id', 'name', 'code'], array_keys($option));
        }
    }

    public function test_every_store_has_categories_and_products(): void
    {
        foreach (DemoData::stores() as $store) {
            $this->assertGreaterThanOrEqual(5, count(DemoData::categories($store['id'])));
            $this->assertGreaterThanOrEqual(20, count(DemoData::products($store['id'])));
        }
    }

    public function test_products_reference_existing_categories_of_the_same_store(): void
    {
        foreach (DemoData::stores() as $store) {
            $categoryIds = array_column(DemoData::categories($store['id']), 'id');

            foreach (DemoData::products($store['id']) as $product) {
                $this->assertContains($product['category_id'], $categoryIds);
            }
        }
    }

    public function test_products_count_on_store_matches_product_list(): void
    {
        foreach (DemoData::stores() as $store) {
            $this->assertSame(
                $store['products_count'],
                count(DemoData::products($store['id'])),
            );
        }
    }

    public function test_transactions_have_items_that_sum_to_total(): void
    {
        foreach (DemoData::transactions(1) as $transaction) {
            $sum = array_sum(array_column($transaction['items'], 'subtotal'));
            $this->assertSame($transaction['total'], $sum);
            $this->assertSame($transaction['items_count'], count($transaction['items']));
        }
    }

    public function test_dashboard_and_settings_expose_expected_keys(): void
    {
        $dashboard = DemoData::dashboard(1);
        $this->assertSame(
            ['sales_today', 'transactions_today', 'items_sold', 'average_per_transaction', 'recent_transactions'],
            array_keys($dashboard),
        );

        $settings = DemoData::settings(1);
        $this->assertSame(
            ['name', 'code', 'address', 'phone', 'currency', 'tax_percent', 'rounding', 'receipt_header', 'receipt_footer', 'paper_size', 'open_time', 'close_time', 'is_active'],
            array_keys($settings),
        );
    }

    public function test_unknown_store_yields_empty_collections(): void
    {
        $this->assertSame([], DemoData::categories(999));
        $this->assertSame([], DemoData::products(999));
        $this->assertSame([], DemoData::transactions(999));
    }

    /**
     * Penjaga regresi: stores() menghitung products_count lewat products(),
     * sedangkan products() butuh kode toko. Bila kode toko dibaca lewat
     * store() (yang memanggil stores()), test ini akan hang / stack overflow.
     */
    public function test_building_stores_does_not_recurse(): void
    {
        $stores = DemoData::stores();

        $this->assertSame('SDR', $stores[0]['code']);
        $this->assertSame('SDR-001', DemoData::products(1)[0]['sku']);
    }
}
