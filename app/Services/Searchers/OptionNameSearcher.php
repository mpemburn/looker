<?php

namespace App\Services\Searchers;

use App\Models\Option;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class OptionNameSearcher extends BlogSearcher
{
    protected array $already = [];
    protected array $headers = [
        'Blog&nbsp;ID',
        'Blog URL',
        'Option',
        'Value'
    ];

    public function process(string $blogId, string $blogUrl): bool
    {
        if (!Schema::hasTable('wp_' . $blogId . '_options')) {
            return false;
        }
        $foundSomething = false;

        $options = (new Option())->setTable('wp_' . $blogId . '_options')
            ->orderBy('option_id');

        $options->each(function (Option $option) use ($blogId, $blogUrl, &$foundSomething) {
            $foundContent = $this->wasFound($option->option_name);
            if ($foundContent) {
                $foundSomething = true;
                $this->found->push([
                    'blog_id' => $blogId,
                    'blog_url' => $blogUrl,
                    'option_name' => $option->option_name,
                    'option_value' => $option->option_value,
                ]);
            }
        });

        return $foundSomething;
    }

    public function render(bool $showNotFound = false): string
    {
        $html = '';

        $found = $showNotFound ? $this->notFound : $this->found;
        $this->foundCount = 0;
        $html .= self::TABLE_TAG_START;
        $html .= $this->buildHeader();
        $found->each(function ($item) use (&$html) {
            if (in_array($item['blog_id'], $this->unique)) {
                return;
            }
            $url = $item['blog_url'];
            $html .= '   <tr style="background-color: ' . $this->setRowColor($this->foundCount) . ';">';
            $html .= self::TABLE_CELL_CENTER;
            $html .= $item['blog_id'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_TOP;
            $html .= $this->makeLink($url);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_TOP;
            $html .= $item['option_name'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_TOP;
            $html .= strip_tags($item['option_value']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_ROW_END;

            $this->foundCount++;
            $this->unique[] = $item['blog_id'];
        });
        $html .= self::TABLE_TAG_END;

        return $html;
    }

    protected function error(): void
    {
        echo 'No text specified. Syntax: ' . URL::to('/in_post') . '?text=';
    }
}
