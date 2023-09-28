<?php

namespace App\Services\Searchers;

use App\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class ShortCodeSearcher extends BlogSearcher
{
    protected array $headers = [
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
        $this->searchRegex ='/\[' . str_replace(['[', ']'], '', $this->searchText) . '/';

        $posts = (new Post())->setTable('wp_' . $blogId . '_posts')
            ->where('post_status', 'publish')
            ->orderBy('ID');

        $posts->each(function (Post $post) use ($blogUrl, &$foundSomething) {
            $found = preg_match($this->searchRegex, $post->post_content, $matches);
            if ($found) {
                $foundSomething = true;
                $this->found->push([
                    'blog_url' => $blogUrl,
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
            $html .= '<a href="' . $url . '" target="_blank">' . $url . '</a><br>';
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= $page['title'];
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= $this->truncateContent(strip_tags($page['content']));
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= Carbon::parse($page['date'])->format('F j, Y');
            $html .= '      </td>';
            $html .= '   </tr>';

            $this->foundCount++;
        });
        $html .= '<table>';
        $html .= '<div>';

        return $html;
    }

    protected function error(): void
    {
        echo 'No shortcode specified. Syntax: ' . URL::to('/shortcode') . '?text=';
    }
}
