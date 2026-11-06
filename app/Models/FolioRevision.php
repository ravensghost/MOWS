<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolioRevision extends Model
{
    protected $table = 'folio_revisions';
    protected $guarded = [];

    public function folio()
    {
        return $this->belongsTo(Folio::class, 'folio_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
