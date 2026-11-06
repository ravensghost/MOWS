<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Volume extends Model
{
    protected $table = 'volumes';
    protected $guarded = [];

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

    /**
     * Retrieve users subscribed/participating in this volume.
     */
    public function getSubscribersAttribute()
    {
        $folioIds = $this->folios()->pluck('id');

        $userIds = Character::whereHas('featuredFolios', function ($q) use ($folioIds) {
            $q->whereIn('folios.id', $folioIds);
        })->whereNotNull('user_id')->pluck('user_id')
            ->merge($this->folios()->whereNotNull('user_id')->pluck('user_id'))
            ->unique()
            ->filter();

        if ($userIds->isEmpty()) {
            return User::where('id', '!=', auth()->id())->get();
        }

        return User::whereIn('id', $userIds)->where('id', '!=', auth()->id())->get();
    }
}
