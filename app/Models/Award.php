<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'base_color',
        'mid_type',
        'mid_image',
        'mid_css',
        'top_type',
        'top_image',
        'top_css',
        'master_group',
        'award_category_id',
    ];

    public function serviceRecords()
    {
        return $this->hasMany(ServiceRecord::class);
    }
    public function category()
    {
        return $this->belongsTo(AwardCategory::class, 'award_category_id');
    }
}
