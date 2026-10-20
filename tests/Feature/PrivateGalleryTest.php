<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_member_uploads_photos_to_private_disk(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('photos.store'), [
                'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
                'caption' => 'Sunrise Plawangan',
            ])
            ->assertRedirect();

        $photos = $user->photos()->get();
        $this->assertCount(2, $photos);
        $photos->each(fn (Photo $photo) => Storage::disk('local')->assertExists($photo->path));
    }

    public function test_owner_and_super_admin_can_view_photo(): void
    {
        $photo = $this->storedPhoto();

        $this->actingAs($photo->user)->get(route('photos.show', $photo))->assertOk();
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('photos.show', $photo))->assertOk();
    }

    public function test_other_member_and_guest_cannot_view_photo(): void
    {
        $photo = $this->storedPhoto();

        $this->actingAs(User::factory()->create())->get(route('photos.show', $photo))->assertForbidden();

        auth()->logout();
        $this->get(route('photos.show', $photo))->assertRedirect(route('login'));
    }

    public function test_gallery_page_lists_only_own_photos(): void
    {
        $own = $this->storedPhoto();
        $other = $this->storedPhoto();

        $this->actingAs($own->user)
            ->get(route('photos.index'))
            ->assertOk()
            ->assertSee(route('photos.show', $own))
            ->assertDontSee(route('photos.show', $other));
    }

    public function test_other_member_cannot_delete_photo(): void
    {
        $photo = $this->storedPhoto();

        $this->actingAs(User::factory()->create())->delete(route('photos.destroy', $photo))->assertForbidden();

        $this->assertModelExists($photo);
    }

    public function test_super_admin_can_send_photos_to_member_private_gallery(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $member = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.members.photos.store', $member), [
                'photos' => [UploadedFile::fake()->image('tim.jpg')],
                'caption' => 'Foto dari admin',
            ])
            ->assertRedirect();

        $photo = $member->photos()->sole();
        $this->assertSame($admin->id, $photo->uploaded_by);
        $this->assertTrue($photo->wasUploadedByOther());

        $this->actingAs($member)->get(route('photos.show', $photo))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('photos.show', $photo))->assertForbidden();
    }

    public function test_member_cannot_send_photos_to_another_member(): void
    {
        $member = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.members.photos.store', $member), [
                'photos' => [UploadedFile::fake()->image('a.jpg')],
            ])
            ->assertForbidden();

        $this->assertSame(0, Photo::count());
    }

    public function test_super_admin_adds_photos_to_shared_gallery_visible_to_all_members(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('photos.shared.store'), [
                'photos' => [UploadedFile::fake()->image('kegiatan.jpg')],
            ])
            ->assertRedirect();

        $photo = Photo::shared()->sole();
        $member = User::factory()->create();

        $this->actingAs($member)
            ->get(route('photos.shared'))
            ->assertOk()
            ->assertSee(route('photos.show', $photo));
        $this->actingAs($member)->get(route('photos.show', $photo))->assertOk();
        $this->actingAs($member)->get(route('photos.index'))->assertDontSee(route('photos.show', $photo));
    }

    public function test_member_cannot_delete_someone_elses_shared_photo(): void
    {
        $shared = Photo::factory()->create(['user_id' => null, 'uploaded_by' => User::factory()->superAdmin(), 'shared_at' => now(), 'approved_at' => now()]);

        $this->actingAs(User::factory()->create())->delete(route('photos.destroy', $shared))->assertForbidden();
        $this->assertModelExists($shared);
    }

    public function test_guest_cannot_open_shared_gallery(): void
    {
        $this->get(route('photos.shared'))->assertRedirect(route('login'));
    }

    private function storedPhoto(): Photo
    {
        $user = User::factory()->create();

        return Photo::factory()->for($user)->create([
            'path' => UploadedFile::fake()->image('foto.jpg')->store("photos/{$user->id}", 'local'),
        ]);
    }
}
