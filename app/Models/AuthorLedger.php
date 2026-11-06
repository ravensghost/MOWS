<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorLedger extends Model
{
    protected $table = 'author_ledgers';
    public $timestamps = false;

    protected $fillable = [
        'folio_id',
        'user_id',
        'contribution_word_count',
    ];

    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class, 'folio_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
