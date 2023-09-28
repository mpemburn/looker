<?php

namespace App\Factories;

use App\Interfaces\SearcherInterface;
use App\Services\Searchers\OptionNameSearcher;
use App\Services\Searchers\OptionsSearcher;
use App\Services\Searchers\PostsSearcher;
use App\Services\Searchers\PostMetaSearcher;
use App\Services\Searchers\ShortCodeSearcher;

class SearcherFactory
{
    public static function build(string $type): ?SearcherInterface
    {
        return match ($type) {
            'posts' => new PostsSearcher(),
            'postmeta' => new PostMetaSearcher(),
            'options' => new OptionsSearcher(),
            'option_name' => new OptionNameSearcher(),
            'shortcodes' => new ShortCodeSearcher(),
            default => null
        };
    }
}
