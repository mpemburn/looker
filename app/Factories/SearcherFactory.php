<?php

namespace App\Factories;

use App\Interfaces\SearcherInterface;
use App\Services\Searchers\BlogByIdSearcher;
use App\Services\Searchers\CommentsSearcher;
use App\Services\Searchers\ListAllBlogsSearcher;
use App\Services\Searchers\MostRecentUpdateSearcher;
use App\Services\Searchers\OptionNameSearcher;
use App\Services\Searchers\OptionsSearcher;
use App\Services\Searchers\PluginSearcher;
use App\Services\Searchers\PostMetaKeysSearcher;
use App\Services\Searchers\PostMetaValuesSearcher;
use App\Services\Searchers\PostsByBlogIdSearcher;
use App\Services\Searchers\PostsSearcher;
use App\Services\Searchers\PostTypeSearcher;
use App\Services\Searchers\ShortCodeSearcher;
use App\Services\Searchers\ThemeSearcher;
use App\Services\Searchers\UsersRolesSearcher;
use App\Services\Searchers\UsersSearcher;

class SearcherFactory
{
    public static function build(string $type): ?SearcherInterface
    {
        return match ($type) {
            'posts' => new PostsSearcher(),
            'posts_by_blog_id' => new PostsByBlogIdSearcher(),
            'post_type' => new PostTypeSearcher(),
            'postmeta_values' => new PostMetaValuesSearcher(),
            'postmeta_keys' => new PostMetaKeysSearcher(),
            'comments' => new CommentsSearcher(),
            'options' => new OptionsSearcher(),
            'option_name' => new OptionNameSearcher(),
            'shortcodes' => new ShortCodeSearcher(),
            'plugins' => new PluginSearcher(),
            'roles' => new UsersRolesSearcher(),
            'themes' => new ThemeSearcher(),
            'updated' => new MostRecentUpdateSearcher(),
            'list_all' => new ListAllBlogsSearcher(),
            'blog_id' => new BlogByIdSearcher(),
            'users' => new UsersSearcher(),
            default => null
        };
    }
}
