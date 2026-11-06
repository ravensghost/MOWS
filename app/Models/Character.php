<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Character extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function rank(): BelongsTo
    {
        return $this->belongsTo(Ranks::class, 'rank_id');
    }

    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class);
    }

    public function folios(): HasMany
    {
        return $this->hasMany(Folio::class, 'active_persona_id');
    }

    public function featuredFolios(): BelongsToMany
    {
        return $this->belongsToMany(Folio::class, 'folio_personae', 'persona_id', 'folio_id');
    }

    public function chapters(): HasMany
    {
        return $this->folios();
    }

    public function featuredChapters(): BelongsToMany
    {
        return $this->featuredFolios();
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(CharacterAttribute::class);
    }

    public function application(): HasOne
    {
        return $this->hasOne(Application::class, 'character_id');
    }
}
