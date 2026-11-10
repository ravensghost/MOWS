<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouncilEvaluation extends Model
{
    protected $table = 'council_evaluations';

    // Allows mass assignment for all columns
    protected $guarded = [];

    /**
     * The user who is the subject of the candidate petition.
     */
    public function subjectUser()
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }

    /**
     * The evaluating council member who cast the evaluation.
     */
    public function evaluatingUser()
    {
        return $this->belongsTo(User::class, 'evaluating_user_id');
    }

    /**
     * The candidate petition being evaluated.
     */
    public function candidatePetition()
    {
        return $this->belongsTo(CandidatePetition::class, 'candidate_petition_id');
    }
}
