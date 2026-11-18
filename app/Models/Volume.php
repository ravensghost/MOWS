<?php
// === app/Models/Volume.php ===

namespace App\Models;

use App\Models\Traits\HasChronologicalTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Volume extends Model
{
    use HasChronologicalTime;

    protected $table = 'volumes';

    protected $guarded = [];

    protected $casts = [
        'is_confidential' => 'boolean',
    ];

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class, 'shelf_id');
    }

    public function folios(): HasMany
    {
        return $this->hasMany(Folio::class, 'volume_id');
    }

    public function publicationState(): BelongsTo
    {
        return $this->belongsTo(PublicationState::class, 'publication_state_id');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        if (auth()->check() && auth()->user()->can('manage_stories')) {
            return $query;
        }

        return $query->where('is_confidential', false)
            ->where(function (Builder $q) {
                $q->whereNull('shelf_id')
                    ->orWhereHas('shelf', fn (Builder $sq) => $sq->where('is_confidential', false));
            });
    }
}