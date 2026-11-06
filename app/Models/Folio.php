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
        return $this->belongsTo(Character::class, 'active_persona_id');
    }

    public function turnUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'turn_user_id');
    }

    public function personae(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'folio_personae', 'folio_id', 'persona_id');
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

    public function isAccessibleBy(?User $user): bool
    {
        if ($this->folio_status === 'published') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->hasRole('webmaster') || $user->can('edit_chapter')) {
            return true;
        }

        if ($this->user_id ===$user->id) {
            return true;
        }

        $coAuthorUserIds =$this->personae()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->toArray();

        return in_array($user->id,$coAuthorUserIds);
    }
}