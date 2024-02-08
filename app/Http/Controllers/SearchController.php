<?php

namespace App\Http\Controllers;
use App\Factories\ListFactory;
use App\Factories\SearcherFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Facades\Database;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $source = request('source') ?? 'remote';
        Config::set('database.is_remote', $source === 'remote');

        $databases = Database::getDatabaseList($source);

        return view('search', ['databases' => $databases]);
    }

    public function search(Request $request): JsonResponse
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

    public function getList(Request $request): ?JsonResponse
    {
        $database = request('database');
        $type = request('type');

        Log::debug($type);
        if (! $database) {
            return response()->json(['error' => 'No Database']);;
        }

        return ListFactory::build($type, $database);
    }

}
