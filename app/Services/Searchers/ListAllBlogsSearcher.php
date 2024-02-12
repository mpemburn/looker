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
        'URL',
        'Admin&nbsp;Email',
        'Created',
        'Updated',
        'Public',
        'Archived',
        'Mature',
        'Spam',
        'Deleted',
    ];

    public function run(?string $searchText, array $options): BlogSearcher
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
                'siteurl' => 'https://' . $blog['domain'],
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
        $html .= self::TABLE_TAG_START;
        $html .= $this->buildHeader();
        $this->found->each(function ($blog) use (&$html) {
            $html .= '   <tr style="background-color: ' . $this->setRowColor($this->foundCount) . ';">';
            $html .= self::TABLE_FIRST_CELL;
            $html .= $blog['blog_id'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_LEFT;
            $html .= $this->makeLink($blog['siteurl'] . $blog['path']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_LEFT;
            $html .= $blog['admin_email'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_RIGHT;
            $html .= Carbon::parse($blog['registered'])->format('F j, Y');
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_RIGHT;
            $html .= Carbon::parse($blog['last_updated'])->format('F j, Y');
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $this->toBool($blog['public']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $this->toBool($blog['archived']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $this->toBool($blog['mature']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $this->toBool($blog['spam']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $this->toBool($blog['deleted']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_ROW_END;

            $this->foundCount++;
        });
        $html .= self::TABLE_TAG_END;

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
