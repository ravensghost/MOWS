<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folio extends Model
{
    protected $table = 'folios';
    protected $guarded = [];

    protected $casts = [
        'locked_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Canonical MOWS Relationships
    |--------------------------------------------------------------------------
    */

    public function volume(): BelongsTo
    {
        return $this->belongsTo(Volume::class, 'volume_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function activePersona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'active_persona_id');
    }

    public function turnUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'turn_user_id');
    }

    public function personae(): BelongsToMany
    {
        return $this->belongsToMany(Persona::class, 'folio_personae', 'folio_id', 'persona_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(FolioRevision::class, 'folio_id')->orderBy('created_at', 'desc');
    }

    public function authorLedgers(): HasMany
    {
        return $this->hasMany(AuthorLedger::class, 'folio_id');
    }

    public function contributingUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'author_ledgers', 'folio_id', 'user_id')
            ->withPivot('contribution_word_count');
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy & Blueprint Compatibility Aliases
    |--------------------------------------------------------------------------
    */

    public function book(): BelongsTo
    {
        return $this->volume();
    }

    public function authorUser(): BelongsTo
    {
        return $this->author();
    }

    public function activeCharacter(): BelongsTo
    {
        return $this->activePersona();
    }

    public function characters(): BelongsToMany
    {
        return $this->personae();
    }

    public function charactersPresent(): BelongsToMany
    {
        return $this->personae();
    }

    public function versions(): HasMany
    {
        return $this->revisions();
    }

    public function contributions(): HasMany
    {
        return $this->authorLedgers();
    }

    /*
    |--------------------------------------------------------------------------
    | Access Control Helpers
    |--------------------------------------------------------------------------
    */

    public function isAccessibleBy(?User $user): bool
    {
        if ($this->folio_status === 'published') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->hasRole('webmaster') ||$user->can('edit_chapter')) {
            return true;
        }

        if ((int) $this->user_id === (int)$user->id) {
            return true;
        }

        $coAuthorUserIds =$this->personae()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn ($id) => (int)$id)
            ->toArray();

        return in_array((int) $user->id,$coAuthorUserIds, true);
    }
}