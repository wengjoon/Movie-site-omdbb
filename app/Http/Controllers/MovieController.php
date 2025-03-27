<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MovieController extends Controller
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
     * Display the search homepage with top rated movies
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Cache top rated movies for one week (10080 minutes)
        $topRatedMovies = Cache::remember('top_rated_movies', 10080, function () {
            $movies = [];
            
            // Get top rated movies (we'll get 8 from the first page)
            $response = Http::get($this->apiUrl . '/movie/top_rated', [
                'api_key' => $this->apiKey,
                'page' => 1,
            ]);
            
            if ($response->successful()) {
                $results = $response->json()['results'];
                
                // Process first 8 movies to get director information
                $count = 0;
                foreach ($results as $movie) {
                    if ($count >= 8) break; // Only get 8 movies
                    
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
                }
            }
            
            return $movies;
        });

        // Split movies into rows (4 movies per row)
        $movieRows = array_chunk($topRatedMovies, 4);
        
        return view('movies.index', [
            'movieRows' => $movieRows
        ]);
    }

    /**
     * Search for movies via the TMDB API
     */
    protected function searchMovies($query, $page = 1)
    {
        return Http::get($this->apiUrl . '/search/movie', [
            'api_key' => $this->apiKey,
            'query' => $query,
            'page' => $page,
        ])->json();
    }
    
    /**
     * Get detailed information for a specific movie
     */
    protected function getMovieDetails($movieId)
{
    $response = Http::get($this->apiUrl . '/movie/' . $movieId, [
        'api_key' => $this->apiKey,
        'append_to_response' => 'credits,videos',
    ]);
    
    // Check if the request was successful
    if (!$response->successful()) {
        // Return an error structure similar to TMDB API errors
        return ['success' => false, 'status_code' => $response->status(), 'status_message' => 'Movie not found'];
    }
    
    return $response->json();
}

    /**
     * Search for movies and display results (OPTIMIZED VERSION)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        $page = $request->input('page', 1);

        if (empty($query)) {
            return redirect()->route('movies.index');
        }

        // Use a different cache key for the optimized search
        $cacheKey = 'movie_search_optimized_' . md5($query . '_' . $page);
        
        $results = Cache::remember($cacheKey, 3600, function () use ($query, $page) {
            // Only make one API call to get search results
            $searchResults = $this->searchMovies($query, $page);
            
            // Instead of fetching details for each movie, just prepare the results
            // We'll add a placeholder for directors
            $processedResults = [];
            foreach ($searchResults['results'] as $movie) {
                // Add an empty directors array to maintain compatibility with the view
                $movie['directors'] = [];
                $processedResults[] = $movie;
            }
            
            return [
                'results' => $processedResults,
                'total_pages' => $searchResults['total_pages'],
                'current_page' => $searchResults['page'],
                'query' => $query,
            ];
        });

        return view('movies.search', $results);
    }

    /**
     * Display the detailed information for a specific movie
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    /**
 * Display the detailed information for a specific movie
 *
 * @param  int  $id
 * @return \Illuminate\View\View
 */
public function show($id)
{
    $cacheKey = 'movie_details_' . $id;
    
    try {
        $movie = Cache::remember($cacheKey, 3600, function () use ($id) {
            // Get movie details from TMDB
            $details = $this->getMovieDetails($id);
            
            // Immediately check if we got a valid response with a title
            if (!isset($details['title'])) {
                throw new \Exception('Movie not found');
            }
            
            // Extract directors
            $directors = [];
            if (isset($details['credits']['crew'])) {
                foreach ($details['credits']['crew'] as $crew) {
                    if ($crew['job'] === 'Director') {
                        $directors[] = $crew['name'];
                    }
                }
            }
            
            // Extract cast (top 5)
            $cast = [];
            if (isset($details['credits']['cast'])) {
                $castCount = min(count($details['credits']['cast']), 5);
                for ($i = 0; $i < $castCount; $i++) {
                    $cast[] = $details['credits']['cast'][$i]['name'];
                }
            }
            
            $details['directors'] = $directors;
            $details['top_cast'] = $cast;
            
            return $details;
        });
        
        return view('movies.show', ['movie' => $movie]);
    } catch (\Exception $e) {
        // Log the error
        \Log::error('Movie not found: ' . $id . ' - ' . $e->getMessage());
        
        // Force a 404 response
        abort(404);
    }
}}