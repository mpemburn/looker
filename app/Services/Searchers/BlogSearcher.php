<?php

namespace App\Services\Searchers;

use App\Facades\Database;
use App\Interfaces\SearcherInterface;
use App\Models\Blog;
use App\Models\SavedSearch;
use Illuminate\Support\Collection;

abstract class BlogSearcher implements SearcherInterface
{

    const TABLE_TAG_START = '<table id="results_table">';
    const TABLE_ROW_START = '   <tr>';
    const TABLE_FIRST_CELL = '      <td class="align-top first-cell text-center">';
    const TABLE_CELL_TOP = '      <td class="align-top">';
    const TABLE_CELL_CENTER = '      <td class="align-top text-center">';
    const TABLE_CELL_LEFT = '      <td class="align-top text-left">';
    const TABLE_CELL_RIGHT = '      <td class="align-top text-right">';
    const TABLE_CELL_END = '      </td>';
    const TABLE_ROW_END = '   </tr>';
    const TABLE_TAG_END = '</table>';

    protected Collection $found;
    protected Collection $notFound;
    protected int $foundCount = 0;
    protected string $database;
    protected string $searchText;
    protected string $searchRegex;
    protected bool $exact = false;
    protected bool $showRaw = false;
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

    public function setDatabase(string $database): void
    {
        Database::setDb($database);

        $this->database = $database;
    }

    public function run(?string $searchText, array $options): self
    {
        if (! $searchText) {
            $this->error();

            return $this;
        }

        $this->verbose = $options['verbose'] ?? false;
        $this->exact = $options['exact'] ?? false;
        $this->showRaw = $options['show_raw'] ?? false;

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

        $html .= '      <thead>';
        $html .= '   <tr style="background-color: #e2e8f0;">';
        foreach ($headers as $header) {
            $html .= '      <th>';
            $html .= $header;
            $html .= '      </th>';
        }
        $html .= self::TABLE_ROW_END;
        $html .= '      </thead>';

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
        if (! $this->showRaw) {
            $content = strip_tags($content);
        }

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
        return ($count % 2) === 1 ? '#e2e8f0' : '#ffffff';
    }

    public function getCount(): int
    {
        return $this->foundCount;
    }
}
