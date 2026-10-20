<?php

namespace Tests\Feature;

use App\Models\Climb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmergencyCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_text_holds_offline_data_and_detail_link_but_not_id_card(): void
    {
        $user = $this->memberWithEmergencyData();

        $text = $user->emergencyQrText();

        $this->assertStringContainsString('Nama: Isnur Lamiyarni', $text);
        $this->assertStringContainsString('Gol. darah: O', $text);
        $this->assertStringContainsString('Alergi: Penisilin', $text);
        $this->assertStringContainsString('Kontak darurat: Budi (Suami) 081234567890', $text);
        $this->assertStringContainsString(route('emergency.show', $user->emergency_token), $text);
        $this->assertStringNotContainsString('5271012345678901', $text);
    }

    public function test_member_can_open_printable_card(): void
    {
        $user = $this->memberWithEmergencyData();

        $this->actingAs($user)
            ->get(route('emergency.card'))
            ->assertOk()
            ->assertSee('Cetak kartu')
            ->assertSee('Penisilin');
    }

    public function test_detail_page_opens_without_login_and_shows_id_card_data(): void
    {
        $user = $this->memberWithEmergencyData();
        Climb::factory()->for($user)->planned()->create(['mountain_name' => 'Gunung Rinjani', 'route' => 'Sembalun']);

        $this->get(route('emergency.show', $user->emergencyToken()))
            ->assertOk()
            ->assertSee('5271012345678901')
            ->assertSee('Penisilin')
            ->assertSee('tel:081234567890', false)
            ->assertSee('Gunung Rinjani')
            ->assertSee('noindex', false);
    }

    public function test_unknown_or_old_token_returns_not_found(): void
    {
        $user = $this->memberWithEmergencyData();
        $oldToken = $user->emergencyToken();

        $this->actingAs($user)->post(route('emergency.regenerate'))->assertRedirect();

        $this->get(route('emergency.show', $oldToken))->assertNotFound();
        $this->get(route('emergency.show', 'token-palsu'))->assertNotFound();
        $this->get(route('emergency.show', $user->fresh()->emergency_token))->assertOk();
    }

    public function test_inactive_member_detail_page_is_hidden(): void
    {
        $user = $this->memberWithEmergencyData();
        $token = $user->emergencyToken();
        $user->update(['is_active' => false]);

        $this->get(route('emergency.show', $token))->assertNotFound();
    }

    public function test_nik_is_encrypted_at_rest(): void
    {
        $user = $this->memberWithEmergencyData();

        $stored = DB::table('users')->where('id', $user->id)->value('nik');

        $this->assertNotSame('5271012345678901', $stored);
        $this->assertSame('5271012345678901', $user->fresh()->nik);
    }

    public function test_member_saves_id_card_and_medical_data_via_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'nik' => '5271012345678901',
                'religion' => 'Islam',
                'marital_status' => 'Kawin',
                'allergies' => 'Penisilin',
                'emergency_contact_relation' => 'Suami',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Penisilin', $user->fresh()->allergies);
    }

    public function test_invalid_nik_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => $user->name, 'email' => $user->email, 'nik' => '12345'])
            ->assertSessionHasErrors('nik');
    }

    public function test_super_admin_can_print_member_card_but_member_cannot_print_others(): void
    {
        $member = $this->memberWithEmergencyData();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.members.emergency-card', $member))
            ->assertOk()
            ->assertSee('Isnur Lamiyarni');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.members.emergency-card', $member))
            ->assertForbidden();
    }

    private function memberWithEmergencyData(): User
    {
        return User::factory()->create([
            'name' => 'Isnur Lamiyarni',
            'blood_type' => 'O',
            'allergies' => 'Penisilin',
            'emergency_contact_name' => 'Budi',
            'emergency_contact_relation' => 'Suami',
            'emergency_contact_phone' => '081234567890',
            'nik' => '5271012345678901',
            'id_card_address' => 'Jl. Pariwisata Senaru, Bayan, Lombok Utara',
        ]);
    }
}
