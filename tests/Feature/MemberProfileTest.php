<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ImpeesaMemberSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_only_featured_profiles(): void
    {
        User::factory()->create(['name' => 'Ahmad Rinjani', 'bio' => 'Petualang sejati.', 'profile_featured_at' => now()]);
        User::factory()->create(['name' => 'Anggota Tersembunyi']);
        User::factory()->inactive()->create(['name' => 'Akun Nonaktif', 'profile_featured_at' => now()]);

        $this->get(route('profiles.index'))
            ->assertOk()
            ->assertSee('Ahmad Rinjani')
            ->assertSee('Petualang sejati.')
            ->assertDontSee('Anggota Tersembunyi')
            ->assertDontSee('Akun Nonaktif');
    }

    public function test_profile_photo_is_public_only_while_profile_is_featured(): void
    {
        Storage::fake('local');
        $featured = User::factory()->create(['profile_featured_at' => now()]);
        $featured->replaceAvatar(UploadedFile::fake()->image('a.jpg'));
        $hidden = User::factory()->create();
        $hidden->replaceAvatar(UploadedFile::fake()->image('b.jpg'));

        $this->get(route('profiles.photo', $featured))->assertOk();
        $this->get(route('profiles.photo', $hidden))->assertNotFound();
    }

    public function test_landing_page_shows_featured_profiles(): void
    {
        User::factory()->create(['name' => 'Waluyo', 'avatar_path' => 'avatars/1/a.jpg', 'profile_featured_at' => now()]);

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Profil Singkat Anggota')
            ->assertSee('Waluyo');
    }

    public function test_super_admin_can_feature_and_unfeature_member_profile(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $member = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.members.profile.feature', $member))->assertRedirect();
        $this->assertTrue($member->fresh()->isProfileFeatured());

        $this->actingAs($admin)->delete(route('admin.members.profile.unfeature', $member))->assertRedirect();
        $this->assertFalse($member->fresh()->isProfileFeatured());
    }

    public function test_member_cannot_feature_profiles(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)
            ->post(route('admin.members.profile.feature', $member))
            ->assertForbidden();

        $this->assertFalse($member->fresh()->isProfileFeatured());
    }

    public function test_seeder_creates_members_with_temporary_password_and_featured_profiles(): void
    {
        Storage::fake('local');

        $this->seed(ImpeesaMemberSeeder::class);
        $this->seed(ImpeesaMemberSeeder::class);

        $this->assertSame(30, User::featuredProfile()->count());

        $member = User::where('email', 'ahmad.rinjani@impeesa-xtream.com')->sole();
        $this->assertTrue(Hash::check(ImpeesaMemberSeeder::TEMPORARY_PASSWORD, $member->password));
        $this->assertSame('Ranu Kumbolo', $member->signature_place);
        Storage::disk('local')->assertExists($member->avatar_path);
    }
}
