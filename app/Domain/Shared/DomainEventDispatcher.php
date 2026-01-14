<?php

namespace App\Domain\Shared;

use Illuminate\Events\Dispatcher;

/**
 * Event Dispatcher para eventos de dominio
 * 
 * Responsabilidades:
 * - Registrar listeners para eventos
 * - Disparar eventos cuando ocurren
 * - Manejar listeners síncronos y asíncronos
 * 
 * Implementa el patrón Observer para desacoplar productores y consumidores de eventos
 */
class DomainEventDispatcher
{
    /**
     * Listeners registrados por tipo de evento
     * 
     * @var array<string, array>
     */
    private array $listeners = [];

    /**
     * Cola de eventos pendientes de procesar
     * 
     * @var array<DomainEvent>
     */
    private array $pendingEvents = [];

    /**
     * Instancia del dispatcher de Laravel (opcional, para integración)
     */
    private ?Dispatcher $laravelDispatcher = null;

    public function __construct(?Dispatcher $laravelDispatcher = null)
    {
        $this->laravelDispatcher = $laravelDispatcher;
    }

    /**
     * Registrar un listener para un tipo de evento
     * 
     * @param class-string<DomainEvent> $eventClass Clase del evento
     * @param callable $listener Función a ejecutar cuando ocurra el evento
     * @param bool $async Si debe ejecutarse asincronamente
     */
    public function subscribe(string $eventClass, callable $listener, bool $async = false): void
    {
        if (!isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }

        $this->listeners[$eventClass][] = [
            'listener' => $listener,
            'async' => $async,
        ];
    }

    /**
     * Disparar (publicar) un evento de dominio
     * 
     * @param DomainEvent $event Evento a disparar
     */
    public function dispatch(DomainEvent $event): void
    {
        $eventClass = get_class($event);

        // Agregar a cola de eventos pendientes
        $this->pendingEvents[] = $event;

        // Si hay listeners registrados, ejecutarlos
        if (isset($this->listeners[$eventClass])) {
            foreach ($this->listeners[$eventClass] as $listenerConfig) {
                $listener = $listenerConfig['listener'];
                $async = $listenerConfig['async'];

                if ($async && function_exists('dispatch')) {
                    // Si es asincrónico y estamos en Laravel, usar queue
                    dispatch(function () use ($listener, $event) {
                        $listener($event);
                    });
                } else {
                    // Ejecución sincrónica
                    $listener($event);
                }
            }
        }

        // Si hay dispatcher de Laravel, también disparar allá para logging global
        if ($this->laravelDispatcher) {
            $this->laravelDispatcher->dispatch($event);
        }
    }

    /**
     * Obtener todos los eventos pendientes
     * 
     * @return array<DomainEvent>
     */
    public function getPendingEvents(): array
    {
        return $this->pendingEvents;
    }

    /**
     * Limpiar eventos pendientes (después de persistir)
     */
    public function clearPendingEvents(): void
    {
        $this->pendingEvents = [];
    }

    /**
     * Obtener eventos pendientes y limpiar
     * Útil para persistencia en Base de Datos
     * 
     * @return array<DomainEvent>
     */
    public function pullPendingEvents(): array
    {
        $events = $this->pendingEvents;
        $this->clearPendingEvents();
        return $events;
    }

    /**
     * Limpiar listeners registrados (útil para testing)
     */
    public function clearListeners(): void
    {
        $this->listeners = [];
    }

    /**
     * Obtener cantidad de listeners para un evento
     */
    public function getListenerCount(string $eventClass): int
    {
        return count($this->listeners[$eventClass] ?? []);
    }
}
