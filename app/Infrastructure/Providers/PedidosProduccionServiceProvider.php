<?php

namespace App\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

// Use Cases (DDD)
use App\Application\Pedidos\UseCases\ListarProduccionPedidosUseCase;
use App\Application\Pedidos\UseCases\ObtenerProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\CrearProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ActualizarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\AnularProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\CambiarEstadoPedidoUseCase;
use App\Application\Pedidos\UseCases\AgregarPrendaAlPedidoUseCase;
use App\Application\Pedidos\UseCases\FiltrarPedidosPorEstadoUseCase;
use App\Application\Pedidos\UseCases\BuscarPedidoPorNumeroUseCase;
use App\Application\Pedidos\UseCases\ObtenerPrendasPedidoUseCase;
use App\Application\Pedidos\UseCases\ActualizarPrendaPedidoUseCase;
use App\Application\Pedidos\UseCases\AgregarPrendaCompletaUseCase;
use App\Application\Pedidos\UseCases\ActualizarPrendaCompletaUseCase;
use App\Application\Pedidos\UseCases\RenderItemCardUseCase;

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

        // Cambiar Estado de Pedido
        $this->app->singleton(CambiarEstadoPedidoUseCase::class, function ($app) {
            return new CambiarEstadoPedidoUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Agregar Prenda al Pedido
        $this->app->singleton(AgregarPrendaAlPedidoUseCase::class, function ($app) {
            return new AgregarPrendaAlPedidoUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Filtrar Pedidos por Estado
        $this->app->singleton(FiltrarPedidosPorEstadoUseCase::class, function ($app) {
            return new FiltrarPedidosPorEstadoUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Buscar Pedido por Número
        $this->app->singleton(BuscarPedidoPorNumeroUseCase::class, function ($app) {
            return new BuscarPedidoPorNumeroUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Obtener Prendas de Pedido
        $this->app->singleton(ObtenerPrendasPedidoUseCase::class, function ($app) {
            return new ObtenerPrendasPedidoUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Actualizar Prenda de Pedido
        $this->app->singleton(ActualizarPrendaPedidoUseCase::class, function ($app) {
            return new ActualizarPrendaPedidoUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Agregar Prenda Completa
        $this->app->singleton(AgregarPrendaCompletaUseCase::class, function ($app) {
            return new AgregarPrendaCompletaUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Actualizar Prenda Completa
        $this->app->singleton(ActualizarPrendaCompletaUseCase::class, function ($app) {
            return new ActualizarPrendaCompletaUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Render Item Card
        $this->app->singleton(RenderItemCardUseCase::class, function ($app) {
            return new RenderItemCardUseCase();
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
