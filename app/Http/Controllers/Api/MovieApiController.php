<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class MovieApiController extends Controller
{
    /**
     * The TMDB API URL
     */
    protected $apiUrl;
    
    /**
     * The TMDB API key
     */
    protected $apiKey;
    
    /**
     * Constructor to initialize API details
     */
    public function __construct()
    {
        $this->apiUrl = env('TMDB_API_URL', 'https://api.themoviedb.org/3');
        $this->apiKey = env('TMDB_API_KEY');
    }

    /**
     * Get popular movies
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function popular()
    {
        // Cache for 12 hours (43200 seconds)
        $movies = Cache::remember('api_popular_movies', 43200, function () {
            $response = Http::get($this->apiUrl . '/movie/popular', [
                'api_key' => $this->apiKey,
                'page' => 1,
            ]);
            
            if ($response->successful()) {
                return $response->json()['results'];
            }
            
            return [];
        });
        
        return response()->json($movies);
    }

    /**
     * Get top rated movies
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function topRated()
    {
        // Cache for 12 hours (43200 seconds)
        $movies = Cache::remember('api_top_rated_movies', 43200, function () {
            $response = Http::get($this->apiUrl . '/movie/top_rated', [
                'api_key' => $this->apiKey,
                'page' => 1,
            ]);
            
            if ($response->successful()) {
                return $response->json()['results'];
            }
            
            return [];
        });
        
        return response()->json($movies);
    }
}