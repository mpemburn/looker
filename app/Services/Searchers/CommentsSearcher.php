<?php

namespace App\Services\Searchers;

use App\Models\Comment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class CommentsSearcher extends BlogSearcher
{
    protected array $headers = [
        'Blog&nbsp;ID',
        'URL',
        'Post&nbsp;ID',
        'Comment',
        'Author',
        'Date',
    ];

    public function process(string $blogId, string $blogUrl): bool
    {
        if (! Schema::hasTable('wp_' . $blogId. '_comments')) {
            return false;
        }

        $foundSomething = false;

        $comments = (new Comment())->setTable('wp_' . $blogId . '_comments')
            ->orderBy('comment_ID');

        $comments->each(function (Comment $comment) use ($blogUrl, $blogId, &$foundSomething) {
            $found = $this->wasFound($comment->comment_content);
            if ($found) {
                $foundSomething = true;
                $this->found->push([
                    'blog_id' => $blogId,
                    'blog_url' => $blogUrl,
                    'post_id' => $comment->comment_post_ID,
                    'author_email' => $comment->comment_author_email,
                    'date' => $comment->comment_date,
                    'content' => trim($comment->comment_content),
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
        $this->found->each(function ($comment) use (&$html) {
            $url = $comment['blog_url'];
            $html .= '   <tr style="background-color: ' . $this->setRowColor($this->foundCount) . ';">';
            $html .= '      <td class="align-top text-center">';
            $html .= $comment['blog_id'];
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= $this->makeLink($url);
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= $comment['post_id'];
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= $this->truncateContent(strip_tags($comment['content']));
            $html .= '      </td>';
            $html .= '      <div class="hidden">';
            $html .= $this->highlight(strip_tags($comment['content']));
            $html .= '      </div>';
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= $comment['author_email'];
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= Carbon::parse($comment['date'])->format('F j, Y');
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
