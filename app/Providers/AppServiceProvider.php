<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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

        // Versión visible en el footer = hash del commit git desplegado, en formato xxxx.xxxx.xxxx.
        // Sirve para confirmar de un vistazo qué versión está corriendo en producción.
        View::share('appVersion', $this->resolverVersionApp());
    }

    /**
     * Devuelve los primeros 12 caracteres del commit HEAD como 'xxxx.xxxx.xxxx'.
     * Lee directo de .git (sin exec). Si no hay repo/git, devuelve 'n/d'.
     */
    private function resolverVersionApp(): string
    {
        try {
            $gitDir = base_path('.git');
            $head = @file_get_contents($gitDir . '/HEAD');
            if ($head === false) {
                return 'n/d';
            }
            $head = trim($head);
            $hash = null;

            if (str_starts_with($head, 'ref:')) {
                $ref = trim(substr($head, 4));
                $refFile = $gitDir . '/' . $ref;
                if (is_file($refFile)) {
                    $hash = trim((string) @file_get_contents($refFile));
                } else {
                    // ref empaquetado (git gc)
                    $packed = (string) @file_get_contents($gitDir . '/packed-refs');
                    if ($packed !== '' && preg_match('/^([0-9a-f]{40})\s+' . preg_quote($ref, '/') . '$/m', $packed, $m)) {
                        $hash = $m[1];
                    }
                }
            } elseif (preg_match('/^[0-9a-f]{40}$/', $head)) {
                $hash = $head; // HEAD desacoplado
            }

            if ($hash && strlen($hash) >= 12) {
                $h = substr($hash, 0, 12);
                return substr($h, 0, 4) . '.' . substr($h, 4, 4) . '.' . substr($h, 8, 4);
            }
        } catch (\Throwable $e) {
            // no interrumpir el render por un problema leyendo .git
        }

        return 'n/d';
    }
}
