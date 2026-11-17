<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_worker_is_served_with_the_store_scope(): void
    {
        $response = $this->get(route('admin.store.service-worker'));

        $response->assertOk()
            ->assertHeader('Service-Worker-Allowed', '/admin/toko');
        $this->assertStringContainsString('application/javascript', $response->headers->get('Content-Type'));
    }

    public function test_only_store_pages_link_the_cashier_app_manifest(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($admin)
            ->get(route('admin.store.pos'))
            ->assertOk()
            ->assertSee('toko.webmanifest')
            ->assertSee('data-pwa-install', false);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('toko.webmanifest');
    }

    public function test_manifest_is_scoped_to_the_store_menu(): void
    {
        $manifest = json_decode(file_get_contents(public_path('toko.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('/admin/toko', $manifest['scope']);
        $this->assertSame('/admin/toko', $manifest['start_url']);
        $this->assertSame(url($manifest['start_url']), route('admin.store.pos'));
    }
}
