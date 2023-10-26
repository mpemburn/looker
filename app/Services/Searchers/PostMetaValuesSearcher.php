<?php

namespace App\Services\Searchers;

use App\Models\Post;
use App\Models\PostMeta;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class PostMetaValuesSearcher extends PostMetaSearcher
{
    protected string $searchColumn = 'meta_value';

}
