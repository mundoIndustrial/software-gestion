<?php

namespace App\Providers;

use App\Domain\B\Repositories\PedidoRepositoryInterface;
use App\Infrastructure\Bodega\Persistence\EloquentPedidoRepository;
use App\Application\Bodega\UseCases\EntregarPedidoUseCase;
use App\Application\Bodega\UseCases\ListarPedidosPorAreaUseCase;
use App\Domain\Bodega\Services\PedidoFilterService;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider para inyección de dependencias DDD de Bodega
 * Registra todos los componentes de la arquitectura DDD
 */
class BodegaDDDServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Repository Pattern - Bind interface a implementación
        $this->app->bind(
            PedidoRepositoryInterface::class,
            EloquentPedidoRepository::class
        );

        // Domain Services
        $this->app->singleton(PedidoFilterService::class);

        // Use Cases
        $this->app->singleton(EntregarPedidoUseCase::class, function ($app) {
            return new EntregarPedidoUseCase(
                $app->make(PedidoRepositoryInterface::class)
            );
        });

        $this->app->singleton(ListarPedidosPorAreaUseCase::class, function ($app) {
            return new ListarPedidosPorAreaUseCase(
                $app->make(PedidoRepositoryInterface::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Registrar listeners para Domain Events
        $this->registrarEventListeners();
    }

    /**
     * Registrar listeners para eventos de dominio
     */
    private function registrarEventListeners(): void
    {
        // Listener para PedidoEntregado
        \App\Domain\Bodega\Events\DomainEventDispatcher::listen(
            \App\Domain\Bodega\Events\PedidoEntregado::class,
            function ($event) {
                \Log::info("Pedido entregado: " . $event->getNumeroPedido());
                
                // Aquí podríamos:
                // - Enviar notificaciones
                // - Actualizar estadísticas
                // - Enviar emails
                // - Actualizar inventario
            }
        );

        // Listener para PedidoActualizado
        \App\Domain\Bodega\Events\DomainEventDispatcher::listen(
            \App\Domain\Bodega\Events\PedidoActualizado::class,
            function ($event) {
                if ($event->esTransicionImportante()) {
                    \Log::info("Transición importante: " . $event);
                    
                    // Aquí podríamos:
                    // - Enviar notificaciones de cambios importantes
                    // - Actualizar dashboards en tiempo real
                    // - Disparar workflows automáticos
                }
            }
        );
    }
}
