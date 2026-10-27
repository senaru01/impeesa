<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'nickname', 'email', 'password', 'role', 'is_active', 'member_number', 'position', 'joined_on',
    'phone', 'gender', 'birth_place', 'birth_date', 'blood_type', 'city', 'address', 'emergency_contact_name',
    'emergency_contact_phone', 'medical_notes', 'bio', 'avatar_path', 'nik', 'religion', 'marital_status',
    'occupation', 'nationality', 'id_card_address', 'emergency_contact_relation', 'allergies', 'medications',
    'signature_place',
])]
#[Hidden(['password', 'remember_token', 'nik', 'emergency_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'joined_on' => 'date',
            'birth_date' => 'date',
            'nik' => 'encrypted',
            'profile_featured_at' => 'datetime',
        ];
    }

    /**
     * Token acak di URL halaman darurat. Dibuat saat pertama dibutuhkan.
     */
    public function emergencyToken(): string
    {
        if ($this->emergency_token === null) {
            $this->regenerateEmergencyToken();
        }

        return $this->emergency_token;
    }

    /**
     * Ganti token supaya QR lama tidak bisa membuka halaman detail lagi.
     */
    public function regenerateEmergencyToken(): void
    {
        $this->forceFill(['emergency_token' => Str::random(40)])->save();
    }

    /**
     * Isi teks QR: data penting yang tetap terbaca tanpa sinyal, ditambah tautan detail.
     */
    public function emergencyQrText(): string
    {
        $contact = collect([
            $this->emergency_contact_name,
            $this->emergency_contact_relation ? "({$this->emergency_contact_relation})" : null,
            $this->emergency_contact_phone,
        ])->filter()->implode(' ');

        $birth = collect([$this->birth_place, $this->birth_date?->format('d-m-Y')])->filter()->implode(', ');

        return collect([
            'DARURAT - IMPEESA X-TREAM ADVENTURE TEAM',
            'Nama: '.$this->name,
            $this->member_number ? 'NIA: '.$this->member_number : null,
            'Gol. darah: '.($this->blood_type ?: '-'),
            $birth || $this->gender ? 'Lahir: '.trim($birth.($this->gender ? " ({$this->gender})" : '')) : null,
            $this->phone ? 'HP: '.$this->phone : null,
            'Alergi: '.($this->allergies ?: '-'),
            'Kondisi medis: '.($this->medical_notes ?: '-'),
            'Obat rutin: '.($this->medications ?: '-'),
            'Kontak darurat: '.($contact ?: '-'),
            'Detail: '.route('emergency.show', $this->emergencyToken()),
        ])->filter()->implode("\n");
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }

    public function isProfileFeatured(): bool
    {
        return $this->profile_featured_at !== null;
    }

    /**
     * Anggota aktif yang profilnya dipilih super admin untuk tampil di web utama.
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function featuredProfile(Builder $query): void
    {
        $query->where('is_active', true)->whereNotNull('profile_featured_at');
    }

    /**
     * Nama sapaan: nama panggilan, atau kata pertama dari nama lengkap.
     *
     * @return Attribute<string, never>
     */
    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => $this->nickname ?: Str::before($this->name, ' '));
    }

    /**
     * @return Attribute<string, never>
     */
    protected function initials(): Attribute
    {
        return Attribute::get(fn (): string => Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $word) => Str::upper(Str::substr($word, 0, 1)))
            ->implode(''));
    }

    /**
     * Simpan foto profil baru di disk privat dan hapus yang lama.
     */
    public function replaceAvatar(UploadedFile $file): void
    {
        $previous = $this->avatar_path;

        $this->update(['avatar_path' => $file->store("avatars/{$this->id}", 'local')]);

        if ($previous) {
            Storage::disk('local')->delete($previous);
        }
    }

    /**
     * @return HasMany<Climb, $this>
     */
    public function climbs(): HasMany
    {
        return $this->hasMany(Climb::class);
    }

    /**
     * @return HasMany<Photo, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    /**
     * @return HasMany<Story, $this>
     */
    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    /**
     * @return HasMany<WeightLog, $this>
     */
    public function weightLogs(): HasMany
    {
        return $this->hasMany(WeightLog::class);
    }

    /**
     * @return HasMany<Run, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(Run::class);
    }

    /**
     * Ringkasan berat badan dan lari untuk halaman kesehatan dan detail anggota.
     * IMT memakai tinggi badan terakhir yang pernah dicatat.
     *
     * @return array{latestWeight: ?WeightLog, weightChange: ?float, totalChange: ?float, heightCm: ?int, bmi: ?float, bmiCategory: ?string, monthRuns: int, monthDistance: float, totalDistance: float, longestRun: ?Run, bestPace: ?string}
     */
    public function healthSummary(): array
    {
        $weights = $this->weightLogs()->orderBy('recorded_on')->orderBy('id')->get();
        $runs = $this->runs()->get();
        $monthRuns = $runs->filter(fn (Run $run) => $run->run_on->isCurrentMonth());

        $latest = $weights->last();
        $previous = $weights->count() > 1 ? $weights->get($weights->count() - 2) : null;
        $heightCm = $weights->whereNotNull('height_cm')->last()?->height_cm;
        $bmi = $latest?->bmi($heightCm);
        $fastest = $runs->filter(fn (Run $run) => $run->distance_km >= 1)->sortBy(fn (Run $run) => $run->paceSeconds())->first();

        return [
            'latestWeight' => $latest,
            'weightChange' => $previous ? round($latest->weight_kg - $previous->weight_kg, 1) : null,
            'totalChange' => $previous ? round($latest->weight_kg - $weights->first()->weight_kg, 1) : null,
            'heightCm' => $heightCm,
            'bmi' => $bmi,
            'bmiCategory' => $bmi ? WeightLog::bmiCategory($bmi) : null,
            'monthRuns' => $monthRuns->count(),
            'monthDistance' => round($monthRuns->sum('distance_km'), 2),
            'totalDistance' => round($runs->sum('distance_km'), 2),
            'longestRun' => $runs->sortByDesc('distance_km')->first(),
            'bestPace' => $fastest?->formattedPace(),
        ];
    }
}
