<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Forzar la URL raíz a la configurada en APP_URL (útil cuando la app está en un subdirectorio)
        $root = config('app.url');
        
        if ($root) {
            URL::forceRootUrl($root);
            // Forzar esquema si está explícito en APP_URL (http/https)
            $scheme = parse_url($root, PHP_URL_SCHEME);
            if ($scheme) {
                URL::forceScheme($scheme);
            }
        }
    }
}
