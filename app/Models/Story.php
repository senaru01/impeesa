<?php

namespace App\Models;

use Database\Factories\StoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'mountain_name', 'climbed_on', 'body', 'cover_path', 'is_public'])]
class Story extends Model
{
    /** @use HasFactory<StoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'climbed_on' => 'date',
            'featured_at' => 'datetime',
            'is_public' => 'boolean',
        ];
    }

    /**
     * Cerita publik bisa dibaca semua member di halaman Cerita Bersama.
     *
     * @param  Builder<Story>  $query
     */
    #[Scope]
    protected function public(Builder $query): void
    {
        $query->where('is_public', true);
    }

    public function isFeatured(): bool
    {
        return $this->featured_at !== null;
    }

    /**
     * Cerita yang dipilih super admin untuk tampil di halaman utama.
     *
     * @param  Builder<Story>  $query
     */
    #[Scope]
    protected function featured(Builder $query): void
    {
        $query->whereNotNull('featured_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
