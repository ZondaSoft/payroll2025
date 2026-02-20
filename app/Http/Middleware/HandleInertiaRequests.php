<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'layouts/legajos';        //'guest';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
        ];
    }

    public function rootView(Request $request)
    {
        // Aquí puedes decidir qué plantilla usar según la ruta, usuario, etc.
        // return $request->routeIs('requisition1.*') ? 'services' : 'app';
        // Por ejemplo, si la ruta coincide con "admin.*", usa la plantilla 'admin.blade.php'.
        // De lo contrario, usa la plantilla por defecto 'app.blade.php'.

        //dd(session('active'));
        $active = session('active');

        // Pasar datos a la vista de layout
        view()->share('active', $active);

        if ($request->is('legajos*')) {
            return 'layouts/legajos';
        }
        if ($request->is('bajas*')) {
            return 'layouts/legajos';
        }

        // Liquidaciones
        // dd($request);

        if ($request->is('liquidacion*')) {
            return 'layouts/legajos';
        }
        if ($request->is('liquidacion/conceptos*')) {
            return 'layouts/legajos';
        }
        if ($request->is('liquidacion/conceptos/*')) {
            return 'layouts/legajos';
        }

        // Sicoss
        if ($request->is('sicoss*')) {
            return 'layouts/legajos';
        }

        // LSD
        if ($request->is('lsd*')) {
            return 'layouts/legajos';
        }

        // Layout por defecto

        //return 'layouts/legajos';
        return 'guest';
    }
}
