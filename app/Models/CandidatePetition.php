<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function answers()
    {
        return $this->hasMany(PetitionEntry::class, 'candidate_petition_id');
    }

    public function votes()
    {
        return $this->hasMany(CouncilEvaluation::class, 'candidate_petition_id');
    }
}