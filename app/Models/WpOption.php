<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class WpOption extends Model
{
    public $table = 'wp_options';

    public $fillable = [
        'option_id',
        'option_name',
        'option_value',
    ];
}
