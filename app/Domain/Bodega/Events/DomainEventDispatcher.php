<?php

namespace App\Domain\Bodega\Events;

/**
 * Dispatcher para Domain Events
 * Implementa el patrón Observer para eventos de dominio
 */
class DomainEventDispatcher
{
    private static array $listeners = [];
    private static array $eventQueue = [];

    /**
     * Registrar un listener para un tipo de evento
     */
    public static function listen(string $eventType, callable $listener): void
    {
        if (!isset(self::$listeners[$eventType])) {
            self::$listeners[$eventType] = [];
        }
        
        self::$listeners[$eventType][] = $listener;
    }

    /**
     * Disparar un evento de dominio
     */
    public static function dispatch(object $event): void
    {
        $eventType = get_class($event);
        
        // Agregar a la cola para procesamiento diferido si es necesario
        self::$eventQueue[] = $event;
        
        // Procesar inmediatamente los listeners registrados
        self::procesarListeners($eventType, $event);
    }

    /**
     * Procesar listeners para un tipo de evento específico
     */
    private static function procesarListeners(string $eventType, object $event): void
    {
        if (!isset(self::$listeners[$eventType])) {
            return;
        }

        foreach (self::$listeners[$eventType] as $listener) {
            try {
                $listener($event);
            } catch (\Exception $e) {
                // Loggear error pero no detener la ejecución
                \Log::error("Error en listener de evento {$eventType}: " . $e->getMessage());
            }
        }
    }

    /**
     * Procesar todos los eventos en la cola
     */
    public static function processQueue(): void
    {
        while (!empty(self::$eventQueue)) {
            $event = array_shift(self::$eventQueue);
            $eventType = get_class($event);
            self::procesarListeners($eventType, $event);
        }
    }

    /**
     * Limpiar todos los listeners
     */
    public static function clearListeners(): void
    {
        self::$listeners = [];
    }

    /**
     * Limpiar la cola de eventos
     */
    public static function clearQueue(): void
    {
        self::$eventQueue = [];
    }

    /**
     * Obtener listeners registrados
     */
    public static function getListeners(): array
    {
        return self::$listeners;
    }

    /**
     * Obtener eventos en cola
     */
    public static function getQueuedEvents(): array
    {
        return self::$eventQueue;
    }
}
