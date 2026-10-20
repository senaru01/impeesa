<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SharedGalleryApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_member_upload_waits_for_approval_and_is_hidden_from_others(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)
            ->post(route('photos.shared.store'), ['photos' => [UploadedFile::fake()->image('a.jpg')]])
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'menunggu persetujuan'));

        $photo = Photo::shared()->sole();
        $this->assertTrue($photo->isPending());

        $this->actingAs($member)->get(route('photos.show', $photo))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('photos.show', $photo))->assertForbidden();
        $this->actingAs(User::factory()->create())
            ->get(route('photos.shared'))
            ->assertDontSee(route('photos.show', $photo));
    }

    public function test_super_admin_upload_is_published_immediately(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('photos.shared.store'), ['photos' => [UploadedFile::fake()->image('a.jpg')]]);

        $this->assertFalse(Photo::shared()->sole()->isPending());
    }

    public function test_super_admin_approves_pending_photo(): void
    {
        $photo = $this->pendingPhoto();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('photos.shared'))
            ->assertSee('Menunggu persetujuan')
            ->assertSee(route('admin.shared-photos.approve', $photo));

        $this->post(route('admin.shared-photos.approve', $photo))->assertRedirect();

        $this->assertFalse($photo->fresh()->isPending());
        $this->actingAs(User::factory()->create())->get(route('photos.show', $photo))->assertOk();
    }

    public function test_super_admin_rejects_pending_photo(): void
    {
        $photo = $this->pendingPhoto();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->delete(route('admin.shared-photos.reject', $photo))
            ->assertRedirect();

        $this->assertModelMissing($photo);
        Storage::disk('local')->assertMissing($photo->path);
    }

    public function test_member_cannot_approve_photos(): void
    {
        $photo = $this->pendingPhoto();

        $this->actingAs($photo->uploader)
            ->post(route('admin.shared-photos.approve', $photo))
            ->assertForbidden();

        $this->assertTrue($photo->fresh()->isPending());
    }

    public function test_member_can_cancel_own_pending_photo(): void
    {
        $photo = $this->pendingPhoto();

        $this->actingAs($photo->uploader)->delete(route('photos.destroy', $photo))->assertRedirect();

        $this->assertModelMissing($photo);
    }

    public function test_member_shares_private_photo_and_it_waits_for_approval(): void
    {
        $photo = $this->privatePhoto();
        $owner = $photo->user;

        $this->actingAs($owner)->post(route('photos.share', $photo))->assertRedirect();

        $photo->refresh();
        $this->assertTrue($photo->isPending());
        $this->actingAs(User::factory()->create())->get(route('photos.show', $photo))->assertForbidden();
        $this->actingAs($owner)->get(route('photos.index'))->assertSee('Menunggu persetujuan');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.shared-photos.approve', $photo))
            ->assertRedirect();

        $other = User::factory()->create();
        $this->actingAs($other)->get(route('photos.show', $photo))->assertOk();
        $this->actingAs($other)->get(route('photos.shared'))->assertSee(route('photos.show', $photo));
        $this->actingAs($owner)->get(route('photos.index'))->assertSee(route('photos.show', $photo));
    }

    public function test_rejecting_shared_private_photo_keeps_it_in_owner_gallery(): void
    {
        $photo = $this->privatePhoto();
        $this->actingAs($photo->user)->post(route('photos.share', $photo));

        $this->actingAs(User::factory()->superAdmin()->create())
            ->delete(route('admin.shared-photos.reject', $photo))
            ->assertRedirect();

        $photo->refresh();
        $this->assertFalse($photo->isShared());
        Storage::disk('local')->assertExists($photo->path);
    }

    public function test_owner_can_make_shared_photo_private_again(): void
    {
        $photo = $this->privatePhoto();
        $photo->forceFill(['shared_at' => now(), 'approved_at' => now()])->save();

        $this->actingAs($photo->user)->delete(route('photos.unshare', $photo))->assertRedirect();

        $this->assertFalse($photo->fresh()->isShared());
        $this->actingAs(User::factory()->create())->get(route('photos.show', $photo))->assertForbidden();
    }

    public function test_super_admin_shares_own_photo_without_approval(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $photo = $this->privatePhoto($admin);

        $this->actingAs($admin)->post(route('photos.share', $photo));

        $this->assertFalse($photo->fresh()->isPending());
    }

    public function test_member_cannot_share_someone_elses_photo(): void
    {
        $photo = $this->privatePhoto();

        $this->actingAs(User::factory()->create())->post(route('photos.share', $photo))->assertForbidden();

        $this->assertFalse($photo->fresh()->isShared());
    }

    private function privatePhoto(?User $owner = null): Photo
    {
        $owner ??= User::factory()->create();

        return Photo::factory()->for($owner)->create([
            'uploaded_by' => $owner->id,
            'path' => UploadedFile::fake()->image('p.jpg')->store("photos/{$owner->id}", 'local'),
        ]);
    }

    private function pendingPhoto(): Photo
    {
        return Photo::factory()->create([
            'user_id' => null,
            'uploaded_by' => User::factory(),
            'shared_at' => now(),
            'path' => UploadedFile::fake()->image('a.jpg')->store('photos/shared', 'local'),
        ]);
    }
}
