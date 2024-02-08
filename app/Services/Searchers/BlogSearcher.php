<?php

namespace App\Services\Searchers;

use App\Interfaces\SearcherInterface;
use App\Models\Blog;
use Illuminate\Support\Collection;

abstract class BlogSearcher implements SearcherInterface
{

    const TABLE_TAG = '<table style="width: 100%;">';

    protected Collection $found;
    protected Collection $notFound;
    protected int $foundCount = 0;
    protected string $searchText;
    protected string $searchRegex;
    protected bool $exact = false;
    protected bool $verbose = false;
    protected array $headers = [];
    protected array $unique = [];

    abstract public function process(string $blogId, string $blogUrl): bool;
    abstract public function render(): string;
    abstract protected function error(): void;

    public function __construct()
    {
        $this->found = collect();
        $this->notFound = collect();
    }

    public function run(?string $searchText, bool $exact = false, bool $verbose = false): self
    {
        if (! $searchText) {
            $this->error();

            return $this;
        }

        $this->verbose = $verbose;
        $this->exact = $exact;

        $blogs = Blog::where('archived', 0)
            ->where('deleted', 0)
            ->where('public', 1);
        $this->searchText = $searchText;
        $this->searchRegex = $this->buildRegex($searchText);

        $blogs->each(function ($blog) {
            $blogId = $blog->blog_id;
            $blogUrl = 'https://' . $blog->domain . $blog->path;
            $this->process($blogId, $blogUrl);
        });

        return $this;
    }

    protected function wasFound(string $testText): bool
    {
        if ($this->exact) {
            // Only return exact word matches
//            return preg_match('/\b' . $this->searchText . '\b/i', $testText);
            return $this->searchText === $testText;
        }

        return preg_match($this->searchRegex, $testText, $matches);
    }

    protected function buildRegex(string $searchText): string
    {
        return '/' . preg_quote($searchText) . '/i';
    }

    protected function buildHeader(): string
    {
        $html = $this->buildColumnGroup();
        $headers = array_is_list($this->headers)
            ? $this->headers
            : array_keys($this->headers);

        $html .= '   <tr style="background-color: #e2e8f0;">';
        foreach ($headers as $header) {
            $html .= '      <th>';
            $html .= $header;
            $html .= '      </th>';
        }
        $html .= '   </tr>';

        return $html;
    }

    protected function buildColumnGroup(): string
    {
        if (array_is_list($this->headers)) {
            return '';
        }

        $html = '<colgroup>';
        foreach (array_values($this->headers) as $width) {
            $html .= '<col span="1" style="width: ' . $width . ';">';
        }
        $html .= '<colgroup>';

        return $html;
    }

    protected function truncateContent(string $content): string
    {
        $length = $this->verbose ? null : 100;

        $position = stripos($content, $this->searchText);

        $start = ! $this->verbose ? $position - 20 : 0;
        $prellipsis = $start > 0 ? '&hellip;' : '';
        $postellipsis = ! $this->verbose && $position > $length ? '&hellip;' : '';

        $truncated = $prellipsis . substr($content, $start, $length) . $postellipsis;

        return $this->highlight($truncated);
    }

    protected function highlight(string $foundString): string
    {
        $replace = '<span class="highlight">$1</span>';

        return preg_replace('/('. preg_quote($this->searchText) . ')/i', $replace, $foundString);
    }

    protected function makeLink(string $url): string
    {
        return '<a href="' . $url . '" target="_blank">' . $url . '</a>';
    }

    protected function setRowColor(int $count): string
    {
        return ($count % 2) === 1 ? '#e2e8f0' : '#fffff';
    }

    protected function makeEnclosingDiv(string $html): string
    {
        $start = '<div style="font-family: sans-serif">';
        $end = '</div>';

        return $start . $html . $end;
    }

    public function getCount(): int
    {
        return $this->foundCount;
    }
}
