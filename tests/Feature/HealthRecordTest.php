<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Run;
use App\Models\User;
use App\Models\WeightLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_records_weight(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('weights.store'), [
                'recorded_on' => now()->toDateString(),
                'weight_kg' => '68.4',
                'height_cm' => '170',
                'notes' => 'pagi',
            ])
            ->assertRedirect(route('health.index'))
            ->assertSessionHas('status', 'Berat badan berhasil dicatat.');

        $log = $user->weightLogs()->sole();
        $this->assertSame(68.4, $log->weight_kg);
        $this->assertSame(23.7, $log->bmi());
    }

    public function test_member_records_run_and_duration_is_converted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('runs.store'), [
                'run_on' => now()->toDateString(),
                'distance_km' => '10',
                'duration' => '1:02:30',
            ])
            ->assertRedirect(route('health.index'))
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'pace 6:15 /km'));

        $run = $user->runs()->sole();
        $this->assertSame(3750, $run->duration_seconds);
        $this->assertSame('1:02:30', $run->formattedDuration());
    }

    public function test_invalid_run_errors_go_to_the_run_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('health.index'))
            ->post(route('runs.store'), [
                'run_on' => now()->addDay()->toDateString(),
                'distance_km' => '5',
                'duration' => '30 menit',
            ])
            ->assertRedirect(route('health.index'))
            ->assertSessionHasErrors(['run_on', 'duration'], errorBag: 'run');

        $this->assertDatabaseCount('runs', 0);
    }

    public function test_health_page_shows_progress_summary(): void
    {
        $user = User::factory()->create();
        WeightLog::factory()->for($user)->create(['recorded_on' => now()->subWeeks(2), 'weight_kg' => 72.0, 'height_cm' => 170]);
        WeightLog::factory()->for($user)->create(['recorded_on' => now(), 'weight_kg' => 70.5, 'height_cm' => null]);
        Run::factory()->for($user)->create(['run_on' => now(), 'distance_km' => 5, 'duration_seconds' => 1500]);

        $this->actingAs($user)
            ->get(route('health.index'))
            ->assertOk()
            ->assertSee('70,5')
            ->assertSee('-1,5 kg')
            ->assertSee('24,4')
            ->assertSee('Normal')
            ->assertSee('5:00 /km')
            ->assertSee('<svg viewBox', false);
    }

    public function test_member_cannot_delete_another_members_records(): void
    {
        $owner = User::factory()->create();
        $weight = WeightLog::factory()->for($owner)->create();
        $run = Run::factory()->for($owner)->create();
        $other = User::factory()->create();

        $this->actingAs($other)->delete(route('weights.destroy', $weight))->assertForbidden();
        $this->actingAs($other)->delete(route('runs.destroy', $run))->assertForbidden();

        $this->actingAs($owner)->delete(route('weights.destroy', $weight))->assertRedirect(route('health.index'));
        $this->actingAs($owner)->delete(route('runs.destroy', $run))->assertRedirect(route('health.index'));
        $this->assertDatabaseEmpty('weight_logs');
        $this->assertDatabaseEmpty('runs');
    }

    public function test_admin_sees_member_health_summary(): void
    {
        $admin = User::factory()->create(['role' => Role::SuperAdmin]);
        $member = User::factory()->create();
        WeightLog::factory()->for($member)->create(['weight_kg' => 64.2, 'height_cm' => 165]);
        Run::factory()->for($member)->create(['distance_km' => 8.5, 'duration_seconds' => 3060]);

        $this->actingAs($admin)
            ->get(route('admin.members.show', $member))
            ->assertOk()
            ->assertSee('64,2 kg')
            ->assertSee('8,50 km')
            ->assertSee('6:00 /km');
    }
}
