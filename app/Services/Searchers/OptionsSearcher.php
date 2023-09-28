<?php

namespace App\Services\Searchers;

use App\Models\Option;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class OptionsSearcher extends BlogSearcher
{
    protected array $already = [];
    protected array $headers = [
        'Blog ID',
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
            $foundContent = preg_match($this->searchRegex, $option->option_value, $matches);

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
        $html .= '<div style="font-family: sans-serif">';
        $html .= '<table>';
        $html .= $this->buildHeader();
        $found->each(function ($item) use (&$html) {
            if (in_array($item['blog_id'], $this->unique)) {
                return;
            }
            $url = $item['blog_url'];
            $bgColor = ($this->foundCount % 2) === 1 ? '#e2e8f0' : '#fffff';
            $html .= '   <tr style="background-color: ' . $bgColor . ';">';
            $html .= '      <td class="align-top">';
            $html .= $item['blog_id'];
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= '<a href="' . $url . '" target="_blank">' . $url . '</a><br>';
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= $item['option_name'];
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= $this->truncateContent($item['option_value']);
            $html .= '      </td>';
            $html .= '   </tr>';

            $this->foundCount++;
            $this->unique[] = $item['blog_id'];
        });
        $html .= '<table>';
        $html .= '<div>';

        return $html;
    }

    protected function error(): void
    {
        echo 'No text specified. Syntax: ' . URL::to('/in_post') . '?text=';
    }
}
