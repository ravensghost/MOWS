<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Volume extends Model
{
    protected $table = 'volumes';
    protected $guarded = [];

    /**
     * Canonical relationship to the parent Shelf (formerly Bookcase).
     */
    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class, 'shelf_id');
    }

    /**
     * Legacy alias for shelf().
     */
    public function bookcase(): BelongsTo
    {
        return $this->shelf();
    }

    /**
     * Canonical relationship to child Folios (formerly Chapters).
     */
    public function folios(): HasMany
    {
        return $this->hasMany(Folio::class, 'volume_id');
    }

    /**
     * Legacy alias for folios().
     */
    public function chapters(): HasMany
    {
        return $this->folios();
    }

    /**
     * Canonical relationship to the PublicationState (formerly Status).
     */
    public function publicationState(): BelongsTo
    {
        return $this->belongsTo(PublicationState::class, 'publication_state_id');
    }

    /**
     * Legacy alias for publicationState().
     */
    public function status(): BelongsTo
    {
        return $this->publicationState();
    }

    /**
     * Retrieve users subscribed or participating in this volume.
     */
    public function getSubscribersAttribute()
    {
        $folioIds = $this->folios()->pluck('id');

        $userIds = Persona::whereHas('folios', function ($q) use ($folioIds) {
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
