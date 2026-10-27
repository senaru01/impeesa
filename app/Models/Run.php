<?php

namespace App\Models;

use Database\Factories\RunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['run_on', 'distance_km', 'duration_seconds', 'notes'])]
class Run extends Model
{
    /** @use HasFactory<RunFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'run_on' => 'date',
            'distance_km' => 'float',
            'duration_seconds' => 'integer',
        ];
    }

    /**
     * Detik yang dibutuhkan per kilometer.
     */
    public function paceSeconds(): int
    {
        return (int) round($this->duration_seconds / max($this->distance_km, 0.01));
    }

    /**
     * Pace dalam format "m:ss /km".
     */
    public function formattedPace(): string
    {
        return self::formatPace($this->paceSeconds());
    }

    /**
     * Durasi dalam format "j:mm:ss" atau "m:ss".
     */
    public function formattedDuration(): string
    {
        $hours = intdiv($this->duration_seconds, 3600);
        $minutes = intdiv($this->duration_seconds % 3600, 60);
        $seconds = $this->duration_seconds % 60;

        return $hours > 0
            ? sprintf('%d:%02d:%02d', $hours, $minutes, $seconds)
            : sprintf('%d:%02d', $minutes, $seconds);
    }

    public static function formatPace(int $seconds): string
    {
        return sprintf('%d:%02d /km', intdiv($seconds, 60), $seconds % 60);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
