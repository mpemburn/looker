<?php

namespace App\Models;

use App\Traits\BindsDynamically;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostMeta extends Model
{
    use BindsDynamically;

    public $incrementing = false;
    protected $primaryKey = null;

    use HasFactory;
}
