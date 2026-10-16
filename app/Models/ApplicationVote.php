<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationVote extends Model
{
    // Allows mass assignment for all columns
    protected $guarded = [];

    /**
     * The user whose application is being voted on.
     */
    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * The council member who cast the vote.
     */
    public function councilMember()
    {
        return $this->belongsTo(User::class, 'council_member_id');
    }
}
