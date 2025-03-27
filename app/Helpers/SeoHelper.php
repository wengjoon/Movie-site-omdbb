<?php

namespace App\Helpers;

class SeoHelper
{
    /**
     * Get page title for the current page
     * 
     * @param string|null $title
     * @param bool $withSuffix
     * @return string
     */
    public static function title($title = null, $withSuffix = true)
    {
        $config = config('seo');
        
        if (empty($title)) {
            return $config['default_title'];
        }
        
        return $title . ($withSuffix ? $config['title_suffix'] : '');
    }
    
    /**
     * Get movie page title
     * 
     * @param array $movie
     * @return string
     */
    public static function movieTitle($movie)
    {
        $title = $movie['title'] ?? 'Movie';
        return sprintf(config('seo.movie_title'), $title);
    }
    
    /**
     * Get movie meta description
     * 
     * @param array $movie
     * @return string
     */
    public static function movieDescription($movie)
    {
        $title = $movie['title'] ?? 'Movie';
        $year = isset($movie['release_date']) ? substr($movie['release_date'], 0, 4) : '';
        $overview = isset($movie['overview']) ? self::truncate($movie['overview'], 150) : '';
        
        return sprintf(config('seo.movie_description'), $title, $year, $overview);
    }
    
    /**
     * Get search page title
     * 
     * @param string $query
     * @return string
     */
    public static function searchTitle($query)
    {
        return sprintf(config('seo.search_title'), $query) . config('seo.title_suffix');
    }
    
    /**
     * Get meta description
     * 
     * @param string|null $description
     * @return string
     */
    public static function description($description = null)
    {
        return $description ?? config('seo.default_description');
    }
    
    /**
     * Get meta keywords
     * 
     * @param string|null $keywords
     * @return string
     */
    public static function keywords($keywords = null)
    {
        return $keywords ?? config('seo.default_keywords');
    }
    
    /**
     * Get Open Graph image URL
     * 
     * @param string|null $image
     * @return string
     */
    public static function ogImage($image = null)
    {
        return asset($image ?? config('seo.default_og_image'));
    }
    
    /**
     * Get Twitter image URL
     * 
     * @param string|null $image
     * @return string
     */
    public static function twitterImage($image = null)
    {
        return asset($image ?? config('seo.default_twitter_image'));
    }
    
    /**
     * Generate JSON-LD schema markup for the homepage
     * 
     * @return string
     */
    public static function homeSchema()
    {
        $config = config('seo');
        
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $config['site_name'],
            'url' => url('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/search') . '?query={search_term_string}',
                'query-input' => 'required name=search_term_string'
            ],
            'description' => $config['structured_data']['website']['description']
        ];
        
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    
    /**
     * Generate JSON-LD schema markup for a movie
     * 
     * @param array $movie
     * @return string
     */
    public static function movieSchema($movie)
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Movie',
            'name' => $movie['title'] ?? '',
            'description' => $movie['overview'] ?? ('Watch ' . ($movie['title'] ?? 'this movie') . ' online for free in HD quality on ' . config('seo.site_name') . '.'),
        ];
        
        // Add poster image if available
        if (!empty($movie['poster_path'])) {
            $data['image'] = 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'];
        }
        
        // Add release date if available
        if (!empty($movie['release_date'])) {
            $data['datePublished'] = $movie['release_date'];
        }
        
        // Add rating if available
        if (isset($movie['vote_average']) && isset($movie['vote_count'])) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $movie['vote_average'],
                'bestRating' => '10',
                'worstRating' => '0',
                'ratingCount' => $movie['vote_count']
            ];
        }
        
        // Add directors if available
        if (!empty($movie['directors'])) {
            $data['director'] = [];
            foreach ($movie['directors'] as $director) {
                $data['director'][] = [
                    '@type' => 'Person',
                    'name' => $director
                ];
            }
        }
        
        // Add cast if available
        if (!empty($movie['top_cast'])) {
            $data['actor'] = [];
            foreach ($movie['top_cast'] as $actor) {
                $data['actor'][] = [
                    '@type' => 'Person',
                    'name' => $actor
                ];
            }
        }
        
        // Add reviews if available
        if (!empty($movie['reviews'])) {
            $data['review'] = [];
            foreach ($movie['reviews'] as $review) {
                $reviewData = [
                    '@type' => 'Review',
                    'reviewBody' => $review['content'] ?? '',
                    'datePublished' => $review['created_at'] ?? date('Y-m-d')
                ];
                
                if (!empty($review['author'])) {
                    $reviewData['author'] = [
                        '@type' => 'Person',
                        'name' => $review['author']
                    ];
                }
                
                if (!empty($review['rating'])) {
                    $reviewData['reviewRating'] = [
                        '@type' => 'Rating',
                        'ratingValue' => $review['rating'],
                        'bestRating' => '10',
                        'worstRating' => '0'
                    ];
                }
                
                $data['review'][] = $reviewData;
            }
        }
        
        // Add watch action
        $data['potentialAction'] = [
            '@type' => 'WatchAction',
            'target' => url()->current()
        ];
        
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    
    /**
     * Generate JSON-LD schema markup for FAQs
     * 
     * @param array $faqs
     * @return string
     */
    public static function faqSchema($faqs)
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => []
        ];
        
        foreach ($faqs as $faq) {
            $data['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                ]
            ];
        }
        
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    
    /**
     * Truncate a string to a specified length
     * 
     * @param string $text
     * @param int $length
     * @param string $append
     * @return string
     */
    private static function truncate($text, $length = 160, $append = '...')
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        
        return $text . $append;
    }
}