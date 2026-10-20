<?php

namespace Tests\Feature;

use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberStoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_write_a_story(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('stories.store'), [
                'title' => 'Hujan es di Plawangan',
                'mountain_name' => 'Gunung Rinjani',
                'body' => str_repeat('Malam itu angin bertiup kencang di Plawangan Sembalun. ', 3),
                'cover' => UploadedFile::fake()->image('cover.jpg'),
                'is_public' => '0',
            ])
            ->assertRedirect(route('stories.index'));

        $story = $user->stories()->sole();
        $this->assertFalse($story->isFeatured());
        $this->assertFalse($story->is_public);
        Storage::disk('local')->assertExists($story->cover_path);
    }

    public function test_super_admin_can_feature_story_on_landing_page(): void
    {
        $story = Story::factory()->public()->create(['title' => 'Sunrise pertama di Rinjani']);

        $this->get(route('landing'))->assertDontSee('Sunrise pertama di Rinjani');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.stories.feature', $story))
            ->assertRedirect();

        $this->assertTrue($story->fresh()->isFeatured());

        auth()->logout();
        $this->get(route('landing'))->assertSee('Sunrise pertama di Rinjani');
        $this->get(route('stories.public', $story))->assertOk()->assertSee('Sunrise pertama di Rinjani');
    }

    public function test_super_admin_can_unfeature_story(): void
    {
        $story = Story::factory()->featured()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->delete(route('admin.stories.unfeature', $story))
            ->assertRedirect();

        $this->assertFalse($story->fresh()->isFeatured());
    }

    public function test_member_cannot_feature_stories(): void
    {
        $story = Story::factory()->public()->create();

        $this->actingAs($story->user)
            ->post(route('admin.stories.feature', $story))
            ->assertForbidden();
    }

    public function test_private_story_is_only_for_owner_and_super_admin(): void
    {
        $story = Story::factory()->create();

        $this->get(route('stories.public', $story))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('stories.public', $story))->assertNotFound();
        $this->actingAs($story->user)->get(route('stories.public', $story))->assertOk();
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('stories.public', $story))->assertOk();
    }

    public function test_public_story_is_readable_by_members_but_not_guests(): void
    {
        $story = Story::factory()->public()->create(['title' => 'Kabut di Segara Anak']);
        $private = Story::factory()->create(['title' => 'Catatan pribadi']);

        $this->actingAs(User::factory()->create())
            ->get(route('stories.shared'))
            ->assertOk()
            ->assertSee('Kabut di Segara Anak')
            ->assertDontSee('Catatan pribadi');
        $this->get(route('stories.public', $story))->assertOk();

        auth()->logout();
        $this->get(route('stories.public', $story))->assertNotFound();
        $this->get(route('stories.shared'))->assertRedirect(route('login'));
    }

    public function test_super_admin_cannot_feature_private_story(): void
    {
        $story = Story::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.stories.feature', $story))
            ->assertForbidden();

        $this->assertFalse($story->fresh()->isFeatured());
    }

    public function test_making_featured_story_private_removes_it_from_landing(): void
    {
        $story = Story::factory()->featured()->create(['title' => 'Sunrise Plawangan']);

        $this->actingAs($story->user)
            ->put(route('stories.update', $story), [
                'title' => $story->title,
                'body' => $story->body,
                'is_public' => '0',
            ])
            ->assertRedirect(route('stories.index'));

        $story->refresh();
        $this->assertFalse($story->is_public);
        $this->assertFalse($story->isFeatured());

        auth()->logout();
        $this->get(route('landing'))->assertDontSee('Sunrise Plawangan');
    }

    public function test_member_cannot_edit_someone_elses_story(): void
    {
        $story = Story::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('stories.edit', $story))
            ->assertForbidden();
    }
}
