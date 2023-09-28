<?php

namespace App\Services\Searchers;

use App\Models\Post;
use App\Models\PostMeta;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class PostMetaSearcher extends BlogSearcher
{
    protected array $headers = [
        'Post ID',
        'Page',
        'Meta Key',
        'Meta Value',
    ];
    protected ?string $metaKey = null;

    public function process(string $blogId, string $blogUrl): bool
    {
        if (! Schema::hasTable('wp_' . $blogId. '_posts')) {
            return false;
        }

        $foundSomething = false;

        $postMetas = (new PostMeta())->setTable('wp_' . $blogId . '_postmeta')
            ->orderBy('meta_id');

        if ($this->metaKey) {
            $postMetas->where('meta_key', $this->metaKey);
        }

        $postMetas->each(function (PostMeta $postMeta) use ($blogId, $blogUrl, &$foundSomething) {
            if ($this->isNotPublished($blogId, $postMeta->post_id)) {
                return;
            }
            $foundValue = preg_match($this->searchRegex, $postMeta->meta_value, $matches);
            if ($foundValue) {
                $foundSomething = true;
                $this->found->push([
                    'blog_url' => $blogUrl,
                    'post_id' => $postMeta->post_id,
                    'post_name' => $this->getPageName($blogId, $postMeta->post_id),
                    'meta_key' => $postMeta->meta_key,
                    'meta_value' => $postMeta->meta_value,
                ]);
            }
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
        $html .= '<table>';
        $html .= $this->buildHeader();
        $this->found->each(function ($postMeta) use (&$html) {
            $url = $postMeta['blog_url'] . $postMeta['post_name'];
            $bgColor = ($this->foundCount % 2) === 1 ? '#e2e8f0' : '#fffff';
            $html .= '   <tr style="background-color: ' . $bgColor . ';">';
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
            $html .= str_replace($this->searchText, '<strong>' . $this->searchText . '</strong>', $postMeta['meta_value']);
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
