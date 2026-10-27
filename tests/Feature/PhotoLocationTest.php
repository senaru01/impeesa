<?php

namespace Tests\Feature;

use App\Models\Climb;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sets_location_and_photo_appears_on_explorer_map(): void
    {
        $owner = User::factory()->create();
        $photo = Photo::factory()->for($owner)->create(['shared_at' => now(), 'approved_at' => now()]);

        $this->actingAs($owner)
            ->put(route('photos.location', $photo), ['mountain_name' => 'Gunung Rinjani'])
            ->assertRedirect()
            ->assertSessionHas('status', 'Lokasi foto diatur ke Gunung Rinjani.');

        $this->assertSame('Gunung Rinjani', $photo->fresh()->mountain_name);

        $explore = $this->get(route('photos.shared'))->viewData('explore');
        $this->assertSame(0, $explore['slides'][0]['place']);
        $this->assertSame('Gunung Rinjani', $explore['places'][0]['name']);
        $this->assertSame(-8.411, $explore['places'][0]['lat']);
    }

    public function test_gunung_batu_is_located_in_jonggol_not_bali(): void
    {
        foreach (['Gunung Batu', 'Gn. Batu', 'Gunung Batu Jonggol'] as $name) {
            $this->assertSame(-6.552, Climb::locateMountain($name)['lat'] ?? null, $name);
        }

        $this->assertSame(-8.24519, Climb::locateMountain('Gunung Batur')['lat']);
    }

    public function test_sender_of_shared_only_photo_and_super_admin_can_set_location(): void
    {
        $sender = User::factory()->create();
        $photo = Photo::factory()->create(['user_id' => null, 'uploaded_by' => $sender->id, 'shared_at' => now()]);

        $this->actingAs($sender)->put(route('photos.location', $photo), ['mountain_name' => 'Gn. Gede'])->assertRedirect();
        $this->actingAs(User::factory()->superAdmin()->create())->put(route('photos.location', $photo), ['mountain_name' => 'Gunung Kerinci'])->assertRedirect();

        $this->assertSame('Gunung Kerinci', $photo->fresh()->mountain_name);
    }

    public function test_other_member_cannot_set_location(): void
    {
        $photo = Photo::factory()->create(['shared_at' => now(), 'approved_at' => now()]);

        $this->actingAs(User::factory()->create())
            ->put(route('photos.location', $photo), ['mountain_name' => 'Gunung Rinjani'])
            ->assertForbidden();

        $this->assertNull($photo->fresh()->mountain_name);
    }

    public function test_climb_photo_location_follows_its_climb(): void
    {
        $owner = User::factory()->create();
        $climb = Climb::factory()->for($owner)->create(['mountain_name' => 'Gunung Rinjani']);
        $photo = Photo::factory()->for($owner)->for($climb)->create();

        $this->actingAs($owner)
            ->put(route('photos.location', $photo), ['mountain_name' => 'Gunung Kerinci'])
            ->assertForbidden();

        $this->get(route('photos.index'))
            ->assertOk()
            ->assertDontSee(route('photos.location', $photo))
            ->assertSee('Gunung Rinjani');
    }

    public function test_unknown_mountain_is_rejected_and_empty_value_clears_location(): void
    {
        $owner = User::factory()->create();
        $photo = Photo::factory()->for($owner)->create(['mountain_name' => 'Gunung Rinjani']);

        $this->actingAs($owner)
            ->put(route('photos.location', $photo), ['mountain_name' => 'Bukit Antah Berantah'])
            ->assertSessionHasErrors('mountain_name', errorBag: 'location');
        $this->assertSame('Gunung Rinjani', $photo->fresh()->mountain_name);

        $this->put(route('photos.location', $photo), ['mountain_name' => ''])
            ->assertSessionHas('status', 'Lokasi foto dihapus.');
        $this->assertNull($photo->fresh()->mountain_name);
    }

    public function test_upload_form_stores_location(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->get(route('photos.shared'))
            ->assertSee('Gunung / lokasi');

        $this->post(route('photos.shared.store'), [
            'photos' => [UploadedFile::fake()->image('a.jpg')],
            'mountain_name' => 'Gunung Rinjani',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Gunung Rinjani', Photo::sole()->mountain_name);
    }
}
