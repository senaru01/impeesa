<?php

namespace App\Models;

use Database\Factories\PhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;

/**
 * Foto dengan pemilik (user_id) ada di galeri pribadi pemiliknya. Foto yang dibagikan
 * (shared_at terisi) juga tampil di Galeri Bersama setelah disetujui (approved_at).
 * Foto yang diunggah langsung ke Galeri Bersama tidak punya pemilik.
 */
#[Fillable(['path', 'caption', 'taken_on', 'mountain_name'])]
class Photo extends Model
{
    /** @use HasFactory<PhotoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'taken_on' => 'date',
            'shared_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Simpan berkas unggahan di disk privat dan catat fotonya.
     *
     * @param  User|null  $owner  Pemilik galeri pribadi; null berarti langsung ke Galeri Bersama.
     * @param  array<string, mixed>  $attributes
     */
    public static function storeUpload(UploadedFile $file, User $uploader, ?User $owner = null, ?Climb $climb = null, array $attributes = []): self
    {
        $photo = new self([
            ...$attributes,
            'path' => $file->store($owner ? "photos/{$owner->id}" : 'photos/shared', 'local'),
        ]);
        $photo->user()->associate($owner);
        $photo->uploader()->associate($uploader);
        $photo->climb()->associate($climb);

        if ($owner === null) {
            $photo->markShared($uploader);
        }

        $photo->save();

        return $photo;
    }

    /**
     * Ajukan foto ke Galeri Bersama; kiriman super admin langsung disetujui.
     */
    public function markShared(User $by): void
    {
        $this->shared_at = now();
        $this->approved_at = $by->isSuperAdmin() ? now() : null;
    }

    public function isShared(): bool
    {
        return $this->shared_at !== null;
    }

    /**
     * Foto Galeri Bersama yang belum disetujui super admin.
     */
    public function isPending(): bool
    {
        return $this->isShared() && $this->approved_at === null;
    }

    /**
     * Foto yang hanya ada di Galeri Bersama, tanpa galeri pribadi pemilik.
     */
    public function isSharedOnly(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Foto pribadi yang diunggahkan orang lain (super admin) ke galeri pemiliknya.
     */
    public function wasUploadedByOther(): bool
    {
        return $this->user_id !== null && $this->uploaded_by !== null && $this->uploaded_by !== $this->user_id;
    }

    /**
     * Nama lokasi foto: gunung pendakiannya, atau lokasi yang diisi manual bila tanpa pendakian.
     * Butuh relasi climb dimuat agar tidak memicu query per foto.
     */
    public function locationName(): ?string
    {
        return $this->climb?->mountain_name ?? $this->mountain_name;
    }

    /**
     * Titik peta foto; null bila lokasinya kosong atau nama gunungnya tidak dikenali.
     *
     * @return array{key: string, lat: float, lng: float}|null
     */
    public function mountainLocation(): ?array
    {
        return $this->climb !== null
            ? $this->climb->mountainLocation()
            : Climb::locateMountain($this->mountain_name);
    }

    /**
     * @param  Builder<Photo>  $query
     */
    #[Scope]
    protected function shared(Builder $query): void
    {
        $query->whereNotNull('shared_at');
    }

    /**
     * @param  Builder<Photo>  $query
     */
    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->whereNotNull('approved_at');
    }

    /**
     * @param  Builder<Photo>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('approved_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return BelongsTo<Climb, $this>
     */
    public function climb(): BelongsTo
    {
        return $this->belongsTo(Climb::class);
    }
}
