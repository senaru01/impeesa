<?php

namespace App\Models;

use App\Enums\ClimbStatus;
use App\Enums\ParticipantStatus;
use Database\Factories\ClimbFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

#[Fillable(['mountain_name', 'route', 'elevation', 'started_on', 'finished_on', 'status', 'notes'])]
class Climb extends Model
{
    /** @use HasFactory<ClimbFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'finished_on' => 'date',
            'elevation' => 'integer',
            'status' => ClimbStatus::class,
        ];
    }

    /**
     * Foto utama: yang dipilih pemilik, atau foto pertama pendakian.
     */
    public function mainPhoto(): ?Photo
    {
        return $this->coverPhoto ?? $this->photos->sortBy('id')->first();
    }

    /**
     * Salinan pendakian yang didapat karena menyetujui ajakan anggota lain.
     */
    public function isJoined(): bool
    {
        return $this->joinedFrom !== null;
    }

    /**
     * Anggota lain yang ikut dalam rombongan yang sama (sudah setuju).
     * Butuh relasi participants.user dan joinedFrom.climb.user/participants.user dimuat.
     *
     * @return Collection<int, User>
     */
    public function companions(): Collection
    {
        if ($this->joinedFrom === null) {
            return $this->participants
                ->where('status', ParticipantStatus::Accepted)
                ->map(fn (ClimbParticipant $participant) => $participant->user)
                ->values();
        }

        $original = $this->joinedFrom->climb;

        return collect([$original->user])
            ->concat($original->participants
                ->where('status', ParticipantStatus::Accepted)
                ->where('user_id', '!=', $this->user_id)
                ->map(fn (ClimbParticipant $participant) => $participant->user))
            ->values();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Foto pendakian; tersimpan di galeri pribadi pemilik.
     *
     * @return HasMany<Photo, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    /**
     * @return BelongsTo<Photo, $this>
     */
    public function coverPhoto(): BelongsTo
    {
        return $this->belongsTo(Photo::class, 'cover_photo_id');
    }

    /**
     * Anggota yang diajak pemilik pendakian ini.
     *
     * @return HasMany<ClimbParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ClimbParticipant::class);
    }

    /**
     * Ajakan asal bila pendakian ini adalah salinan milik peserta.
     *
     * @return HasOne<ClimbParticipant, $this>
     */
    public function joinedFrom(): HasOne
    {
        return $this->hasOne(ClimbParticipant::class, 'joined_climb_id');
    }
}
