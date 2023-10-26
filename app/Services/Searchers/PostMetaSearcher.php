<?php

namespace App\Services\Searchers;

use App\Models\Post;
use App\Models\PostMeta;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class PostMetaSearcher extends BlogSearcher
{
    protected array $headers = [
        'Blog ID',
        'Post ID',
        'Post',
        'Meta Key',
        'Meta Value',
    ];
    protected string $searchColumn = '';

    public function process(string $blogId, string $blogUrl): bool
    {
        if (! Schema::hasTable('wp_' . $blogId. '_posts')) {
            return false;
        }

        $foundSomething = false;

        $test = $this->exact ? $this->searchText : '%' . $this->searchText . '%';

        $postMetas = (new PostMeta())->setTable('wp_' . $blogId . '_postmeta')
            ->where($this->searchColumn, 'LIKE', $test)
            ->orderBy('meta_id');

        $postMetas->each(function (PostMeta $postMeta) use ($blogId, $blogUrl, &$foundSomething) {
            if ($this->isNotPublished($blogId, $postMeta->post_id)) {
                return;
            }

            $this->found->push([
                'blog_id' => $blogId,
                'blog_url' => $blogUrl,
                'post_id' => $postMeta->post_id,
                'post_name' => $this->getPageName($blogId, $postMeta->post_id),
                'meta_key' => $postMeta->meta_key,
                'meta_value' => $postMeta->meta_value,
            ]);
        });

        return $foundSomething;
    }

    public function setMetaKey(?string $metaKey = null): self
    {
        $this->metaKey = $metaKey;

        return $this;
    }

    protected function isNotPublished($blogId, $postId): bool
    {
        $post = (new Post())->setTable('wp_' . $blogId . '_posts')
            ->where('ID', $postId)
            ->where('post_status', 'publish')
            ->first();

        return (bool)$post;
    }

    protected function getPageName($blogId, $postId): string
    {
        $post = (new Post())->setTable('wp_' . $blogId . '_posts')
            ->where('ID', $postId)
            ->first();

        return $post->post_name;
    }

    public function render(): string
    {
        $html = '';

        $this->foundCount = 0;
        $html .= '<div style="font-family: sans-serif">';
        $html .= self::TABLE_TAG;
        $html .= $this->buildHeader();
        $this->found->each(function ($postMeta) use (&$html) {
            $url = $postMeta['blog_url'] . $postMeta['post_name'];
            $html .= '   <tr style="background-color: ' . $this->setRowColor($this->foundCount) . ';">';
            $html .= '      <td class="align-top first-cell">';
            $html .= $postMeta['blog_id'];
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= $postMeta['post_id'];
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= '<a href="' . $url . '" target="_blank">' . $url . '</a><br>';
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= $postMeta['meta_key'];
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= $this->highlight($postMeta['meta_value']);
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
        echo 'No search text specified. Syntax: ' . URL::to('/in_post') . '?text=';
    }
}
