<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRoleChanges
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     * Limita cambios de roles a 10 por hora por usuario
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        if (!$user) {
            return $next($request);
        }

        // Clave única para rate limiting
        $key = "role-changes:{$user->id}";
        
        // Máximo 10 cambios de roles por hora
        $maxAttempts = 10;
        $decayMinutes = 60;

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);
            
            return response()->json([
                'error' => 'Demasiados cambios de roles. Intenta más tarde.',
                'retry_after' => $retryAfter,
                'message' => "Espera {$retryAfter} segundos antes de intentar de nuevo."
            ], 429);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        return $next($request);
    }
}
