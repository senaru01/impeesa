<?php

namespace App\Models;

use App\Enums\ParticipantStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ajakan seorang anggota ke pendakian anggota lain.
 */
class ClimbParticipant extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ParticipantStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    /**
     * Pendakian milik pengajak.
     *
     * @return BelongsTo<Climb, $this>
     */
    public function climb(): BelongsTo
    {
        return $this->belongsTo(Climb::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Salinan pendakian di catatan peserta setelah ajakan disetujui.
     *
     * @return BelongsTo<Climb, $this>
     */
    public function joinedClimb(): BelongsTo
    {
        return $this->belongsTo(Climb::class, 'joined_climb_id');
    }
}
