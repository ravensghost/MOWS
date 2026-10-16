<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookStatus extends Model
{
    protected $table = 'book_statuses';
    protected $fillable = ['name', 'slug', 'sort_order'];

    public function books()
    {
        return $this->hasMany(Book::class, 'status_id');
    }
}