<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentRating extends Model
    {
        protected $table = 'content_ratings';
        protected $guarded = [];
        public $timestamps = false;
    }