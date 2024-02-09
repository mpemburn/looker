<?php

namespace App\Services;

use App\Models\SavedSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Ticketpark\HtmlPhpExcel\HtmlPhpExcel;

class ExcelService
{
    public function buildExcel(): JsonResponse
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
            File::delete(public_path() . '/' . $filename);

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
