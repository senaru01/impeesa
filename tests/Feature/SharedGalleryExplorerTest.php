<?php

namespace Tests\Feature;

use App\Models\Climb;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedGalleryExplorerTest extends TestCase
{
    use RefreshDatabase;

    public function test_explorer_orders_approved_photos_by_date_and_groups_them_by_mountain(): void
    {
        $owner = User::factory()->create();
        $rinjani = Climb::factory()->for($owner)->create(['mountain_name' => 'Gunung Rinjani']);
        $gede = Climb::factory()->for($owner)->create(['mountain_name' => 'Gn. Gede']);

        $this->sharedPhoto($owner, $gede, '2026-03-01', 'Alun-alun Suryakencana');
        $this->sharedPhoto($owner, $rinjani, '2026-01-10', 'Plawangan');
        $this->sharedPhoto($owner, $rinjani, '2026-05-02', 'Segara Anak');
        $this->sharedPhoto($owner, null, '2026-02-01', 'Kumpul tim');
        Photo::factory()->for($owner)->for($rinjani)->create(['shared_at' => now(), 'caption' => 'Masih menunggu']);

        $explore = $this->actingAs(User::factory()->create())
            ->get(route('photos.shared'))
            ->assertOk()
            ->assertSee('Jelajah galeri')
            ->viewData('explore');

        $this->assertSame(['Plawangan', 'Kumpul tim', 'Alun-alun Suryakencana', 'Segara Anak'], array_column($explore['slides'], 'caption'));
        $this->assertSame([0, null, 1, 0], array_column($explore['slides'], 'place'));
        $this->assertSame(['Gunung Rinjani', 'Gn. Gede'], array_column($explore['places'], 'name'));
        $this->assertSame(-8.411, $explore['places'][0]['lat']);
        $this->assertSame(2, $explore['places'][0]['count']);
        $this->assertSame([['id' => $owner->id, 'name' => $owner->display_name, 'count' => 4]], $explore['members']);
    }

    public function test_explorer_is_hidden_when_gallery_is_empty(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('photos.shared'))
            ->assertOk()
            ->assertDontSee('Jelajah galeri')
            ->assertDontSee('gallery-explorer.js');
    }

    private function sharedPhoto(User $owner, ?Climb $climb, string $takenOn, string $caption): Photo
    {
        return Photo::factory()->for($owner)->create([
            'climb_id' => $climb?->id,
            'taken_on' => $takenOn,
            'caption' => $caption,
            'shared_at' => now(),
            'approved_at' => now(),
        ]);
    }
}
