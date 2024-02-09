<?php

namespace App\Console\Commands;

use App\Facades\Curl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TestUrlsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $linksTxt = Storage::path('www2_no_redirect.txt');
        $links = explode("\n", file_get_contents($linksTxt));

        collect($links)->each(function ($link) {
            $code = Curl::getReturnCode($link);
            if (! str_contains($link, 'facultybio') || $code !== '200') {
                return;
            }
            echo "<a href='{$link}' target='_blank'>{$link}</a>" . PHP_EOL;
        });
    }
}
