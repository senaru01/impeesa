<?php

namespace Tests\Feature;

use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_open_admin_pages(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.members.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_list_and_search_members(): void
    {
        User::factory()->create(['name' => 'Kiki Dwi Oktapiani']);
        User::factory()->create(['name' => 'Musdyanto']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.members.index', ['q' => 'Kiki']))
            ->assertOk()
            ->assertSee('Kiki Dwi Oktapiani')
            ->assertDontSee('Musdyanto');
    }

    public function test_super_admin_can_open_story_moderation_page(): void
    {
        Story::factory()->public()->create(['title' => 'Kabut di Segara Anak']);
        Story::factory()->create(['title' => 'Catatan privat']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.stories.index'))
            ->assertOk()
            ->assertSee('Kabut di Segara Anak')
            ->assertSee('Tampilkan di beranda')
            ->assertSee('Catatan privat')
            ->assertSee('pemilik perlu menjadikannya publik dulu');
    }

    public function test_super_admin_can_create_member_account(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.members.store'), [
                'name' => 'Moch Salvareja',
                'email' => 'salva@example.com',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
                'role' => 'member',
                'is_active' => '1',
                'member_number' => 'IMP-0042',
                'position' => 'Logistik',
            ])
            ->assertRedirect();

        $member = User::where('email', 'salva@example.com')->sole();
        $this->assertSame('IMP-0042', $member->member_number);
        $this->assertTrue($member->is_active);
        $this->assertTrue(Hash::check('rahasia123', $member->password));
    }

    public function test_super_admin_can_deactivate_member(): void
    {
        $member = User::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('admin.members.update', $member), [
                'name' => $member->name,
                'email' => $member->email,
                'role' => 'member',
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.members.show', $member));

        $this->assertFalse($member->fresh()->is_active);
    }

    public function test_super_admin_cannot_demote_themself(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->put(route('admin.members.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'member',
                'is_active' => '0',
            ]);

        $admin->refresh();
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertTrue($admin->is_active);
    }

    public function test_super_admin_sees_member_private_gallery_on_detail_page(): void
    {
        $member = User::factory()->hasPhotos(2)->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.members.show', $member))
            ->assertOk()
            ->assertSee(route('photos.show', $member->photos->first()));
    }
}
