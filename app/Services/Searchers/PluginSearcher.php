<?php

namespace App\Services\Searchers;

use App\Models\Option;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class PluginSearcher extends BlogSearcher
{
    protected array $headers = [
        '10%' => 'Blog ID',
        '40%' => 'Blog URL',
        '50%' => 'Plugin(s)',
    ];

    public function process(string $blogId, string $blogUrl): bool
    {
        if (!Schema::hasTable('wp_' . $blogId . '_options')) {
            return false;
        }
        $foundSomething = false;

        $plugins = (new Option())->setTable('wp_' . $blogId . '_options')
            ->where('option_name', 'active_plugins')
            ->first();

        $plugins = unserialize($plugins->option_value);
        $foundPlugins = collect();
        collect($plugins)->each(function ($plugin) use ($blogId, $blogUrl, &$foundSomething, &$foundPlugins){
            $directory = current(explode('/', $plugin));
            $foundPlugin = $this->wasFound($directory);
            if ($foundPlugin) {
                $foundSomething = true;
                $foundPlugins->push($directory);
                $this->found->push([
                    'blog_id' => $blogId,
                    'blog_url' => $blogUrl,
                    'plugin_name' => $foundPlugins,
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
        $html .= self::TABLE_TAG;
        $html .= $this->buildColumnGroup();
        $html .= $this->buildHeader();
        $found->each(function ($item) use (&$html) {
            $url = $item['blog_url'];
            $html .= '   <tr style="background-color: ' . $this->setRowColor($this->foundCount) . ';">';
            $html .= '      <td class="align-top first-cell">';
            $html .= $item['blog_id'];
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= '<a href="' . $url . '" target="_blank">' . $url . '</a><br>';
            $html .= '      </td>';
            $html .= '      <td class="align-top">';
            $html .= $this->highlight($item['plugin_name']->implode(', '));
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
        echo 'No text specified. Syntax: ' . URL::to('/in_plugin') . '?text=';
    }
}
