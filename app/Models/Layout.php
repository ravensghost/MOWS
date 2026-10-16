<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Layout extends Model
{
protected $fillable = ['name', 'description'];

public function blocks(): HasMany
{
return $this->hasMany(LayoutBlock::class)->orderBy('sort_order');
}

public function pages(): HasMany
{
return $this->hasMany(Page::class);
}
}