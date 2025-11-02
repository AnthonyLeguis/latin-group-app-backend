<?php

namespace App\Http\Middleware;

use App\Events\AgentActivityUpdated;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActivity
{
    /**
     * Handle an incoming request.
     *
     * Actualiza el campo last_activity del usuario autenticado.
     * Actualiza en cada request para tracking preciso (sin cache).
     * Dispara evento de broadcasting cada 5 segundos para reducir carga.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->type === 'agent') {
            $user = $request->user();
            
            // Actualizar directamente sin cache para detección inmediata
            $user->update(['last_activity' => now()]);

            // Disparar evento solo si han pasado 5 segundos desde el último broadcast
            // Esto reduce la carga del servidor mientras mantiene actualización casi en tiempo real
            $cacheKey = "agent_broadcast_{$user->id}";
            
            if (!Cache::has($cacheKey)) {
                // Calcular estadísticas
                $stats = $this->getStats();

                try {
                    // Disparar evento
                    broadcast(new AgentActivityUpdated($stats));
                } catch (\Throwable $e) {
                    Log::error('❌ Error al transmitir evento (middleware):', [
                        'user_id' => $user->id,
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                // Cachear por 5 segundos
                Cache::put($cacheKey, true, 5);
            }
        }

        return $next($request);
    }

    /**
     * Obtener estadísticas de agentes para broadcasting
     */
    private function getStats(): array
    {
        $onlineThreshold = now()->subMinutes(1);
        
        return [
            'online_agents' => User::where('type', 'agent')
                ->where('last_activity', '>=', $onlineThreshold)
                ->count(),
            'total_agents' => User::where('type', 'agent')->count(),
            'timestamp' => now()->toIso8601String()
        ];
    }
}

