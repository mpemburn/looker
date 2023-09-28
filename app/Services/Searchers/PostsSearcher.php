<?php

namespace App\Services\Searchers;

use App\Models\Option;
use App\Models\Post;
use App\Services\DatabaseService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class PostsSearcher extends BlogSearcher
{
    protected array $headers = [
        'ID',
        'Post',
        'Page',
        'Title',
        'Content',
        'Created',
    ];

    public function process(string $blogId, string $blogUrl): bool
    {
        if (! Schema::hasTable('wp_' . $blogId. '_posts')) {
            return false;
        }

        $foundSomething = false;

        $posts = (new Post())->setTable('wp_' . $blogId . '_posts')
            ->where('post_status', 'publish')
            ->orderBy('ID');

        $posts->each(function (Post $post) use ($blogUrl, $blogId, &$foundSomething) {
            $foundContent = preg_match($this->searchRegex, $post->post_content, $matches);
            $foundTitle = preg_match($this->searchRegex, $post->post_title, $matches);
            if ($foundContent || $foundTitle) {
                $foundSomething = true;
                $this->found->push([
                    'blog_id' => $blogId,
                    'blog_url' => $blogUrl,
                    'post_id' => $post->ID,
                    'post_name' => $post->post_name,
                    'title' => $post->post_title,
                    'date' => $post->post_date,
                    'content' => trim($post->post_content),
                ]);
            }
        });

        return $foundSomething;
    }

    public function render(): string
    {
        $html = '';

        $this->foundCount = 0;
        $html .= '<div style="font-family: sans-serif">';
        $html .= '<table>';
        $html .= $this->buildHeader();
        $this->found->each(function ($page) use (&$html) {
            $url = $page['blog_url'] . $page['post_name'];
            $bgColor = ($this->foundCount % 2) === 1 ? '#e2e8f0' : '#fffff';
            $html .= '   <tr style="background-color: ' . $bgColor . ';">';
            $html .= '      <td class="align-top">';
            $html .= $page['blog_id'];
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= $page['post_id'];
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= '<a href="' . $url . '" target="_blank">' . $url . '</a><br>';
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= str_replace($this->searchText, '<strong>' . $this->searchText . '</strong>', $page['title']);
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= $this->truncateContent(strip_tags($page['content']));
            $html .= '      <div class="hidden">';
            $html .= str_replace($this->searchText, '<strong>' . $this->searchText . '</strong>', strip_tags($page['content']));
            $html .= '      <div>';
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
        echo 'No search text specified. Syntax: ' . URL::to('/in_post') . '?text=';
    }
}
