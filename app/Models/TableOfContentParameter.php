<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableOfContentParameter extends Model
{
    protected $table = 'table_of_content_parameters';

    protected $fillable = [
        'parameter_mode',
        'parameter_value',
        'parameter_label',
        'parameter_sort_order',
    ];
}
