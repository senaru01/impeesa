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
     * Titik gunung pendakian ini; null bila nama gunungnya tidak dikenali.
     * "key" adalah nama gunung yang dinormalkan, dipakai untuk mengelompokkan pendakian.
     *
     * @return array{key: string, lat: float, lng: float}|null
     */
    public function mountainLocation(): ?array
    {
        return self::locateMountain($this->mountain_name);
    }

    /**
     * Titik gunung untuk nama bebas ("Gn. Rinjani via Sembalun"); null bila tidak dikenali.
     *
     * @return array{key: string, lat: float, lng: float}|null
     */
    public static function locateMountain(?string $name): ?array
    {
        $locations = self::mountainLocations();
        $key = self::resolveMountain((string) $name, $locations);

        return $key === null ? null : ['key' => $key, ...$locations[$key]];
    }

    /**
     * Saran nama gunung beserta ketinggiannya untuk isian formulir.
     *
     * @return array<string, int>
     */
    public static function mountainOptions(): array
    {
        return collect(config('mountains.destinations'))
            ->concat(config('mountains.others'))
            ->sortBy('name')
            ->mapWithKeys(fn (array $mountain) => [$mountain['name'] => (int) preg_replace('/\D/', '', $mountain['elevation'])])
            ->all();
    }

    /**
     * Indeks koordinat gunung dari destinasi (titik puncak) lalu bank data jalur.
     *
     * @return array<string, array{lat: float, lng: float}>
     */
    private static function mountainLocations(): array
    {
        return once(function (): array {
            $locations = [];

            foreach ([...config('mountains.destinations'), ...config('mountains.others')] as $mountain) {
                [$lat, $lng] = $mountain['coordinates'];

                foreach (array_filter([$mountain['name'], $mountain['slug'] ?? null]) as $alias) {
                    $locations[self::normalizeMountain($alias)] ??= ['lat' => $lat, 'lng' => $lng];
                }
            }

            foreach (config('trails.trails') as $trail) {
                $locations[self::normalizeMountain($trail['name'])] ??= ['lat' => $trail['lat'], 'lng' => $trail['lng']];
            }

            return $locations;
        });
    }

    /**
     * Cocokkan nama bebas ("Gn. Rinjani via Sembalun") ke kunci indeks; pilih kecocokan terpanjang.
     *
     * @param  array<string, array{lat: float, lng: float}>  $locations
     */
    private static function resolveMountain(string $name, array $locations): ?string
    {
        $needle = self::normalizeMountain($name);

        if ($needle === '' || isset($locations[$needle])) {
            return $needle === '' ? null : $needle;
        }

        return collect(array_keys($locations))
            ->filter(fn (string $key) => strlen($key) >= 4 && (str_contains($needle, $key) || (strlen($needle) >= 4 && str_contains($key, $needle))))
            ->sortByDesc(fn (string $key) => strlen($key))
            ->first();
    }

    private static function normalizeMountain(string $name): string
    {
        $name = preg_replace('/\b(gunung|gn|kawah|puncak|pegunungan|peg|mount|mt)\b\.?/', '', strtolower($name));

        return preg_replace('/[^a-z0-9]/', '', $name);
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
