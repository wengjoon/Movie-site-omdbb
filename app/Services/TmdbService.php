<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TmdbService
{
    /**
     * Base TMDB API URL
     */
    protected $apiUrl;
    
    /**
     * API Keys configuration
     */
    protected $apiKeys = [];
    
    /**
     * Proxies configuration indexed by API key
     */
    protected $proxies = [];
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiUrl = env('TMDB_API_URL', 'https://api.themoviedb.org/3');
        $this->loadConfiguration();
    }
    
    /**
     * Load API key and proxy configuration
     */
    protected function loadConfiguration()
    {
        // API keys from .env (comma separated)
        $keysStr = env('TMDB_API_KEYS', env('TMDB_API_KEY', ''));
        $this->apiKeys = array_map('trim', explode(',', $keysStr));
        
        // Proxies from .env (comma separated)
        $proxiesStr = env('TMDB_API_PROXIES', '');
        $proxyList = array_map('trim', explode(',', $proxiesStr));
        
        // Skip first key (direct connection)
        for ($i = 1; $i < count($this->apiKeys); $i++) {
            if (isset($proxyList[$i-1])) {
                $this->proxies[$this->apiKeys[$i]] = $proxyList[$i-1];
            }
        }
    }
    
    /**
     * Get the appropriate API key using round-robin
     */
    public function getApiKey()
    {
        // Implement rotation based on usage
        $keyIndex = Cache::increment('tmdb_api_key_index', 1) % count($this->apiKeys);
        Cache::put('tmdb_api_key_index', $keyIndex, now()->addDay());
        
        return $this->apiKeys[$keyIndex];
    }
    
    /**
     * Get the proxy for a specific API key
     */
    protected function getProxyForKey($apiKey)
    {
        return $this->proxies[$apiKey] ?? null;
    }
    
    /**
     * Make a request to TMDB API with automatic key rotation and proxy
     */
    public function makeRequest($endpoint, $params = [], $method = 'GET')
    {
        // Create a cache key from the request
        $cacheKey = 'tmdb_' . md5($endpoint . json_encode($params) . $method);
        
        // Check cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        // Get appropriate API key
        $apiKey = $this->getApiKey();
        
        // Add API key to params
        $params['api_key'] = $apiKey;
        
        // Get proxy for this key if available
        $proxy = $this->getProxyForKey($apiKey);
        
        // Create request instance
        $request = Http::timeout(10); // 10 second timeout
        
        // Add proxy if available
        if ($proxy) {
            $request = $request->withOptions([
                'proxy' => $proxy
            ]);
        }
        
        // Throttle requests with a 250ms delay
        $this->throttleRequests();
        
        try {
            if ($method === 'GET') {
                $response = $request->get($this->apiUrl . $endpoint, $params);
            } else {
                $response = $request->post($this->apiUrl . $endpoint, $params);
            }
            
            // If successful, cache and return
            if ($response->successful()) {
                $data = $response->json();
                Cache::put($cacheKey, $data, 604800); // 1 week
                return $data;
            } else {
                // Log the error
                Log::error('TMDB API Error', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                ]);
                
                // Return empty result
                return [];
            }
        } catch (\Exception $e) {
            // Log the exception
            Log::error('TMDB API Exception: ' . $e->getMessage());
            
            // Return empty result
            return [];
        }
    }
    
    /**
     * Throttle requests to prevent overloading the API
     */
    protected function throttleRequests()
    {
        // Get the timestamp of the last API call
        $lastCallTimestamp = Cache::get('tmdb_last_api_call', 0);
        $now = microtime(true);
        
        // If the last call was made less than 250ms ago, wait
        if ($now - $lastCallTimestamp < 0.25) {
            $sleepTime = 0.25 - ($now - $lastCallTimestamp);
            usleep($sleepTime * 1000000); // Convert to microseconds
        }
        
        // Update the last call timestamp
        Cache::put('tmdb_last_api_call', microtime(true), 60);
    }
    
    /**
     * Search for movies
     */
    public function searchMovies($query, $page = 1)
    {
        return $this->makeRequest('/search/movie', [
            'query' => $query,
            'page' => $page
        ]);
    }
    
    /**
     * Get movie details
     */
    public function getMovieDetails($movieId)
    {
        $details = $this->makeRequest('/movie/' . $movieId, [
            'append_to_response' => 'credits,videos'
        ]);
        
        // Extract directors and cast for common operations
        $directors = [];
        $cast = [];
        
        if (isset($details['credits']['crew'])) {
            foreach ($details['credits']['crew'] as $crew) {
                if ($crew['job'] === 'Director') {
                    $directors[] = $crew['name'];
                }
            }
        }
        
        if (isset($details['credits']['cast'])) {
            $castCount = min(count($details['credits']['cast'] ?? []), 5);
            for ($i = 0; $i < $castCount; $i++) {
                if (isset($details['credits']['cast'][$i])) {
                    $cast[] = $details['credits']['cast'][$i]['name'];
                }
            }
        }
        
        $details['directors'] = $directors;
        $details['top_cast'] = $cast;
        
        return $details;
    }
    
    /**
     * Get popular movies
     */
    public function getPopularMovies($page = 1)
    {
        return $this->makeRequest('/movie/popular', ['page' => $page]);
    }
    
    /**
     * Get top rated movies
     */
    public function getTopRatedMovies($page = 1)
    {
        return $this->makeRequest('/movie/top_rated', ['page' => $page]);
    }
}