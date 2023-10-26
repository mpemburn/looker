<?php

namespace App\Factories;

use App\Interfaces\SearcherInterface;
use App\Services\Searchers\MostRecentUpdateSearcher;
use App\Services\Searchers\OptionNameSearcher;
use App\Services\Searchers\OptionsSearcher;
use App\Services\Searchers\PluginSearcher;
use App\Services\Searchers\PostMetaKeysSearcher;
use App\Services\Searchers\PostMetaValuesSearcher;
use App\Services\Searchers\PostsSearcher;
use App\Services\Searchers\PostMetaSearcher;
use App\Services\Searchers\ShortCodeSearcher;
use App\Services\Searchers\ThemeSearcher;

class SearcherFactory
{
    public static function build(string $type): ?SearcherInterface
    {
        return match ($type) {
            'posts' => new PostsSearcher(),
            'postmeta_values' => new PostMetaValuesSearcher(),
            'postmeta_keys' => new PostMetaKeysSearcher(),
            'options' => new OptionsSearcher(),
            'option_name' => new OptionNameSearcher(),
            'shortcodes' => new ShortCodeSearcher(),
            'plugins' => new PluginSearcher(),
            'themes' => new ThemeSearcher(),
            'updated' => new MostRecentUpdateSearcher(),
            default => null
        };
    }
}
