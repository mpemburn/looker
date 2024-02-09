<?php

namespace App\Services\Searchers;

use App\Models\Option;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class ThemeSearcher extends BlogSearcher
{
    protected array $headers = [
        'Blog ID' => '10%',
        'Blog URL' => '40%',
        'Plugin(s)' => '50%',
    ];

    public function process(string $blogId, string $blogUrl): bool
    {
        if (!Schema::hasTable('wp_' . $blogId . '_options')) {
            return false;
        }
        $foundSomething = false;

        $search = $this->exact ? $this->searchText : '%' . $this->searchText . '%';

        $theme = (new Option())->setTable('wp_' . $blogId . '_options')
            ->where('option_name', 'stylesheet')
            ->where('option_value', 'LIKE', $search)
            ->first();

        if ($theme) {
            $foundSomething = true;

            $this->found->push([
                'blog_id' => $blogId,
                'blog_url' => $blogUrl,
                'theme_name' => $theme->option_value,
            ]);
        }

        return $foundSomething;
    }

    public function render(bool $showNotFound = false): string
    {
        $html = '';

        $found = $showNotFound ? $this->notFound : $this->found;
        $this->foundCount = 0;
        $html .= '<div style="font-family: sans-serif">';
        $html .= self::TABLE_TAG;
        $html .= $this->buildHeader();
        $found->each(function ($item) use (&$html) {
            $url = $item['blog_url'];
            $html .= '   <tr style="background-color: ' . $this->setRowColor($this->foundCount) . ';">';
            $html .= self::TABLE_CELL_CENTER;
            $html .= $item['blog_id'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_TOP;
            $html .= $this->makeLink($url);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_TOP;
            $html .= $this->highlight($item['theme_name']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_ROW_END;

            $this->foundCount++;
            $this->unique[] = $item['blog_id'];
        });
        $html .= self::TABLE_END;
        $html .= '<div>';

        return $html;
    }

    protected function error(): void
    {
        echo 'No text specified. Syntax: ' . URL::to('/in_theme') . '?text=';
    }
}
