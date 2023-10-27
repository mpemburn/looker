<?php

use App\Factories\SearcherFactory;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Facades\Database;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dev', function () {
    Database::setDb('www_clarku');
    $searcher = SearcherFactory::build('list_all');
    $html = $searcher->run('foo')->render();
    echo $html;

});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/search', [SearchController::class, 'index'])->name('search');
});

Route::post('/do_search', [SearchController::class, 'search'])->name('do_search');

require __DIR__.'/auth.php';
