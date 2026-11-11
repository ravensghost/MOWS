<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublicationState extends Model
{
    protected $table = 'publication_states';

    protected $fillable = [
        'state_title',
        'book_url',
        'state_sort_order',
    ];

    public function volumes(): HasMany
    {
        return $this->hasMany(Volume::class, 'publication_state_id');
    }

    public function books(): HasMany
    {
        return $this->hasMany(Volume::class, 'publication_state_id');
    }
}
