<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetitionEntry extends Model
{
    protected $table = 'petition_entries';

    protected $fillable = [
        'candidate_petition_id',
        'metadata_id',
        'petition_answer',
    ];

    public function candidatePetition(): BelongsTo
    {
        return $this->belongsTo(CandidatePetition::class, 'candidate_petition_id');
    }

    public function metadata(): BelongsTo
    {
        return $this->belongsTo(Metadata::class, 'metadata_id');
    }

    public function formField(): BelongsTo
    {
        return $this->metadata();
    }
}