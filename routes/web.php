<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Api\MovieApiController;
use App\Http\Middleware\SearchRateLimiter; // Add this import

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

Route::get('/', [MovieController::class, 'index'])->name('movies.index');
Route::get('/search', [MovieController::class, 'search'])
    ->middleware(SearchRateLimiter::class) // Use class directly instead of alias
    ->name('movies.search');
Route::get('/movie/{id}', [MovieController::class, 'show'])->name('movies.show');

// Sitemap route
Route::get('/sitemap.xml', [SitemapController::class, 'index']);

// If this API route exists
if (method_exists(MovieApiController::class, 'search')) {
    Route::get('/api/movies/search', [MovieApiController::class, 'search'])
        ->middleware(SearchRateLimiter::class); // Use class directly
}