<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Option;
use App\Models\Post;
use App\Models\WpOption;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use App\Facades\Database;

class BlogService
{
    public function setDatabase(?string $database): self
    {
        if ($database) {
            Database::setDb($database);
        }

        return $this;
    }


    public function getActiveBlogs(array $filter = []): Collection
    {
        $rows = collect();
        $blogs = Blog::where('archived', 0)
            ->where('deleted', 0)
            ->where('public', 1)
            ->get();

        $blogs->each(function ($blog) use ($rows, $filter) {

            $data = $this->getBlogData($blog, $filter);

            if ($data) {
                $rows->push($data);
            }
        });

        return $rows;
    }

    public function getBlogsById(array $blogIds, array $filter = [])
    {
        $rows = collect();
        $blogs = Blog::whereIn('blog_id', $blogIds)->get();

        $blogs->each(function ($blog) use ($rows, $filter) {

            $data = $this->getBlogData($blog, $filter);

            if ($data) {
                $rows->push($data);
            }
        });

        return $rows;
    }

    public function getPluginList(string $database): array
    {
        $plugins = collect();

        Database::setDb($database);
        $this->getActiveBlogs()->each(function ($blog) use (&$plugins) {
            $activePlugins = (new Option())->setTable('wp_' . $blog['blog_id'] . '_options')
                ->where('option_name', 'active_plugins')
                ->first();
            collect(unserialize($activePlugins->option_value))->each(function ($plugin) use (&$plugins) {
                $parts = explode('/', $plugin);
                $plugins->push(current($parts));
            });
        });

        return array_values($plugins->unique()->sort()->toArray());
    }

    public function getPostTypeList(string $database): array
    {
        Database::setDb($database);

        $postTypes = collect();

        $this->getActiveBlogs()->each(function ($blog) use (&$postTypes) {
            $posts = (new Post())->setTable('wp_' . $blog['blog_id'] . '_posts')
                ->select('post_type')->distinct('post_type')->get();
            $posts->each(function ($post) use (&$postTypes) {
                $postTypes->put($post->post_type, $post->post_type);
            });
        });

        return $postTypes->unique()->sort()->toArray();
    }

    public function getRolesList(string $database): array
    {
        Database::setDb($database);

        $rawRoles = WpOption::where('option_name', 'wp_user_roles')->first();

        $roles = unserialize($rawRoles->option_value);

        $dropdown = collect($roles)->map(function ($role, $key) {
            return $role['name'];
        });

        $dropdown->put('site_administrator', 'Faux Site Adminstrator');

        return $dropdown->toArray();
    }

    public function getThemeList(string $database): array
    {
        $themes = collect();

        Database::setDb($database);
        $rootTheme = (new Option())->setTable('wp_options')
            ->where('option_name', 'stylesheet')
            ->first();
        $themes->push($rootTheme->option_value);

        $this->getActiveBlogs()->each(function ($blog) use (&$themes) {
            $theme = (new Option())->setTable('wp_' . $blog['blog_id'] . '_options')
                ->where('option_name', 'stylesheet')
                ->first();
            $themes->push($theme->option_value);
        });

        return array_values($themes->unique()->sort()->toArray());
    }

    protected function getBlogData(Blog $blog, array $filter): ?array
    {
        if ($blog->blog_id < 2) {
            return null;
        }

        $data = [];

        $options = (new Option())->setTable('wp_' . $blog->blog_id . '_options')
            ->whereIn('option_name', ['siteurl', 'admin_email', 'current_theme', 'template', 'active_plugins'])
            ->orderBy('option_name');

        $data['blog_id'] = $blog->blog_id;
        $data['last_updated'] = $blog->last_updated;

        $options->each(function (Option $option) use (&$data) {
            $data[$option->option_name] = $option->option_value;
        });

        if ($filter && str_replace($filter, '', $data['siteurl']) === $data['siteurl']) {
            return null;
        }

        return $data;
    }

