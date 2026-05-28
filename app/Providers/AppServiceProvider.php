<?php

namespace App\Providers;

use App\Data\DataCapsule;
use App\Support\Languages;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;
use App\Services\AI\AIAutomationHeartbeat;
use App\Services\Filesystem\RoundRobin\RoundRobinService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RoundRobinService::class, function () {
            return new RoundRobinService();
        });

        $this->app->singleton(DataCapsule::class, function () {
            return new DataCapsule();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        bcscale(2);

        View::composer('*', function($view) {
            $view->with('localeName', (new Languages())->getLocaleName());
            $path = storage_path('frontend/build.num');
            $buildNumber = null;

            if (is_file($path)) {
                $value = @file_get_contents($path);
                if ($value !== false && trim($value) !== '') {
                    $buildNumber = trim($value);
                }
            }

            if (! $buildNumber) {
                $buildNumber = (string) (env('VITE_JS_APP_BUILD_NUMBER') ?: '1');
            }

            $view->with('buildNumber', $buildNumber);
        });
        
        LogViewer::auth(function() {
            return auth_check() && me()->isAdmin();
        });

        // Part 7: Real User Interaction Observers
        \App\Models\Post::observe(\App\Observers\PostObserver::class);
        \App\Models\Comment::observe(\App\Observers\CommentObserver::class);

        // Optional fallback for local development only.
        // IMPORTANT: Running heartbeat on every HTTP request can cause severe timeouts.
        if (
            !$this->app->runningInConsole()
            && (bool) config('agent-creation.heartbeat_on_http', false)
        ) {
            app(AIAutomationHeartbeat::class)->tick();
        }
    }
}
