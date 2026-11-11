<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrespondenceEntry extends Model
{
    protected $table = 'correspondence_entries';

    protected $fillable = [
        'correspondence_thread_id',
        'user_id',
        'persona_id',
        'correspondence_text',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(CorrespondenceThread::class, 'correspondence_thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }
}
