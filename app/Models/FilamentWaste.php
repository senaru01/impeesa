<?php

namespace App\Models;

use App\Enums\FilamentWasteReason;
use Database\Factories\FilamentWasteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Filament yang dibuang (sisa rusak) atau habis untuk cetakan gagal (NG). Sisa roll
 * berkurang tanpa menghasilkan stok; uangnya sudah keluar saat roll dibeli, jadi ini
 * dicatat sebagai kerugian, bukan uang keluar baru.
 */
#[Fillable(['filament_lot_id', 'user_id', 'reason', 'grams', 'value', 'notes'])]
class FilamentWaste extends Model
{
    /** @use HasFactory<FilamentWasteFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => FilamentWasteReason::class,
            'grams' => 'float',
            'value' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<FilamentLot, $this>
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(FilamentLot::class, 'filament_lot_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
