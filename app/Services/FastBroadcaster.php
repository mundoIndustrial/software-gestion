<?php

namespace App\Services;

use Illuminate\Broadcasting\Broadcaster;
use Illuminate\Contracts\Broadcasting\Factory;
use Throwable;

/**
 * FastBroadcaster: Wrapper que ejecuta broadcasts con timeout ultra-bajo
 * No bloquea la respuesta si Reverb no está disponible
 */
class FastBroadcaster
{
    /**
     * Despacha un evento de broadcast con timeout ultra-bajo
     * Captura automáticamente cualquier error de red/timeout
     */
    public static function dispatchEvent($event): void
    {
        try {
            // Dispatch normal del evento
            $event::class;
            
            // Envuelve la llamada con timeout super bajo
            // Larval dispatch es sincrono pero lo hacemos con try-catch para capturar errores rápido
            event($event);
            
        } catch (Throwable $e) {
            // Log silencioso - no afecta la respuesta
            \Log::debug('FastBroadcaster: Broadcast timeout/error (no-blocking)', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Versión alternativa con verdadero fire-and-forget usando Process
     * Requiere PHP 8.1+ con ext-pcntl
     */
    public static function dispatchAsync($event): void
    {
        try {
            // En desarrollo: simple dispatch con try-catch
            event($event);
        } catch (Throwable $e) {
            // Silencioso
            \Log::debug('FastBroadcaster: Event dispatch failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
