<?php
// === app/Models/FolioRevision.php ===

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolioRevision extends Model
{
    protected $table = 'folio_revisions';

    protected $guarded = [];

    protected $casts = [
        'folio_id' => 'integer',
        'user_id'  => 'integer',
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