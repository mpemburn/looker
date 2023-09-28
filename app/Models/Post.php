<?php

namespace App\Models;

use App\Traits\BindsDynamically;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Post extends Model
{
    use BindsDynamically;

    public $incrementing = false;
    protected $primaryKey = null;

    use HasFactory;

    public function hasTable()
    {
        return Schema::hasTable($this->getTable());
    }
}
