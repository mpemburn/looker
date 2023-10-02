<?php

namespace App\Services\Searchers;

use App\Interfaces\SearcherInterface;
use App\Models\Blog;
use App\Models\Option;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

abstract class BlogSearcher implements SearcherInterface
{

    protected Collection $found;
    protected Collection $notFound;
    protected int $foundCount = 0;
    protected string $searchText;
    protected string $searchRegex;
    protected bool $verbose;
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

    public function run(?string $searchText, bool $verbose = false): self
    {
        if (! $searchText) {
            $this->error();

            return $this;
        }

        $this->verbose = $verbose;

        $blogs = Blog::where('archived', 0)
            ->where('deleted', 0)
            ->where('public', 1);

        $this->searchText = $searchText;
        $this->searchRegex = '/' . str_replace('/', '\/', $this->searchText) . '/';

        $blogs->each(function ($blog) use ($searchText) {
            $blogId = $blog->blog_id;
            $blogUrl = 'https://' . $blog->domain . $blog->path;
            $found = $this->process($blogId, $blogUrl);
        });

        return $this;
    }

    protected function buildHeader(): string
    {
        $html = '   <tr style="background-color: #e2e8f0;">';
        foreach ($this->headers as $header) {
            $html .= '      <td>';
            $html .= $header;
            $html .= '      </td>';
        }
        $html .= '   </tr>';

        return $html;
    }

    protected function truncateContent(string $content): string
    {
        $length = $this->verbose ? null : 100;

        $highlight = str_replace($this->searchText, '<strong>' . $this->searchText . '</strong>', $content);
        $position = stripos($highlight, $this->searchText);

        $start = ($position - 20) > 0 ? $position - 20 : 0;
        $prellipsis = $start > 0 ? '&hellip;' : '';
        $postellipsis = ! $this->verbose && strlen($highlight) > $length ? '&hellip;' : '';

        return $prellipsis . substr($highlight, $start, $length) . $postellipsis;
    }

    public function getCount(): int
    {
        return $this->foundCount;
    }
}