    public function getStaleBlogs(): Collection
    {
        $rows = collect();
        $blogs = Blog::where('archived', 0);

        $blogs->each(function ($blog) use (&$rows) {
            if ($blog->blog_id < 2) {
                return;
            }

            $posts = (new Post())->setTable('wp_' . $blog->blog_id . '_posts')
                ->where('post_status', 'publish');

            if ($posts->count() < 3) {
                $post = (new Post())->setTable('wp_' . $blog->blog_id . '_posts')
                    ->where('post_title', 'LIKE', '%Hello World%')
                    ->orWhere('post_title', 'LIKE', '%Sample Page%')
                    ->orWhere('post_content', 'LIKE', '%cupcake%')
                    ->first();

                if ($post) {
                    $data = [];

                    $data['blog_id'] = $blog->blog_id;
                    $data['last_updated'] = $blog->last_updated;

                    $options = (new Option())->setTable('wp_' . $blog->blog_id . '_options')
                        ->whereIn('option_name', ['siteurl', 'admin_email'])
                        ->orderBy('option_name');

                    $options->each(function (Option $option) use (&$data) {
                        $data[$option->option_name] = $option->option_value;
                    });

                    $rows->push($data);
                }
            }
        });

        return $rows;
    }

    public function getFormattedBlogs(?string $startDate = null, ?string $endDate = null)
    {
        return $this->getActiveBlogs([], $startDate, $endDate)
            ->transform(function ($blog) {
                $plugins = collect(unserialize($blog['active_plugins']))->map(function ($plugin) {
                    return current(explode('/', $plugin));
                })->implode(',');
                $blog['last_updated'] = Carbon::parse($blog['last_updated'])->format('m/d/Y');
                $blog['template'] = isset($blog['template']) && strlen($blog['template']) > 0
                    ? $blog['template']
                    : 'N/A';
                $blog['current_theme'] = isset($blog['current_theme']) && strlen($blog['current_theme']) > 0
                    ? $blog['current_theme']
                    : 'N/A';
                $blog['active_plugins'] = $plugins;

                return $blog;
            });
    }

    public function findPluginInSubsite(string $pluginName, string $title, $notFoundOnly = false)
    {
        $data = $this->getActiveBlogs();
        $notFound = true;

        $titleRow = '<div><b>' . $title . '</b></div>';
        $html = '<div style="font-family:  sans-serif">';
        $html .= $titleRow;
        $data->each(function ($row) use (&$notFound, &$html, $pluginName) {
            if (isset($row['active_plugins']) && stripos($row['active_plugins'], $pluginName) !== false) {
                $html .= '<div>';
                $html .= $row['siteurl'];
                $html .= '</div>';
                $notFound = false;
            }
        });

        if ($notFoundOnly) {
            return $notFound ? $titleRow : '';
        }

        return $notFound ? $titleRow . '<div style="font-family: sans-serif">Not Found</div><br>' : $html . '<br>';
    }

    public function findThemesInSubsite(string $themeName, $notFoundOnly = false)
    {
        $data = $this->getActiveBlogs();
        $notFound = true;
        $notFoundMsg = '<div style="font-family: sans-serif">Not Found</div><br>';

        $titleRow = '<div style="font-family: sans-serif"><b>' . $themeName . '</b></div>';
        $html = '<div style="font-family: sans-serif">';
        $html .= $titleRow;
        $data->each(function ($row) use (&$notFound, &$html, $themeName) {
            if (!isset($row['current_theme'])) {
                return;
            }
            if ($row['current_theme'] === $themeName) {
                $html .= '<div>';
                $html .= $row['siteurl'];
                $html .= '</div>';
                $notFound = false;
            }
        });
        $html .= '</div>';

        if ($notFoundOnly) {
            return $notFound ? $titleRow : '';
        }

        return $notFound ? $titleRow . '<div style="font-family: sans-serif">Not Found</div><br>' : $html . '<br>';
    }

}
