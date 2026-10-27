<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedPhotoCaptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_renames_one_shared_photo_without_touching_the_others(): void
    {
        [$first, $second] = Photo::factory()->count(2)->create([
            'user_id' => null,
            'caption' => 'Gunung Semeru',
            'shared_at' => now(),
            'approved_at' => now(),
        ]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('photos.shared'))
            ->assertSee(route('photos.caption', $first));

        $this->from(route('photos.shared'))
            ->put(route('photos.caption', $first), ['caption' => 'Ranu Kumbolo'])
            ->assertRedirect(route('photos.shared').'#foto-'.$first->id)
            ->assertSessionHas('status', 'Keterangan foto diubah menjadi "Ranu Kumbolo".');

        $this->assertSame('Ranu Kumbolo', $first->fresh()->caption);
        $this->assertSame('Gunung Semeru', $second->fresh()->caption);
    }

    public function test_super_admin_can_rename_pending_photo_before_approving(): void
    {
        $photo = Photo::factory()->create(['shared_at' => now(), 'caption' => 'Gunung Semeru']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('photos.shared'))
            ->assertSee(route('photos.caption', $photo));

        $this->put(route('photos.caption', $photo), ['caption' => 'Pos 2 Watu Rejeng'])->assertRedirect();

        $this->assertSame('Pos 2 Watu Rejeng', $photo->fresh()->caption);
    }

    public function test_member_cannot_rename_shared_photo_even_their_own(): void
    {
        $owner = User::factory()->create();
        $photo = Photo::factory()->for($owner)->create(['shared_at' => now(), 'approved_at' => now(), 'caption' => 'Gunung Semeru']);

        $this->actingAs($owner)
            ->get(route('photos.shared'))
            ->assertDontSee(route('photos.caption', $photo));

        $this->put(route('photos.caption', $photo), ['caption' => 'Ranu Kumbolo'])->assertForbidden();

        $this->assertSame('Gunung Semeru', $photo->fresh()->caption);
    }

    public function test_private_photo_cannot_be_renamed(): void
    {
        $photo = Photo::factory()->create(['caption' => 'Pribadi']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('photos.caption', $photo), ['caption' => 'Diubah'])
            ->assertForbidden();
    }

    public function test_too_long_caption_is_rejected_back_to_the_photo(): void
    {
        $photo = Photo::factory()->create(['user_id' => null, 'shared_at' => now(), 'approved_at' => now(), 'caption' => 'Gunung Semeru']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->from(route('photos.shared'))
            ->put(route('photos.caption', $photo), ['caption' => str_repeat('a', 151)])
            ->assertRedirect(route('photos.shared').'#foto-'.$photo->id)
            ->assertSessionHasErrors('caption', errorBag: 'caption');

        $this->assertSame('Gunung Semeru', $photo->fresh()->caption);
    }
}
