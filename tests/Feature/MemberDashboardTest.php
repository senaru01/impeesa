<?php

namespace Tests\Feature;

use App\Enums\ClimbStatus;
use App\Models\Climb;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_member_climbing_stats(): void
    {
        $user = User::factory()->create(['name' => 'Isnur Lamiyarni', 'member_number' => 'IMP-0016']);
        Climb::factory()->for($user)->create(['mountain_name' => 'Gunung Rinjani', 'elevation' => 3726]);
        Climb::factory()->for($user)->create(['status' => ClimbStatus::NotSummit, 'elevation' => 3676]);
        Climb::factory()->for($user)->planned()->create(['mountain_name' => 'Gunung Kerinci']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Selamat datang, Isnur')
            ->assertSee('IMP-0016')
            ->assertSee('3.726')
            ->assertSee('50% summit')
            ->assertSee('Gunung Kerinci')
            ->assertSee('H-10');
    }

    public function test_dashboard_map_shows_only_climbed_mountains(): void
    {
        $user = User::factory()->create();
        Climb::factory()->for($user)->create(['mountain_name' => 'Gunung Rinjani']);
        Climb::factory()->for($user)->create(['mountain_name' => 'Rinjani via Torean', 'status' => ClimbStatus::NotSummit]);
        Climb::factory()->for($user)->create(['mountain_name' => 'Gn. Gede', 'status' => ClimbStatus::NotSummit]);
        Climb::factory()->for($user)->create(['mountain_name' => 'Bukit Antah Berantah']);
        Climb::factory()->for($user)->planned()->create(['mountain_name' => 'Gunung Kerinci']);

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $points = collect($response->viewData('mapPoints'))->keyBy('name');

        $this->assertCount(2, $points);
        $rinjani = $points->first(fn (array $point) => str_contains($point['name'], 'Rinjani'));
        $this->assertSame(-8.411, $rinjani['lat']);
        $this->assertCount(2, $rinjani['climbs']);
        $this->assertTrue($rinjani['summit']);
        $this->assertFalse($points['Gn. Gede']['summit']);
        $this->assertSame(['Bukit Antah Berantah'], $response->viewData('unmappedClimbs'));
        $response->assertSee('Jejak Pendakian')->assertSee('2 gunung sudah didaki');
    }

    public function test_sanggabuana_is_a_climb_option_and_appears_on_map(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('climbs.index'))
            ->assertSee('value="Gunung Sanggabuana" data-elevation="1291"', false);

        Climb::factory()->for($user)->create(['mountain_name' => 'Gunung Sanggabuana']);

        $point = collect($this->get(route('dashboard'))->viewData('mapPoints'))->sole();
        $this->assertSame(-6.5908, $point['lat']);
    }

    public function test_dashboard_slideshow_shows_only_approved_shared_photos(): void
    {
        $approved = Photo::factory()->create(['caption' => 'Sunrise Plawangan', 'shared_at' => now(), 'approved_at' => now()]);
        $pending = Photo::factory()->create(['caption' => 'Belum disetujui', 'shared_at' => now()]);
        $private = Photo::factory()->create(['caption' => 'Foto pribadi']);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sunrise Plawangan')
            ->assertSee(route('photos.show', $approved))
            ->assertDontSee(route('photos.show', $pending))
            ->assertDontSee(route('photos.show', $private));
    }

    public function test_dashboard_works_for_a_new_member_without_data(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Belum ada rencana');
    }

    public function test_member_can_record_a_climb(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('climbs.store'), [
                'mountain_name' => 'Gunung Semeru',
                'route' => 'Ranu Pani',
                'elevation' => 3676,
                'started_on' => '2026-08-15',
                'finished_on' => '2026-08-17',
                'status' => 'summit',
            ])
            ->assertRedirect(route('climbs.index'));

        $this->assertDatabaseHas('climbs', ['user_id' => $user->id, 'mountain_name' => 'Gunung Semeru']);
    }

    public function test_climb_photos_go_to_private_gallery_and_map_popup(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('climbs.store'), [
                'mountain_name' => 'Gunung Rinjani',
                'route' => 'Sembalun',
                'started_on' => '2026-08-15',
                'status' => 'summit',
                'photos' => [UploadedFile::fake()->image('puncak.jpg'), UploadedFile::fake()->image('danau.jpg')],
            ])
            ->assertRedirect(route('climbs.index'))
            ->assertSessionHas('status', fn (string $status) => str_contains($status, '2 foto'));

        $climb = $user->climbs()->sole();
        $photos = $user->photos()->get();
        $this->assertCount(2, $photos);
        $this->assertTrue($photos->every(fn ($photo) => $photo->climb_id === $climb->id && ! $photo->isShared()));
        $this->assertSame('Gunung Rinjani via Sembalun', $photos->first()->caption);

        $point = collect($this->get(route('dashboard'))->viewData('mapPoints'))->sole();
        $this->assertCount(2, $point['photos']);
        $this->assertContains(route('photos.show', $photos->first()), array_column($point['photos'], 'url'));
    }

    public function test_photos_can_be_added_when_editing_a_climb(): void
    {
        Storage::fake('local');
        $climb = Climb::factory()->create();

        $this->actingAs($climb->user)
            ->put(route('climbs.update', $climb), [
                'mountain_name' => $climb->mountain_name,
                'started_on' => $climb->started_on->format('Y-m-d'),
                'status' => 'summit',
                'photos' => [UploadedFile::fake()->image('a.jpg')],
            ])
            ->assertRedirect(route('climbs.index'));

        $this->assertSame(1, $climb->photos()->count());
    }

    public function test_member_cannot_edit_someone_elses_climb(): void
    {
        $climb = Climb::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('climbs.update', $climb), [
                'mountain_name' => 'Diubah',
                'started_on' => '2026-01-01',
                'status' => 'summit',
            ])
            ->assertForbidden();
    }

    public function test_member_can_update_own_profile_but_not_role(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Nama Baru',
                'email' => $user->email,
                'blood_type' => 'O',
                'role' => 'superadmin',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('O', $user->blood_type);
        $this->assertFalse($user->isSuperAdmin());
    }
}
