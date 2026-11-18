<?php
// === app/Models/InsigniaCatalogue.php ===

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsigniaCatalogue extends Model
{
    protected $table = 'insignia_catalogues';

    protected $fillable = [
        'catalogue_title',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function insignia(): HasMany
    {
        return $this->hasMany(Insignia::class, 'insignia_catalogue_id')->orderBy('insignia_sort_order');
    }

    public function ranks(): HasMany
    {
        return $this->insignia();
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class, 'insignia_catalogue_id');
    }

    public function sections(): HasMany
    {
        return $this->divisions();
    }
}