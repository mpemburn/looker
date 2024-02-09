<?php

namespace App\Services\Searchers;

use App\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class PostTypeSearcher extends BlogSearcher
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
            $foundType = $this->wasFound($post->post_type);
            if ($foundType) {
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
        $html .= self::TABLE_TAG;
        $html .= $this->buildHeader();
        $this->found->each(function ($page) use (&$html) {
            $url = $page['blog_url'] . $page['post_name'];
            $html .= '   <tr style="background-color: ' . $this->setRowColor($this->foundCount) . ';">';
            $html .= self::TABLE_CELL_CENTER;
            $html .= $page['blog_id'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $page['post_id'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_TOP;
            $html .= $this->makeLink($url);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_TOP;
            $html .= $this->highlight($page['title']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_TOP;
            $html .= $this->truncateContent(strip_tags($page['content']));
            $html .= '      <div class="hidden">';
            $html .= $this->highlight(strip_tags($page['content']));
            $html .= '      <div>';
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_TOP;
            $html .= Carbon::parse($page['date'])->format('F j, Y');
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_ROW_END;

            $this->foundCount++;
        });
        $html .= self::TABLE_END;
        $html .= '<div>';

        return mb_convert_encoding($html, 'UTF-8', 'UTF-8');
    }

    protected function error(): void
    {
        echo 'No search text specified. Syntax: ' . URL::to('/in_post') . '?text=';
    }
}
