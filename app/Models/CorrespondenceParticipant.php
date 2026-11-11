<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CorrespondenceParticipant extends Model
{
    use SoftDeletes;

    protected $table = 'correspondence_participants';

    protected $fillable = [
        'correspondence_thread_id',
        'user_id',
        'persona_id',
        'last_read_at',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
    ];

    public function thread()
    {
        return $this->belongsTo(CorrespondenceThread::class, 'correspondence_thread_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function character()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }
}
