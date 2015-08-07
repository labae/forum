<?php namespace Boxkode\Forum;

use Event;
use Boxkode\Forum\Events\ThreadWasViewed;
use Illuminate\Support\ServiceProvider;

class ForumServiceProvider extends ServiceProvider {

    /**
    * Register the service provider.
    *
    * @return void
    */
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__.'/../../config/integration.php', 'forum.integration');
        $this->mergeConfigFrom(__DIR__.'/../../config/permissions.php', 'forum.permissions');
        $this->mergeConfigFrom(__DIR__.'/../../config/preferences.php', 'forum.preferences');
        $this->mergeConfigFrom(__DIR__.'/../../config/routing.php', 'forum.routing');
    }

    /**
    * Bootstrap the application events.
    *
    * @return void
    */
    public function boot()
    {
        // Publish controller, config, views and migrations
        $this->publishes([
            __DIR__.'/Controllers/ForumController.php' => base_path('app/Http/Controllers/ForumController.php')
        ], 'controller');

        $this->publishes([
            __DIR__.'/../../config/integration.php' => config_path('forum.integration.php'),
            __DIR__.'/../../config/permissions.php' => config_path('forum.permissions.php'),
            __DIR__.'/../../config/preferences.php' => config_path('forum.preferences.php'),
            __DIR__.'/../../config/routing.php' => config_path('forum.routing.php')
        ], 'config');

        $this->publishes([
            __DIR__.'/../../views/' => base_path('/resources/views/vendor/forum')
        ], 'views');
        // Load views
        $this->loadViewsFrom(__DIR__.'/../../views', 'forum');
        
        $this->publishes([
            __DIR__.'/../../migrations/' => base_path('/database/migrations')
        ], 'migrations');

        // Enable routing
        if (config('forum.routing.enabled')) {
            $controller = config('forum.integration.controller');

            include __DIR__.'/../../routes.php';
        }

        // Subscribe event Handlers
        Event::subscribe('Boxkode\Forum\Handlers\Events\IncrementThreadViewCount');
    }

}
