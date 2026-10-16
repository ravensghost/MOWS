<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class NavigationLink extends Model
{
   protected $guarded = [];

   protected static function booted(): void
   {
      static::saved(function () {
         Cache::forget('navigation_links_all_array');
      });

      static::deleted(function () {
         Cache::forget('navigation_links_all_array');
      });
   }

   public function children()
   {
      return $this->hasMany(NavigationLink::class, 'parent_id')->orderBy('order');
   }

   public function parent()
   {
      return $this->belongsTo(NavigationLink::class, 'parent_id');
   }
}
