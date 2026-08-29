<?php

namespace Tests\Feature\Stores;

use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        // Identitas toko dan status aktif sudah pindah ke wewenang owner
        // (StoreFormRequest), jadi tidak lagi bagian dari payload ini.
        return [
            'currency' => 'IDR',
            'tax_percent' => 11,
            'rounding' => 100,
            'receipt_header' => 'Toko Sudirman',
            'receipt_footer' => 'Terima kasih',
            'paper_size' => '58mm',
            'open_time' => '08:00',
            'close_time' => '21:00',
            'printer_connector' => 'none',
            'printer_target' => '',
            'printer_channel' => 1,
            'printer_feed_lines' => 1,
            'printer_auto_print' => true,
        ];
    }

    public function test_paper_size_must_be_supported(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));

        $payload = $this->validPayload();
        $payload['paper_size'] = 'A4';

        $this->from(route('stores.settings.edit', ['store' => $store]))
            ->put(route('stores.settings.update', ['store' => $store]), $payload)
            ->assertSessionHasErrors('paper_size');
    }

    public function test_times_must_use_hour_minute_format(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));

        $payload = $this->validPayload();
        $payload['open_time'] = '8 pagi';
        $payload['close_time'] = '';

        $this->from(route('stores.settings.edit', ['store' => $store]))
            ->put(route('stores.settings.update', ['store' => $store]), $payload)
            ->assertSessionHasErrors(['open_time', 'close_time']);
    }

    public function test_tax_percent_must_be_between_zero_and_hundred(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));

        $payload = $this->validPayload();
        $payload['tax_percent'] = 120;

        $this->from(route('stores.settings.edit', ['store' => $store]))
            ->put(route('stores.settings.update', ['store' => $store]), $payload)
            ->assertSessionHasErrors('tax_percent');
    }

    public function test_setting_fields_reject_values_longer_than_their_max_length(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));

        $payload = $this->validPayload();
        $payload['currency'] = str_repeat('A', 6);
        $payload['receipt_header'] = str_repeat('a', 121);
        // Footer kini kolom TEXT banyak baris; batasnya 2000, bukan 120 lagi.
        $payload['receipt_footer'] = str_repeat('a', 2001);

        $this->from(route('stores.settings.edit', ['store' => $store]))
            ->put(route('stores.settings.update', ['store' => $store]), $payload)
            ->assertSessionHasErrors([
                'currency',
                'receipt_header',
                'receipt_footer',
            ]);
    }

    public function test_valid_settings_are_saved_to_the_store(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($this->storeAdmin($store));

        $this->from(route('stores.settings.edit', ['store' => $store]))
            ->put(route('stores.settings.update', ['store' => $store]), $this->validPayload())
            ->assertSessionHas('success');

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'tax_percent' => 11,
            'rounding' => 100,
            'paper_size' => '58mm',
            'open_time' => '08:00',
        ]);
    }
}
