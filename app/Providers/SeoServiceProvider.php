<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Helpers\SeoHelper;

class SeoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/seo.php', 'seo'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/seo.php' => config_path('seo.php'),
        ], 'config');
        
        // Register blade directives
        Blade::directive('seoTitle', function ($expression) {
            return "<?php echo App\\Helpers\\SeoHelper::title($expression); ?>";
        });
        
        Blade::directive('seoDescription', function ($expression) {
            return "<?php echo App\\Helpers\\SeoHelper::description($expression); ?>";
        });
        
        Blade::directive('seoKeywords', function ($expression) {
            return "<?php echo App\\Helpers\\SeoHelper::keywords($expression); ?>";
        });
        
        Blade::directive('seoHomeSchema', function () {
            return "<?php echo App\\Helpers\\SeoHelper::homeSchema(); ?>";
        });
        
        Blade::directive('seoMovieSchema', function ($expression) {
            return "<?php echo App\\Helpers\\SeoHelper::movieSchema($expression); ?>";
        });
        
        Blade::directive('seoFaqSchema', function ($expression) {
            return "<?php echo App\\Helpers\\SeoHelper::faqSchema($expression); ?>";
        });
    }
}