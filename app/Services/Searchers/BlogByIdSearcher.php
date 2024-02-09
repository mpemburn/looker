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

    public function run(?string $searchText, array $options): BlogSearcher
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
            $html .= self::TABLE_TAG_START;
            $html .= $this->buildHeader();
            $html .= self::TABLE_ROW_START;
            $html .= self::TABLE_FIRST_CELL;
            $html .= $this->data['blog_id'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $this->data['site_id'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $this->data['domain'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $this->data['path'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= Carbon::parse($this->data['registered'])->format('F j, Y');
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= Carbon::parse($this->data['last_updated'])->format('F j, Y');
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $this->toBool($this->data['public']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $this->toBool($this->data['archived']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $this->toBool($this->data['mature']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $this->toBool($this->data['spam']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $this->toBool($this->data['deleted']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_ROW_END;
            $html .= self::TABLE_TAG_END;
        }

        return mb_convert_encoding($html, 'UTF-8', 'UTF-8');
    }

    protected function toBool(string $value): string
    {
        return $value ? 'true' : 'false';
    }

    protected function error(): void
    {
        // No errors here
    }
}
