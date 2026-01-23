<?php

namespace App\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

// Use Cases (DDD)
use App\Application\Pedidos\UseCases\ListarProduccionPedidosUseCase;
use App\Application\Pedidos\UseCases\ObtenerProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\CrearProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ActualizarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\AnularProduccionPedidoUseCase;

// Repositories
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use App\Domain\Shared\CQRS\QueryBus;
use App\Domain\Shared\CQRS\CommandBus;

/**
 * PedidosProduccionServiceProvider
 * 
 * Registra todos los servicios y dependencias para PedidosProduccionController
 * 
 * Responsabilidades:
 * - Inyectar Use Cases en el controlador
 * - Centralizar configuración de dependencias
 * 
 * Beneficios:
 * - Constructor limpio
 * - Fácil testing (inyección clara)
 * - Fácil mantenimiento (cambios centralizados)
 */
class PedidosProduccionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // ===== USE CASES (DDD) =====

        // Listar Pedidos
        $this->app->singleton(ListarProduccionPedidosUseCase::class, function ($app) {
            return new ListarProduccionPedidosUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Obtener Pedido
        $this->app->singleton(ObtenerProduccionPedidoUseCase::class, function ($app) {
            return new ObtenerProduccionPedidoUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Crear Pedido
        $this->app->singleton(CrearProduccionPedidoUseCase::class, function ($app) {
            return new CrearProduccionPedidoUseCase();
        });

        // Actualizar Pedido
        $this->app->singleton(ActualizarProduccionPedidoUseCase::class, function ($app) {
            return new ActualizarProduccionPedidoUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Anular Pedido
        $this->app->singleton(AnularProduccionPedidoUseCase::class, function ($app) {
            return new AnularProduccionPedidoUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Aquí irían eventos, listeners, etc.
    }
}
