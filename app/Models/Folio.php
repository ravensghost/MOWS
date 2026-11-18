<?php
// === app/Models/Folio.php ===

namespace App\Models;

use App\Models\Traits\HasChronologicalTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folio extends Model
{
    use HasChronologicalTime;

    protected $table = 'folios';

    protected $guarded = [];

    protected $casts = [
        'locked_at' => 'datetime',
    ];

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

    public function isAccessibleBy(?User $user): bool
    {
        if ($this->folio_status === 'published') {
            if ($this->volume && ($this->volume->is_confidential || $this->volume->shelf?->is_confidential)) {
                return $user !== null && $user->can('manage_stories');
            }
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->can('edit_chapter') || $user->can('manage_stories')) {
            return true;
        }

        if ((int) $this->user_id === (int) $user->id || (int) $this->turn_user_id === (int) $user->id) {
            return true;
        }

        $coAuthorUserIds = $this->personae()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        return in_array((int) $user->id, $coAuthorUserIds, true);
    }
}