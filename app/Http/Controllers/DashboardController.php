<?php

namespace App\Http\Controllers;

use App\Facades\Database;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $production = Database::getDatabaseList();

        return view('dashboard', ['production' => $production]);
    }
}
