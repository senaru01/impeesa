<?php

namespace Tests\Feature;

use App\Enums\ClimbStatus;
use App\Enums\ParticipantStatus;
use App\Models\Climb;
use App\Models\ClimbParticipant;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClimbParticipantTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_invites_members_who_see_the_invitation(): void
    {
        $organizer = User::factory()->create(['name' => 'Fatah Senaru']);
        $irsyad = User::factory()->create();
        $wawan = User::factory()->create();

        $this->actingAs($organizer)
            ->post(route('climbs.store'), $this->climbPayload(['participants' => [$irsyad->id, $wawan->id]]))
            ->assertRedirect(route('climbs.index'))
            ->assertSessionHas('status', fn (string $status) => str_contains($status, '2 anggota diajak'));

        $climb = $organizer->climbs()->sole();
        $this->assertSame(2, $climb->participants()->where('status', ParticipantStatus::Pending)->count());

        $this->actingAs($irsyad)
            ->get(route('dashboard'))
            ->assertSee('Fatah mengajakmu ke Gunung Rinjani')
            ->assertSee(route('invitations.accept', $climb->participants()->where('user_id', $irsyad->id)->sole()));
    }

    public function test_accepting_creates_the_climb_in_participant_records(): void
    {
        [$climb, $participant] = $this->invitation();

        $this->actingAs($participant->user)
            ->post(route('invitations.accept', $participant))
            ->assertRedirect();

        $participant->refresh();
        $this->assertSame(ParticipantStatus::Accepted, $participant->status);

        $copy = $participant->user->climbs()->sole();
        $this->assertSame($participant->joined_climb_id, $copy->id);
        $this->assertSame('Gunung Rinjani', $copy->mountain_name);
        $this->assertSame(ClimbStatus::Planned, $copy->status);
        $this->assertTrue($copy->started_on->isSameDay($climb->started_on));
        $this->assertSame([$climb->user->id], $copy->companions()->pluck('id')->all());

        $this->actingAs($participant->user)
            ->get(route('dashboard'))
            ->assertSee('bersama '.$climb->user->display_name);
    }

    public function test_declining_after_accepting_removes_the_copy(): void
    {
        [, $participant] = $this->invitation();
        $this->actingAs($participant->user)->post(route('invitations.accept', $participant));

        $this->actingAs($participant->user)
            ->post(route('invitations.decline', $participant->fresh()))
            ->assertRedirect();

        $this->assertSame(ParticipantStatus::Declined, $participant->fresh()->status);
        $this->assertSame(0, $participant->user->climbs()->count());
    }

    public function test_member_cannot_answer_someone_elses_invitation(): void
    {
        [, $participant] = $this->invitation();

        $this->actingAs(User::factory()->create())
            ->post(route('invitations.accept', $participant))
            ->assertForbidden();

        $this->assertSame(ParticipantStatus::Pending, $participant->fresh()->status);
    }

    public function test_organizer_changes_sync_to_copies_and_removed_members_lose_invitation(): void
    {
        [$climb, $participant] = $this->invitation();
        $this->actingAs($participant->user)->post(route('invitations.accept', $participant));
        $copyId = $participant->fresh()->joined_climb_id;
        $other = User::factory()->create();

        $this->actingAs($climb->user)
            ->put(route('climbs.update', $climb), $this->climbPayload([
                'started_on' => now()->addDays(20)->format('Y-m-d'),
                'route' => 'Torean',
                'participants' => [$other->id],
            ]))
            ->assertRedirect(route('climbs.index'));

        $this->assertModelMissing($participant);
        $this->assertSame(1, $climb->participants()->where('user_id', $other->id)->count());

        // Peserta yang dikeluarkan tetap menyimpan salinannya sendiri.
        $this->assertNotNull(Climb::find($copyId));
    }

    public function test_accepted_copy_follows_organizer_date_changes(): void
    {
        [$climb, $participant] = $this->invitation();
        $this->actingAs($participant->user)->post(route('invitations.accept', $participant));

        $newDate = now()->addDays(25)->format('Y-m-d');
        $this->actingAs($climb->user)
            ->put(route('climbs.update', $climb), $this->climbPayload([
                'started_on' => $newDate,
                'route' => 'Torean',
                'participants' => [$participant->user_id],
            ]));

        $copy = $participant->user->climbs()->sole();
        $this->assertSame($newDate, $copy->started_on->format('Y-m-d'));
        $this->assertSame('Torean', $copy->route);
    }

    public function test_cannot_invite_yourself_or_inactive_members(): void
    {
        $organizer = User::factory()->create();
        $inactive = User::factory()->inactive()->create();

        $this->actingAs($organizer)
            ->post(route('climbs.store'), $this->climbPayload(['participants' => [$organizer->id, $inactive->id]]))
            ->assertSessionHasErrors(['participants.0', 'participants.1']);

        $this->assertSame(0, Climb::count());
    }

    public function test_emergency_page_lists_climbing_companions(): void
    {
        [$climb, $participant] = $this->invitation();
        $this->actingAs($participant->user)->post(route('invitations.accept', $participant));

        $this->get(route('emergency.show', $climb->user->emergencyToken()))
            ->assertOk()
            ->assertSee('Rombongan')
            ->assertSee($participant->user->name);
    }

    public function test_first_uploaded_photo_becomes_main_photo_and_can_be_changed(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('climbs.store'), $this->climbPayload([
            'status' => 'summit',
            'started_on' => '2026-08-15',
            'photos' => [UploadedFile::fake()->image('utama.jpg'), UploadedFile::fake()->image('lain.jpg'), UploadedFile::fake()->image('lain2.jpg')],
        ]));

        $climb = $user->climbs()->sole();
        $photos = $climb->photos()->orderBy('id')->get();
        $this->assertCount(3, $photos);
        $this->assertSame($photos[0]->id, $climb->cover_photo_id);
        $this->assertSame(3, $user->photos()->count());

        $this->actingAs($user)->put(route('climbs.cover', [$climb, $photos[2]]))->assertRedirect();
        $this->assertSame($photos[2]->id, $climb->fresh()->cover_photo_id);

        $mapPhotos = collect($this->actingAs($user)->get(route('dashboard'))->viewData('mapPoints'))->sole()['photos'];
        $this->assertSame(route('photos.show', $photos[2]), $mapPhotos[0]['url']);
    }

    public function test_cannot_use_photo_from_another_climb_as_main_photo(): void
    {
        $climb = Climb::factory()->create();
        $otherPhoto = Photo::factory()->for($climb->user)->create();

        $this->actingAs($climb->user)
            ->put(route('climbs.cover', [$climb, $otherPhoto]))
            ->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function climbPayload(array $overrides = []): array
    {
        return [
            'mountain_name' => 'Gunung Rinjani',
            'route' => 'Sembalun',
            'elevation' => 3726,
            'started_on' => now()->addDays(10)->format('Y-m-d'),
            'finished_on' => null,
            'status' => 'planned',
            ...$overrides,
        ];
    }

    /**
     * @return array{0: Climb, 1: ClimbParticipant}
     */
    private function invitation(): array
    {
        $organizer = User::factory()->create();
        $guest = User::factory()->create();

        $this->actingAs($organizer)->post(route('climbs.store'), $this->climbPayload(['participants' => [$guest->id]]));
        auth()->logout();

        $climb = $organizer->climbs()->sole();

        return [$climb, $climb->participants()->sole()];
    }
}
