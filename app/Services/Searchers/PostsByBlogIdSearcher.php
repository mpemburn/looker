<?php

namespace App\Services\Searchers;

use App\Models\Blog;
use App\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;


class PostsByBlogIdSearcher extends BlogSearcher
{
    protected array $headers = [
        'ID',
        'Post',
        'Page',
        'Title',
        'Content',
        'Created',
    ];

    protected ?array $data = null;

    public function run(?string $searchText, bool $exact = false, bool $verbose = false): BlogSearcher
    {
        $blogId = $searchText;

        if (! Schema::hasTable('wp_' . $blogId. '_posts')) {
            return $this;
        }

        $this->searchText = '';

        $posts = (new Post())->setTable('wp_' . $blogId . '_posts')
            ->where('post_status', 'publish')
            ->orderBy('ID');

        $blog = Blog::where('blog_id', $blogId)->first();
        $blogUrl = 'https://' . $blog->domain . '/';

        $posts->each(function (Post $post) use ($blogUrl, $blogId,) {
            $this->found->push([
                'blog_id' => $blogId,
                'blog_url' => $blogUrl,
                'post_id' => $post->ID,
                'post_name' => $post->post_name,
                'title' => $post->post_title,
                'date' => $post->post_date,
                'content' => trim($post->post_content),
            ]);
        });

        return $this;
    }

    public function process(string $blogId, string $blogUrl): bool
    {
        return true;
    }

    public function render(): string
    {
        $html = '';

        $this->foundCount = 0;
        $html .= '<div style="font-family: sans-serif">';
        $html .= self::TABLE_TAG;
        $html .= $this->buildHeader();
        $this->found->each(function ($page) use (&$html) {
            $url = $page['blog_url'] . $page['post_name'];
            $html .= '   <tr style="background-color: ' . $this->setRowColor($this->foundCount) . ';">';
            $html .= '      <td class="align-top text-center">';
            $html .= $page['blog_id'];
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= $page['post_id'];
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= $this->makeLink($url);
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= $this->highlight($page['title']);
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= strip_tags($page['content']);
            $html .= '      <div class="hidden">';
            $html .= $this->highlight(strip_tags($page['content']));
            $html .= '      </div>';
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= Carbon::parse($page['date'])->format('F j, Y');
            $html .= '      </td>';
            $html .= '   </tr>';

            $this->foundCount++;
        });
        $html .= '<table>';
        $html .= '<div>';

        return mb_convert_encoding($html, 'UTF-8', 'UTF-8');
    }

    protected function error(): void
    {
    }
}
