<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarConfiguracionInicial
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rutasExcluidas = [
            'configuracion-inicial*',
            'estilos/*',
            'storage/*',
            'build/*',
            'favicon.ico',
        ];

        foreach ($rutasExcluidas as $patron) {
            if ($request->is($patron)) {
                return $next($request);
            }
        }

        $hayUsuarios = User::where('estado', true)->exists();
        $hayEmpresas = Empresa::where('estado', true)->exists();

        if (! $hayUsuarios || ! $hayEmpresas) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El sistema requiere configuración inicial.',
                    'redirect' => route('setup.index'),
                ], 403);
            }

            return redirect()->route('setup.index');
        }

        return $next($request);
    }
}
