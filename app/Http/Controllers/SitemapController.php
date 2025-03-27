<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SitemapController extends Controller
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
     * Generate and return the sitemap XML
     * 
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Cache the sitemap for 24 hours (86400 seconds)
        $content = Cache::remember('sitemap', 86400, function () {
            return $this->generateSitemap();
        });
        
        return response($content, 200)
            ->header('Content-Type', 'text/xml');
    }
    
    /**
     * Generate sitemap.xml content
     * 
     * @return string
     */
    protected function generateSitemap()
    {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        
        // Add home page
        $sitemap .= $this->createUrlEntry(
            route('movies.index'),
            Carbon::now()->toW3cString(),
            'daily',
            '1.0'
        );
        
        // Add popular movies
        $popularMovies = $this->getPopularMovies();
        foreach ($popularMovies as $movie) {
            $sitemap .= $this->createUrlEntry(
                route('movies.show', ['id' => $movie['id']]),
                isset($movie['release_date']) ? Carbon::parse($movie['release_date'])->toW3cString() : Carbon::now()->toW3cString(),
                'weekly',
                '0.8'
            );
        }
        
        // Add top rated movies
        $topRatedMovies = $this->getTopRatedMovies();
        foreach ($topRatedMovies as $movie) {
            // Skip if already added from popular movies
            if (!$this->isMovieInList($movie, $popularMovies)) {
                $sitemap .= $this->createUrlEntry(
                    route('movies.show', ['id' => $movie['id']]),
                    isset($movie['release_date']) ? Carbon::parse($movie['release_date'])->toW3cString() : Carbon::now()->toW3cString(),
                    'weekly',
                    '0.8'
                );
            }
        }
        
        // Note: We're not adding any search pages or search queries to the sitemap
        
        $sitemap .= '</urlset>';
        
        return $sitemap;
    }
    
    /**
     * Create a URL entry for the sitemap
     * 
     * @param string $loc
     * @param string $lastmod
     * @param string $changefreq
     * @param string $priority
     * @return string
     */
    protected function createUrlEntry($loc, $lastmod, $changefreq, $priority)
    {
        return "\t<url>\n" .
               "\t\t<loc>" . htmlspecialchars($loc) . "</loc>\n" .
               "\t\t<lastmod>" . $lastmod . "</lastmod>\n" .
               "\t\t<changefreq>" . $changefreq . "</changefreq>\n" .
               "\t\t<priority>" . $priority . "</priority>\n" .
               "\t</url>\n";
    }
    
    /**
     * Get popular movies from TMDB API
     * 
     * @param int $limit
     * @return array
     */
    protected function getPopularMovies($limit = 100)
    {
        $movies = [];
        $page = 1;
        $maxPages = 5; // Limit to 5 pages (100 movies)
        
        while (count($movies) < $limit && $page <= $maxPages) {
            $response = Http::get($this->apiUrl . '/movie/popular', [
                'api_key' => $this->apiKey,
                'page' => $page,
            ]);
            
            if ($response->successful()) {
                $results = $response->json()['results'];
                foreach ($results as $movie) {
                    // Include all movies, including TV Movies
                    $movies[] = $movie;
                    if (count($movies) >= $limit) {
                        break;
                    }
                }
            } else {
                // Break on API error
                break;
            }
            
            $page++;
        }
        
        return $movies;
    }
    
    /**
     * Get top rated movies from TMDB API
     * 
     * @param int $limit
     * @return array
     */
    protected function getTopRatedMovies($limit = 50)
    {
        $movies = [];
        $page = 1;
        $maxPages = 3; // Limit to 3 pages (60 movies)
        
        while (count($movies) < $limit && $page <= $maxPages) {
            $response = Http::get($this->apiUrl . '/movie/top_rated', [
                'api_key' => $this->apiKey,
                'page' => $page,
            ]);
            
            if ($response->successful()) {
                $results = $response->json()['results'];
                foreach ($results as $movie) {
                    // Include all movies, including TV Movies
                    $movies[] = $movie;
                    if (count($movies) >= $limit) {
                        break;
                    }
                }
            } else {
                // Break on API error
                break;
            }
            
            $page++;
        }
        
        return $movies;
    }
    
    /**
     * Check if a movie is already in a list
     * 
     * @param array $movie
     * @param array $list
     * @return bool
     */
    protected function isMovieInList($movie, $list)
    {
        foreach ($list as $item) {
            if ($item['id'] === $movie['id']) {
                return true;
            }
        }
        
        return false;
    }
}