<?php

namespace App\Http\Controllers;
use App\Factories\SearcherFactory;
use App\Services\BlogService;
use Illuminate\Http\Request;
use App\Facades\Database;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $source = request('source');
        $useList = $source === 'test' ? 'INSTALLED_TEST_DATABASES' : 'INSTALLED_DATABASES';

        $databases = Database::getDatabaseList($useList);

        return view('search', ['databases' => $databases]);
    }

    public function search(Request $request)
    {
        $database = request('database');
        if (! $database) {
            return response()->json(['error' => 'No Database']);;
        }

        Database::setDb($database);

        $searchType = request('type');
        $searchText = request('text');
        $exact = (bool)request('exact');

        if ($searchType && $searchText) {
            $searcher = SearcherFactory::build($searchType);
            if (! $searcher) {
                return response()->json(['error' => 'No Search']);;
            }
            $html = $searcher->run($searchText, $exact)->render();
            $count = $searcher->getCount();

            return response()->json(['html' => $html, 'found' => $count]);
        }

        return response()->json(['error' => 'Nothing']);
    }

    public function getList(Request $request)
    {
        $database = request('database');
        $type = request('type');

        if (! $database) {
            return response()->json(['error' => 'No Database']);;
        }

        if ($type === 'plugins') {
            return response()->json(['type' => $type, 'plugins' => $this->getPluginList($database)]);
        }

        if ($type === 'themes') {
            return response()->json(['type' => $type, 'themes' => $this->getThemeList($database)]);
        }
    }

    protected function getPluginList(string $database): array
    {
        return (new BlogService())->getPluginList($database);
    }

    protected function getThemeList(string $database): array
    {
        return (new BlogService())->getThemeList($database);
    }
}
