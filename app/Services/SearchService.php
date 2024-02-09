<?php

namespace App\Services;

use App\Events\SearchCompleteEvent;
use App\Facades\Database;
use App\Factories\SearcherFactory;
use App\Models\SavedSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class SearchService
{
    public function processSearch(): ?JsonResponse
    {
        $database = request('database');
        if ($database) {
            $searchType = request('type');
            $searchText = request('text');
            $exact = (bool)request('exact');
            $showRaw = (bool)request('show_raw');

            if ($searchType && $searchText) {
                $searcher = SearcherFactory::build($searchType);
                if (!$searcher) {
                    return response()->json(['error' => 'No Search']);
                }
                $searcher->setDatabase($database);
                $html = $searcher->run($searchText, ['exact' => $exact, 'show_raw' => $showRaw])->render();
                $count = $searcher->getCount();

                // Fire the SearchCompleteEvent to persist to database
                $completed = new SearchCompleteEvent([
                    'database' => $database,
                    'type' => $searchType,
                    'search_text' => $searchText,
                    'results' => $html,
                ]);
                event($completed);

                return response()->json([
                    'html' => $html,
                    'found' => $count,
                    'filename' => $this->makeSearchName($searchType, $searchText)
                ]);
            }
        }

        return response()->json(['error' => 'No Database']);
    }

    public function saveSearch(array $data): void
    {
        Database::setDb(env('DB_DATABASE'));
        SavedSearch::create([
            'database' => $data['database'],
            'type' => $data['type'],
            'search_text' => $data['search_text'],
            'results' => mb_convert_encoding($data['results'], 'UTF-8', 'UTF-8')
        ]);
        Database::setDb($data['database']);
    }

    protected function makeSearchName(string $type, string $searchText): string
    {
        $searchText = str_replace([' ', 'Hidden'], ['_', ''], ucwords($searchText));
        $searchText = strlen($searchText) > 0 ? '_' . $searchText : '';
        $dateTime = Carbon::now()->format('_m-d-Y-H-i-s');

        return ucfirst($type) . '_on' . $searchText . $dateTime;
    }
}
