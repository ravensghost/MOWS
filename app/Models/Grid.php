<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grid extends Model
{
protected $fillable = ['grid_title', 'grid_synopsis'];

public function blocks(): HasMany
{
return $this->hasMany(GridModule::class)->orderBy('grid_modules_sort_order');
}

public function pages(): HasMany
{
return $this->hasMany(Book::class);
}
}