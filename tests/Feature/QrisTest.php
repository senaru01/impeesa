<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrisTest extends TestCase
{
    use RefreshDatabase;

    /**
     * QRIS statis GoPay toko (hasil scan), CRC aslinya 06CB.
     */
    private const STATIC_PAYLOAD = '00020101021126610014COM.GO-JEK.WWW01189360091435144024770210G5144024770303UMI51440014ID.CO.QRIS.WWW0215ID10266404525240303UMI5204594753033605802ID5916Impeesa 3D Print6006BEKASI61051753062070703A01630406CB';

    public function test_crc_matches_the_official_static_qris(): void
    {
        $this->assertSame('06CB', Sale::crc16(substr(self::STATIC_PAYLOAD, 0, -4)));
    }

    public function test_static_qris_becomes_dynamic_with_amount(): void
    {
        $payload = Sale::dynamicQris(self::STATIC_PAYLOAD, 42500);

        $this->assertStringStartsWith('000201010212', $payload);
        $this->assertStringContainsString('53033605405425005802ID', $payload);
        $this->assertStringContainsString('5916Impeesa 3D Print', $payload);
        $this->assertSame(Sale::crc16(substr($payload, 0, -4)), substr($payload, -4));
        $this->assertSame(1, substr_count($payload, '6304'));

        // Membuat ulang dari QRIS yang sudah bernominal hanya mengganti nominalnya.
        $again = Sale::dynamicQris($payload, 18000);
        $this->assertStringContainsString('54051800058', $again);
        $this->assertStringNotContainsString('42500', $again);
    }

    public function test_receipt_shows_qris_with_amount_only_for_qris_sales(): void
    {
        config(['services.qris.payload' => self::STATIC_PAYLOAD]);
        $admin = User::factory()->create(['role' => Role::SuperAdmin]);
        $qrisSale = Sale::factory()->create(['payment_method' => PaymentMethod::Qris, 'total' => 42500, 'paid_amount' => 42500]);
        $cashSale = Sale::factory()->create(['payment_method' => PaymentMethod::Cash]);

        $this->actingAs($admin)
            ->get(route('admin.store.sales.receipt', $qrisSale))
            ->assertOk()
            ->assertSee('Scan QRIS untuk bayar')
            ->assertSee('<svg', false);

        $this->get(route('admin.store.sales.show', $qrisSale))->assertOk()->assertSee('Scan QRIS untuk bayar');
        $this->get(route('admin.store.sales.receipt', $cashSale))->assertOk()->assertDontSee('Scan QRIS');

        config(['services.qris.payload' => null]);
        $this->get(route('admin.store.sales.receipt', $qrisSale))->assertOk()->assertDontSee('Scan QRIS');
    }
}
