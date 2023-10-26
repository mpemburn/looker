<?php

namespace App\Services\Searchers;

use App\Models\WpBlogs;
use Illuminate\Support\Carbon;

class BlogByIdSearcher extends BlogSearcher
{
    protected array $headers = [
        'Blog ID',
        'Site ID',
        'Domain',
        'Path',
        'Created',
        'Updated',
        'Public',
        'Archived',
        'Mature',
        'Spam',
        'Deleted',
    ];

    protected ?array $data = null;

    public function run(?string $searchText, bool $exact = false, bool $verbose = false): BlogSearcher
    {
        $blog = WpBlogs::select("*")
            ->where('blog_id', $searchText);

        if ($blog->exists()) {
            $this->data = $blog->first()->toArray();
            $this->foundCount = 1;
        }

        return $this;
    }

    public function process(string $blogId, string $blogUrl): bool
    {
        return true;
    }

    public function render(): string
    {
        $html = '';

        if ($this->data) {
            $html .= '<div style="font-family: sans-serif">';
            $html .= self::TABLE_TAG;
            $html .= $this->buildHeader();
            $html .= '   <tr>';
            $html .= '      <td class="align-top first-cell text-center">';
            $html .= $this->data['blog_id'];
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= $this->data['site_id'];
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= $this->data['domain'];
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= $this->data['path'];
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= Carbon::parse($this->data['registered'])->format('F j, Y');
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= Carbon::parse($this->data['last_updated'])->format('F j, Y');
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= $this->toBool($this->data['public']);
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= $this->toBool($this->data['archived']);
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= $this->toBool($this->data['mature']);
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= $this->toBool($this->data['spam']);
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= $this->toBool($this->data['deleted']);
            $html .= '      </td>';
            $html .= '   </tr>';
            $html .= '<table>';
            $html .= '<div>';
        }

        return mb_convert_encoding($html, 'UTF-8', 'UTF-8');
    }

    protected function toBool(string $value): string
    {
        return $value ? 'true' : 'false';
    }

    protected function error(): void
    {
    }
}
