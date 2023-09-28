<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogList extends Model
{
    protected $fillable = [
        'site',
        'blog_id',
        'deprecated',
        'blog_url',
        'last_updated',
        'admin_email',
        'current_theme',
        'template',
    ];
    public $table = 'blogs_list';
}
