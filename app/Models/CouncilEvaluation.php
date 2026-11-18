<?php
// === app/Models/CouncilEvaluation.php ===

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouncilEvaluation extends Model
{
    protected $table = 'council_evaluations';

    protected $guarded = [];

    protected $casts = [
        'candidate_petition_id' => 'integer',
        'subject_user_id'       => 'integer',
        'evaluating_user_id'    => 'integer',
    ];

    public function subjectUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }

    public function evaluatingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluating_user_id');
    }

    public function candidatePetition(): BelongsTo
    {
        return $this->belongsTo(CandidatePetition::class, 'candidate_petition_id');
    }
}