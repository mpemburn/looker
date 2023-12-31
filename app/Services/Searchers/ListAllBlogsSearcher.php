<?php

namespace App\Services\Searchers;

use App\Models\Option;
use App\Models\WpBlogs;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class ListAllBlogsSearcher extends BlogSearcher
{
    protected array $headers = [
        'Blog&nbsp;ID',
        'Admin&nbsp;Email',
        'Path',
        'Created',
        'Updated',
        'Public',
        'Archived',
        'Mature',
        'Spam',
        'Deleted',
    ];

    public function run(?string $searchText, bool $exact = false, bool $verbose = false): BlogSearcher
    {
        $blogs = WpBlogs::all();

        $blogs->each(function ($blog) {
            $admin_email = 'n/a';
            if (Schema::hasTable('wp_' . $blog['blog_id'] . '_options')) {
                $admin = (new Option())->setTable('wp_' . $blog['blog_id'] . '_options')
                    ->where('option_name', 'admin_email')
                    ->first()
                    ->toArray();
                $admin_email = $admin['option_value'];
            }

            $this->found->push([
                'blog_id' => $blog['blog_id'],
                'admin_email' => $admin_email,
                'path' => $blog['path'],
                'registered' => $blog['registered'],
                'last_updated' => $blog['last_updated'],
                'public' => $blog['public'],
                'archived' => $blog['archived'],
                'mature' => $blog['mature'],
                'spam' => $blog['spam'],
                'deleted' => $blog['deleted'],
                'lang_id' => $blog['lang_id'],
            ]);
        });

        return $this;
    }

    public function process(string $blogId, string $blogUrl): bool
    {
        return true;
    }

    public function render(): string
    {
        $html = '';
        $html .= '<div style="font-family: sans-serif">';
        $html .= self::TABLE_TAG;
        $html .= $this->buildHeader();
        $this->found->each(function ($blog) use (&$html) {
            $html .= '   <tr style="background-color: ' . $this->setRowColor($this->foundCount) . ';">';
            $html .= '      <td class="align-top first-cell text-center">';
            $html .= $blog['blog_id'];
            $html .= '      </td>';
            $html .= '      <td class="align-top text-left">';
            $html .= $blog['admin_email'];
            $html .= '      </td>';
            $html .= '      <td class="align-top text-left">';
            $html .= $blog['path'];
            $html .= '      </td>';
            $html .= '      <td class="align-top text-right">';
            $html .= Carbon::parse($blog['registered'])->format('F j, Y');
            $html .= '      </td>';
            $html .= '      <td class="align-top text-right">';
            $html .= Carbon::parse($blog['last_updated'])->format('F j, Y');
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= $this->toBool($blog['public']);
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= $this->toBool($blog['archived']);
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= $this->toBool($blog['mature']);
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= $this->toBool($blog['spam']);
            $html .= '      </td>';
            $html .= '      <td class="align-top text-center">';
            $html .= $this->toBool($blog['deleted']);
            $html .= '      </td>';
            $html .= '   </tr>';

            $this->foundCount++;
        });
        $html .= '<table>';
        $html .= '<div>';

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
