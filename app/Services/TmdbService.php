<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TmdbService
{
    protected $apiUrl;
    protected $apiKey;
    
    public function __construct()
    {
        $this->apiUrl = env('TMDB_API_URL', 'https://api.themoviedb.org/3');
        $this->apiKey = env('TMDB_API_KEY');
    }
    
    public function searchMovies($query, $page = 1)
    {
        return Http::get($this->apiUrl . '/search/movie', [
            'api_key' => $this->apiKey,
            'query' => $query,
            'page' => $page,
        ])->json();
    }
    
    public function getMovieDetails($movieId)
    {
        return Http::get($this->apiUrl . '/movie/' . $movieId, [
            'api_key' => $this->apiKey,
            'append_to_response' => 'credits,videos',
        ])->json();
    }
}