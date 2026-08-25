<?php

namespace Tests\Feature\Stores;

use App\Models\User;
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
        return [
            'name' => 'Toko Sudirman',
            'code' => 'SDR',
            'address' => 'Jl. Jend. Sudirman No. 12',
            'phone' => '021-5550112',
            'currency' => 'IDR',
            'tax_percent' => 11,
            'rounding' => 100,
            'receipt_header' => 'Toko Sudirman',
            'receipt_footer' => 'Terima kasih',
            'paper_size' => '58mm',
            'open_time' => '08:00',
            'close_time' => '21:00',
            'is_active' => true,
        ];
    }

    public function test_paper_size_must_be_supported(): void
    {
        $this->actingAs(User::factory()->create());

        $payload = $this->validPayload();
        $payload['paper_size'] = 'A4';

        $this->from(route('stores.settings.edit', ['store' => 1]))
            ->put(route('stores.settings.update', ['store' => 1]), $payload)
            ->assertSessionHasErrors('paper_size');
    }

    public function test_times_must_use_hour_minute_format(): void
    {
        $this->actingAs(User::factory()->create());

        $payload = $this->validPayload();
        $payload['open_time'] = '8 pagi';
        $payload['close_time'] = '';

        $this->from(route('stores.settings.edit', ['store' => 1]))
            ->put(route('stores.settings.update', ['store' => 1]), $payload)
            ->assertSessionHasErrors(['open_time', 'close_time']);
    }

    public function test_tax_percent_must_be_between_zero_and_hundred(): void
    {
        $this->actingAs(User::factory()->create());

        $payload = $this->validPayload();
        $payload['tax_percent'] = 120;

        $this->from(route('stores.settings.edit', ['store' => 1]))
            ->put(route('stores.settings.update', ['store' => 1]), $payload)
            ->assertSessionHasErrors('tax_percent');
    }

    public function test_valid_settings_return_demo_flash(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('stores.settings.edit', ['store' => 1]))
            ->put(route('stores.settings.update', ['store' => 1]), $this->validPayload())
            ->assertSessionHas('success');
    }
}
