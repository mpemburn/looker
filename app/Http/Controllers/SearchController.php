<?php

namespace App\Http\Controllers;

use App\Factories\ListFactory;
use App\Models\SavedSearch;
use App\Services\ExcelService;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use App\Facades\Database;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Ticketpark\HtmlPhpExcel\HtmlPhpExcel;

class SearchController extends Controller
{
    public function index()
    {
        $source = request('source') ?? 'remote';
        Config::set('database.is_remote', $source === 'remote');

        $databases = Database::getDatabaseList($source);

        return view('search', ['databases' => $databases]);
    }

    public function search(SearchService $service): JsonResponse
    {
        return $service->processSearch();
    }

    public function getList(): ?JsonResponse
    {
        $database = request('database');
        $type = request('type');

        if (!$database) {
            return response()->json(['error' => 'No Database']);;
        }

        return ListFactory::build($type, $database);
    }

    public function getExcel(ExcelService $service): ?JsonResponse
    {
        return $service->buildExcel();
    }
}
