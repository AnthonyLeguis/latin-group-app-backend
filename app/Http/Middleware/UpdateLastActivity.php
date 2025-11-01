<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActivity
{
    /**
     * Handle an incoming request.
     *
     * Actualiza el campo last_activity del usuario autenticado.
     * Usa cache para evitar writes innecesarios a la DB (máximo cada 2 minutos).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $cacheKey = 'user_last_activity_' . $request->user()->id;
            
            // Solo actualizar si no hay una actualización reciente en cache (últimos 2 minutos)
            if (!Cache::has($cacheKey)) {
                $request->user()->update([
                    'last_activity' => now()
                ]);
                
                // Cachear por 2 minutos para evitar writes constantes
                Cache::put($cacheKey, true, now()->addMinutes(2));
            }
        }

        return $next($request);
    }
}

