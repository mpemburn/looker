<?php

namespace App\Services\Searchers;

use App\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class MostRecentUpdateSearcher extends BlogSearcher
{
    protected array $headers = [
        'Blog ID',
        'Post',
        'Page',
        'Title',
        'Created',
    ];

    protected ?Carbon $latestDate = null;

    public function process(string $blogId, string $blogUrl): bool
    {
        if (! Schema::hasTable('wp_' . $blogId. '_posts')) {
            return false;
        }

        $foundSomething = false;

        $post = (new Post())->setTable('wp_' . $blogId . '_posts')
            ->whereIn('post_status', ['publish', 'inherit'])
            ->where(function ($post) {
                return $post->where('post_date', $post->max('post_date'));
            })->first();

        if ($post && $this->isLatestPost($post->post_date)) {
            $this->found = collect();
            $this->found->push([
                'blog_id' => $blogId,
                'blog_url' => $blogUrl,
                'post_id' => $post->ID,
                'post_name' => $post->post_name,
                'title' => $post->post_title,
                'date' => $post->post_date,
            ]);
            $foundSomething = true;
        }

        return $foundSomething;
    }

    protected function isLatestPost(string $date): bool
    {
        $thisDate = Carbon::parse($date);

        if (! $this->latestDate || $thisDate->isAfter($this->latestDate)) {
            $this->latestDate = $thisDate;

            return true;
        }

        return false;
    }

    public function render(): string
    {
        $html = '';

        $this->foundCount = 0;
        $html .= self::TABLE_TAG_START;
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
            $html .= self::TABLE_CELL_CENTER;
            $html .= Carbon::parse($page['date'])->format('F j, Y');
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_ROW_END;

            $this->foundCount++;
        });
        $html .= self::TABLE_TAG_END;

        return mb_convert_encoding($html, 'UTF-8', 'UTF-8');
    }

    protected function error(): void
    {
        echo 'No search text specified. Syntax: ' . URL::to('/in_post') . '?text=';
    }
}
