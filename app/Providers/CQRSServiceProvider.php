<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Shared\CQRS\QueryBus;
use App\Domain\Shared\CQRS\CommandBus;
use App\Domain\PedidoProduccion\Validators\PedidoValidator;
use App\Domain\PedidoProduccion\Validators\EstadoValidator;
use App\Domain\PedidoProduccion\Validators\PrendaValidator;

// Query Handlers
use App\Domain\PedidoProduccion\QueryHandlers\ObtenerPedidoHandler;
use App\Domain\PedidoProduccion\QueryHandlers\ListarPedidosHandler;
use App\Domain\PedidoProduccion\QueryHandlers\FiltrarPedidosPorEstadoHandler;
use App\Domain\PedidoProduccion\QueryHandlers\BuscarPedidoPorNumeroHandler;
use App\Domain\PedidoProduccion\QueryHandlers\ObtenerPrendasPorPedidoHandler;

// Command Handlers
use App\Domain\PedidoProduccion\CommandHandlers\CrearPedidoHandler;
use App\Domain\PedidoProduccion\CommandHandlers\ActualizarPedidoHandler;
use App\Domain\PedidoProduccion\CommandHandlers\CambiarEstadoPedidoHandler;
use App\Domain\PedidoProduccion\CommandHandlers\AgregarPrendaAlPedidoHandler;
use App\Domain\PedidoProduccion\CommandHandlers\EliminarPedidoHandler;

// Query Classes
use App\Domain\PedidoProduccion\Queries\ObtenerPedidoQuery;
use App\Domain\PedidoProduccion\Queries\ListarPedidosQuery;
use App\Domain\PedidoProduccion\Queries\FiltrarPedidosPorEstadoQuery;
use App\Domain\PedidoProduccion\Queries\BuscarPedidoPorNumeroQuery;
use App\Domain\PedidoProduccion\Queries\ObtenerPrendasPorPedidoQuery;

// Command Classes
use App\Domain\PedidoProduccion\Commands\CrearPedidoCommand;
use App\Domain\PedidoProduccion\Commands\ActualizarPedidoCommand;
use App\Domain\PedidoProduccion\Commands\CambiarEstadoPedidoCommand;
use App\Domain\PedidoProduccion\Commands\AgregarPrendaAlPedidoCommand;
use App\Domain\PedidoProduccion\Commands\EliminarPedidoCommand;

/**
 * CQRSServiceProvider
 * 
 * Responsabilidad:
 * - Registrar QueryBus y CommandBus como singletons
 * - Registrar todos los Query/Command Handlers
 * - Registrar todos los Validators
 * - Configurar el service locator pattern para resolución de handlers
 * 
 * Patrón: Service Provider (Laravel)
 * SRP: Solo gestiona inyección de dependencias para CQRS
 */
class CQRSServiceProvider extends ServiceProvider
{
    /**
     * Register services in the container
     */
    public function register(): void
    {
        // Registrar Validators como singletons
        $this->registerValidators();

        // Registrar QueryBus como singleton
        $this->app->singleton(QueryBus::class, function ($app) {
            return new QueryBus($app);
        });

        // Registrar CommandBus como singleton
        $this->app->singleton(CommandBus::class, function ($app) {
            return new CommandBus($app);
        });

        // Registrar Query Handlers
        $this->registerQueryHandlers();

        // Registrar Command Handlers
        $this->registerCommandHandlers();
    }

    /**
     * Bootstrap services
     */
    public function boot(QueryBus $queryBus, CommandBus $commandBus): void
    {
        // Registrar Queries
        $this->registerQueries($queryBus);

        // Registrar Commands
        $this->registerCommands($commandBus);

        \Illuminate\Support\Facades\Log::info('✅ [CQRSServiceProvider] CQRS providers registrados');
    }

    /**
     * Registrar Validators
     */
    private function registerValidators(): void
    {
        $this->app->singleton(PedidoValidator::class, function ($app) {
            return new PedidoValidator();
        });

        $this->app->singleton(EstadoValidator::class, function ($app) {
            return new EstadoValidator();
        });

        $this->app->singleton(PrendaValidator::class, function ($app) {
            return new PrendaValidator();
        });
    }

