<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidatePetition extends Model
{
    protected $table = 'candidate_petitions';

    protected $fillable = [
        'proposed_persona_name',
        'user_id',
        'persona_id',
        'petition_status',
        'lodged_at',
    ];

    protected $casts = [
        'lodged_at' => 'datetime',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(PetitionEntry::class, 'candidate_petition_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PetitionEntry::class, 'candidate_petition_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(CouncilEvaluation::class, 'candidate_petition_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(CouncilEvaluation::class, 'candidate_petition_id');
    }
}
