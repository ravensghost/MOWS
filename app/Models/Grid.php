<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grid extends Model
{
protected $fillable = ['grid_title', 'grid_synopsis'];

public function gridModules(): HasMany
{
return $this->hasMany(GridModule::class)->orderBy('grid_module_sort_order');
}

public function books(): HasMany
{
return $this->hasMany(Book::class);
}
}