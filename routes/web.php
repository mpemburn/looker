<?php

use App\Factories\SearcherFactory;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Facades\Database;
use App\Models\WpOption;
use App\Models\WpUser;
use App\Services\Searchers\UsersSearcher;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
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

Route::get('/db', function () {
    collect(
        ['www_clarku','news_clarku','sites_clarku','wordpress_clarku']
    )->each(function ($database) {
        Database::setDb($database);

        $blogs = \DB::connection()->table('wp_blogs')->get();

        !d($blogs->count());
    });
});

Route::get('/dev', function () {
    // Do what thou wilt
});

Route::get('/remote', function () {
    app('config')->write('database.is_remote', false);
    echo config('database.is_remote');
    die();
    Database::setDb('www_clarku');
    $users = (new UsersSearcher())->run('cdonofrio')->render();

    echo $users;
    // Do what thou wilt
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/search', [SearchController::class, 'index'])->name('search');
});

Route::get('/get_list', [SearchController::class, 'getList'])->name('get_list');
Route::post('/do_search', [SearchController::class, 'search'])->name('do_search');

require __DIR__.'/auth.php';
