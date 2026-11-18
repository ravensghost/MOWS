<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Persona extends Model
{
    protected $table = 'personae';

    protected $guarded = [];

    protected $casts = [];

    /**
     * Bridge primary_portrait_uri <-> primary_portrait_url seamlessly.
     */
    protected function primaryPortraitUri(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => $value ?? ($attributes['primary_portrait_url'] ?? null),
            set: fn ($value) => ['primary_portrait_url' => $value]
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function insignia(): BelongsTo
    {
        return $this->belongsTo(Insignia::class, 'insignia_id');
    }

    public function lineageCatalogue(): BelongsTo
    {
        return $this->belongsTo(LineageCatalogue::class, 'lineage_catalogue_id');
    }

    public function chronologicalRegisters(): HasMany
    {
        return $this->hasMany(ChronologicalRegister::class, 'persona_id');
    }

    public function serviceRecords(): HasMany
    {
        return $this->chronologicalRegisters();
    }

    public function folios(): HasMany
    {
        return $this->hasMany(Folio::class, 'active_persona_id');
    }

    public function featuredFolios(): BelongsToMany
    {
        return $this->belongsToMany(Folio::class, 'folio_personae', 'persona_id', 'folio_id');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(PersonaMetadata::class, 'persona_id');
    }

    public function candidatePetition(): HasOne
    {
        return $this->hasOne(CandidatePetition::class, 'persona_id');
    }

    public function application(): HasOne
    {
        return $this->candidatePetition();
    }
}