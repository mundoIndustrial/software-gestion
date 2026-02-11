<?php

namespace App\Providers;

use App\Application\Bodega\CQRS\CQRSManager;
use App\Application\Bodega\CQRS\Commands\CommandBus;
use App\Application\Bodega\CQRS\Queries\QueryBus;
use App\Application\Bodega\CQRS\Handlers\Commands\EntregarPedidoHandler;
use App\Application\Bodega\CQRS\Handlers\Commands\ActualizarEstadoPedidoHandler;
use App\Application\Bodega\CQRS\Handlers\Queries\ObtenerPedidosPorAreaHandler;
use App\Application\Bodega\CQRS\Handlers\Queries\ObtenerEstadisticasPedidosHandler;
use App\Application\Bodega\CQRS\Commands\EntregarPedidoCommand;
use App\Application\Bodega\CQRS\Commands\ActualizarEstadoPedidoCommand;
use App\Application\Bodega\CQRS\Queries\ObtenerPedidosPorAreaQuery;
use App\Application\Bodega\CQRS\Queries\ObtenerEstadisticasPedidosQuery;
use App\Domain\Bodega\Repositories\PedidoRepositoryInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider para CQRS de Bodega
 * Configura Command Bus, Query Bus y todos sus handlers
 */
class BodegaCQRSServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar Command Bus y Query Bus
        $this->app->singleton(CommandBus::class);
        $this->app->singleton(QueryBus::class, function ($app) {
            return new QueryBus(config('cqrs.cache_ttl', 300)); // 5 minutos por defecto
        });

        // Registrar CQRS Manager
        $this->app->singleton(CQRSManager::class, function ($app) {
            return new CQRSManager(
                $app->make(CommandBus::class),
                $app->make(QueryBus::class)
            );
        });

        // Registrar Handlers de Commands
        $this->app->singleton(EntregarPedidoHandler::class, function ($app) {
            return new EntregarPedidoHandler(
                $app->make(PedidoRepositoryInterface::class)
            );
        });

        $this->app->singleton(ActualizarEstadoPedidoHandler::class, function ($app) {
            return new ActualizarEstadoPedidoHandler(
                $app->make(PedidoRepositoryInterface::class)
            );
        });

        // Registrar Handlers de Queries
        $this->app->singleton(ObtenerPedidosPorAreaHandler::class, function ($app) {
            return new ObtenerPedidosPorAreaHandler(
                $app->make(PedidoRepositoryInterface::class)
            );
        });

        $this->app->singleton(ObtenerEstadisticasPedidosHandler::class, function ($app) {
            return new ObtenerEstadisticasPedidosHandler(
                $app->make(PedidoRepositoryInterface::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Registrar handlers en los buses
        $this->registrarCommandHandlers();
        $this->registrarQueryHandlers();

        // Publicar configuración si es necesario
        $this->publishes([
            __DIR__.'/../config/cqrs.php' => config_path('cqrs.php'),
        ], 'cqrs-config');
    }

    /**
     * Registrar handlers de Commands
     */
    private function registrarCommandHandlers(): void
    {
        $commandBus = $this->app->make(CommandBus::class);

        // Registrar EntregarPedidoCommand
        $commandBus->register(
            EntregarPedidoCommand::class,
            [$this->app->make(EntregarPedidoHandler::class), 'handle']
        );

        // Registrar ActualizarEstadoPedidoCommand
        $commandBus->register(
            ActualizarEstadoPedidoCommand::class,
            [$this->app->make(ActualizarEstadoPedidoHandler::class), 'handle']
        );
    }

    /**
     * Registrar handlers de Queries
     */
    private function registrarQueryHandlers(): void
    {
        $queryBus = $this->app->make(QueryBus::class);

        // Registrar ObtenerPedidosPorAreaQuery
        $queryBus->register(
            ObtenerPedidosPorAreaQuery::class,
            [$this->app->make(ObtenerPedidosPorAreaHandler::class), 'handle']
        );

        // Registrar ObtenerEstadisticasPedidosQuery
        $queryBus->register(
            ObtenerEstadisticasPedidosQuery::class,
            [$this->app->make(ObtenerEstadisticasPedidosHandler::class), 'handle']
        );
    }
}