    /**
     * Registrar Query Handlers
     */
    private function registerQueryHandlers(): void
    {
        $this->app->bind(ObtenerPedidoHandler::class, function ($app) {
            return new ObtenerPedidoHandler($app->make(\App\Models\PedidoProduccion::class));
        });

        $this->app->bind(ListarPedidosHandler::class, function ($app) {
            return new ListarPedidosHandler($app->make(\App\Models\PedidoProduccion::class));
        });

        $this->app->bind(FiltrarPedidosPorEstadoHandler::class, function ($app) {
            return new FiltrarPedidosPorEstadoHandler($app->make(\App\Models\PedidoProduccion::class));
        });

        $this->app->bind(BuscarPedidoPorNumeroHandler::class, function ($app) {
            return new BuscarPedidoPorNumeroHandler($app->make(\App\Models\PedidoProduccion::class));
        });

        $this->app->bind(ObtenerPrendasPorPedidoHandler::class, function ($app) {
            return new ObtenerPrendasPorPedidoHandler($app->make(\App\Models\PedidoProduccion::class));
        });
    }

    /**
     * Registrar Command Handlers
     */
    private function registerCommandHandlers(): void
    {
        $this->app->bind(CrearPedidoHandler::class, function ($app) {
            return new CrearPedidoHandler(
                $app->make(\App\Models\PedidoProduccion::class),
                $app->make(\App\Domain\Shared\DomainEventDispatcher::class),
                $app->make(PedidoValidator::class),
            );
        });

        $this->app->bind(ActualizarPedidoHandler::class, function ($app) {
            return new ActualizarPedidoHandler(
                $app->make(\App\Models\PedidoProduccion::class),
                $app->make(PedidoValidator::class),
            );
        });

        $this->app->bind(CambiarEstadoPedidoHandler::class, function ($app) {
            return new CambiarEstadoPedidoHandler(
                $app->make(\App\Models\PedidoProduccion::class),
                $app->make(EstadoValidator::class),
            );
        });

        $this->app->bind(AgregarPrendaAlPedidoHandler::class, function ($app) {
            return new AgregarPrendaAlPedidoHandler(
                $app->make(\App\Models\PedidoProduccion::class),
                $app->make(\App\Domain\PedidoProduccion\Services\PrendaCreationService::class),
                $app->make(PrendaValidator::class),
            );
        });

        $this->app->bind(EliminarPedidoHandler::class, function ($app) {
            return new EliminarPedidoHandler($app->make(\App\Models\PedidoProduccion::class));
        });
    }

    /**
     * Registrar Queries en el QueryBus
     */
    private function registerQueries(QueryBus $queryBus): void
    {
        $queryBus->register(
            ObtenerPedidoQuery::class,
            ObtenerPedidoHandler::class
        );

        $queryBus->register(
            ListarPedidosQuery::class,
            ListarPedidosHandler::class
        );

        $queryBus->register(
            FiltrarPedidosPorEstadoQuery::class,
            FiltrarPedidosPorEstadoHandler::class
        );

        $queryBus->register(
            BuscarPedidoPorNumeroQuery::class,
            BuscarPedidoPorNumeroHandler::class
        );

        $queryBus->register(
            ObtenerPrendasPorPedidoQuery::class,
            ObtenerPrendasPorPedidoHandler::class
        );
    }

    /**
     * Registrar Commands en el CommandBus
     */
    private function registerCommands(CommandBus $commandBus): void
    {
        $commandBus->register(
            CrearPedidoCommand::class,
            CrearPedidoHandler::class
        );

        $commandBus->register(
            ActualizarPedidoCommand::class,
            ActualizarPedidoHandler::class
        );

        $commandBus->register(
            CambiarEstadoPedidoCommand::class,
            CambiarEstadoPedidoHandler::class
        );

        $commandBus->register(
            AgregarPrendaAlPedidoCommand::class,
            AgregarPrendaAlPedidoHandler::class
        );

        $commandBus->register(
            EliminarPedidoCommand::class,
            EliminarPedidoHandler::class
        );
    }
}
