<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Persona extends Model
{
    protected $table = 'personae';
    protected $guarded = [];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    /**
     * The user account that owns this persona.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The duty assignment/position held by this persona.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    /**
     * The rank insignia assigned to this persona.
     */
    public function rank(): BelongsTo
    {
        return $this->belongsTo(Insignia::class, 'insignia_id');
    }

    /**
     * The chronological service register entries for this persona.
     */
    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ChronologicalRegister::class, 'persona_id');
    }

    /**
     * The folios authored/posted by this persona as active character.
     */
    public function folios(): HasMany
    {
        return $this->hasMany(Folio::class, 'active_persona_id');
    }

    /**
     * The folios featuring this persona in the scene via pivot table.
     */
    public function featuredFolios(): BelongsToMany
    {
        return $this->belongsToMany(Folio::class, 'folio_personae', 'persona_id', 'folio_id');
    }

    /**
     * Legacy alias for folios.
     */
    public function chapters(): HasMany
    {
        return $this->folios();
    }

    /**
     * Legacy alias for featuredFolios.
     */
    public function featuredChapters(): BelongsToMany
    {
        return $this->featuredFolios();
    }

    /**
     * The metadata attributes associated with this persona.
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(PersonaMetadata::class, 'persona_id');
    }

    /**
     * The candidate petition submitted to create this persona.
     */
    public function application(): HasOne
    {
        return $this->hasOne(CandidatePetition::class, 'persona_id');
    }
}
