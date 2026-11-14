<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $table = 'assignments';

    protected $fillable = [
        'division_id',
        'assignment_title',
        'assignment_description',
        'maximum_occupants',
        'is_recruitment_open',
        'is_visible',
    ];

    protected $casts = [
        'maximum_occupants'   => 'integer',
        'is_recruitment_open' => 'boolean',
        'is_visible'          => 'boolean',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function personae()
    {
        return $this->hasMany(Persona::class, 'assignment_id');
    }
}
