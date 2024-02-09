<?php

namespace App\Services\Searchers;

use App\Models\Option;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class PluginSearcher extends BlogSearcher
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
        $html .= self::TABLE_TAG_START;
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
            $html .= $this->highlight($item['plugin_name']->implode(', '));
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
        echo 'No text specified. Syntax: ' . URL::to('/in_plugin') . '?text=';
    }
}
