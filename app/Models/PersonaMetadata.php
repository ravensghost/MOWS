<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonaMetadata extends Model
{
    protected $table = 'persona_metadata';

    protected $fillable = [
        'persona_id',
        'attribute_key',
        'attribute_value',
    ];

    /**
     * The persona instance that owns this metadata attribute.
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    /**
     * Required relationship alias for persona.
     */
    public function character(): BelongsTo
    {
        return $this->persona();
    }
}
