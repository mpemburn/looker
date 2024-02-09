<?php

namespace App\Http\Controllers;

use App\Factories\ListFactory;
use App\Factories\SearcherFactory;
use App\Models\SavedSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Facades\Database;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Ticketpark\HtmlPhpExcel\HtmlPhpExcel;

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

                return response()->json([
                    'html' => $html,
                    'found' => $count,
                    'filename' => $this->makeSearchName($searchType, $searchText)
                ]);
            }
        }

        return response()->json(['error' => 'No Database']);
    }

    protected function makeSearchName(string $type, string $searchText): string
    {
        $searchText = str_replace([' ', 'Hidden'], ['_', ''], ucwords($searchText));
        $searchText = strlen($searchText) > 0 ? '_' . $searchText : '';
        $dateTime = Carbon::now()->format('_m-d-Y-H-i-s');

        return ucfirst($type) . '_on' . $searchText . $dateTime;
    }

    public function getList(Request $request): ?JsonResponse
    {
        $database = request('database');
        $type = request('type');

        if (!$database) {
            return response()->json(['error' => 'No Database']);;
        }

        return ListFactory::build($type, $database);
    }

    public function getExcel(): ?JsonResponse
    {
        $database = request('database');
        $type = request('type');
        $search = request('search');
        $filename = request('filename') . '.xlsx';

        $saved = SavedSearch::where('database', $database)
            ->where('type', $type)
            ->where('search_text', $search)
            ->orderBy('created_at', 'desc')
            ->first();

        try {
            $htmlPhpExcel = new HtmlPhpExcel($saved->results);
            $htmlPhpExcel->process()->save($filename);
            $excel = File::get(public_path() . '/' . $filename);

            return response()->json([
                'filename' => $filename,
                'data' => base64_encode($excel),
                'mime_type' => 'application/vnd.ms-excel'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ]);
        }
    }
}
