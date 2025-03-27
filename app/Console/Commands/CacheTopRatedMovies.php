<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CacheTopRatedMovies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movies:cache-top-rated';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache top rated movies from TMDB API';

    /**
     * The TMDB API URL
     */
    protected $apiUrl;
    
    /**
     * The TMDB API key
     */
    protected $apiKey;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to cache top rated movies...');
        
        $this->apiUrl = env('TMDB_API_URL', 'https://api.themoviedb.org/3');
        $this->apiKey = env('TMDB_API_KEY');
        
        if (empty($this->apiKey)) {
            $this->error('TMDB API key not found. Please set TMDB_API_KEY in your .env file.');
            return 1;
        }
        
        $movies = [];
        $this->info('Fetching top rated movies from TMDB...');
        
        // Get top rated movies from TMDB
        $response = Http::get($this->apiUrl . '/movie/top_rated', [
            'api_key' => $this->apiKey,
            'page' => 1,
        ]);
        
        if ($response->successful()) {
            $results = $response->json()['results'];
            
            // Process first 8 movies to get director information
            $this->output->progressStart(8);
            $count = 0;
            
            foreach ($results as $movie) {
                if ($count >= 8) break; // Only process first 8 movies
                
                $details = $this->getMovieDetails($movie['id']);
                
                // Extract directors
                $directors = [];
                if (isset($details['credits']['crew'])) {
                    foreach ($details['credits']['crew'] as $crew) {
                        if ($crew['job'] === 'Director') {
                            $directors[] = $crew['name'];
                        }
                    }
                }
                
                $movie['directors'] = $directors;
                $movies[] = $movie;
                $count++;
                
                $this->output->progressAdvance();
            }
            
            $this->output->progressFinish();
        } else {
            $this->error("Failed to fetch top rated movies.");
            $this->error($response->body());
            return 1;
        }
        
        // Store in cache for one week
        Cache::put('top_rated_movies', $movies, now()->addMinutes(10080));
        
        $this->info('Successfully cached ' . count($movies) . ' top rated movies for one week.');
        
        return 0;
    }
    
    /**
     * Get detailed information for a specific movie
     */
    protected function getMovieDetails($movieId)
    {
        return Http::get($this->apiUrl . '/movie/' . $movieId, [
            'api_key' => $this->apiKey,
            'append_to_response' => 'credits,videos',
        ])->json();
    }
}