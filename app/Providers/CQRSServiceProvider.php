<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Shared\CQRS\QueryBus;
use App\Domain\Shared\CQRS\CommandBus;

// ===== PEDIDOS DOMAIN (ÚNICO DOMINIO UNIFICADO) =====

// Validators
use App\Domain\Pedidos\Validators\PedidoValidator;
use App\Domain\Pedidos\Validators\PrendaValidator;
use App\Domain\Pedidos\Validators\EstadoValidator;

// Queries
use App\Domain\Pedidos\Queries\ObtenerPedidoQuery;
use App\Domain\Pedidos\Queries\ListarPedidosQuery;
use App\Domain\Pedidos\Queries\FiltrarPedidosPorEstadoQuery;
use App\Domain\Pedidos\Queries\BuscarPedidoPorNumeroQuery;

// Query Handlers
use App\Domain\Pedidos\QueryHandlers\ObtenerPedidoHandler;
use App\Domain\Pedidos\QueryHandlers\ListarPedidosHandler;
use App\Domain\Pedidos\QueryHandlers\FiltrarPedidosPorEstadoHandler;
use App\Domain\Pedidos\QueryHandlers\BuscarPedidoPorNumeroHandler;

// Commands
use App\Domain\Pedidos\Commands\CrearPedidoCommand;
use App\Domain\Pedidos\Commands\CrearPedidoCompletoCommand;
use App\Domain\Pedidos\Commands\AgregarPrendaAlPedidoCommand;
use App\Domain\Pedidos\Commands\ActualizarPedidoCommand;
use App\Domain\Pedidos\Commands\CambiarEstadoPedidoCommand;
use App\Domain\Pedidos\Commands\EliminarPedidoCommand;
use App\Domain\Pedidos\Commands\ActualizarVariantePrendaCommand;

// Command Handlers
use App\Domain\Pedidos\CommandHandlers\CrearPedidoHandler;
use App\Domain\Pedidos\CommandHandlers\CrearPedidoCompletoHandler;
use App\Domain\Pedidos\CommandHandlers\AgregarPrendaAlPedidoHandler;
use App\Domain\Pedidos\CommandHandlers\ActualizarPedidoHandler;
use App\Domain\Pedidos\CommandHandlers\CambiarEstadoPedidoHandler;
use App\Domain\Pedidos\CommandHandlers\EliminarPedidoHandler;
use App\Domain\Pedidos\CommandHandlers\ActualizarVariantePrendaHandler;

// ===== EPP DOMAIN =====

// EPP Queries
use App\Domain\Epp\Queries\BuscarEppQuery;
use App\Domain\Epp\Queries\ObtenerEppPorIdQuery;
use App\Domain\Epp\Queries\ObtenerEppPorCategoriaQuery;
use App\Domain\Epp\Queries\ListarEppActivosQuery;
use App\Domain\Epp\Queries\ListarCategoriasEppQuery;
use App\Domain\Epp\Queries\ObtenerEppDelPedidoQuery;

// EPP Query Handlers
use App\Domain\Epp\QueryHandlers\BuscarEppHandler;
use App\Domain\Epp\QueryHandlers\ObtenerEppPorIdHandler;
use App\Domain\Epp\QueryHandlers\ObtenerEppPorCategoriaHandler;
use App\Domain\Epp\QueryHandlers\ListarEppActivosHandler;
use App\Domain\Epp\QueryHandlers\ListarCategoriasEppHandler;
use App\Domain\Epp\QueryHandlers\ObtenerEppDelPedidoHandler;

// EPP Commands
use App\Domain\Epp\Commands\AgregarEppAlPedidoCommand;
use App\Domain\Epp\Commands\EliminarEppDelPedidoCommand;

// EPP Command Handlers
use App\Domain\Epp\CommandHandlers\AgregarEppAlPedidoHandler;
use App\Domain\Epp\CommandHandlers\EliminarEppDelPedidoHandler;

// ===== APPLICATION LAYER =====
use App\Application\Commands\CrearEppCommand;
use App\Application\Handlers\CrearEppHandler;

/**
 * CQRSServiceProvider
 * 
 * Provider unificado para CQRS con dominio Pedidos único
 * 
 * Responsabilidades:
 * - Registrar QueryBus y CommandBus como singletons
 * - Registrar todos los Query/Command Handlers del dominio Pedidos
 * - Registrar Validators
 * - Configurar service locator pattern para resolución de handlers
 * 
 * Patrón: Service Provider (Laravel) + CQRS
 * SRP: Solo gestiona inyección de dependencias para CQRS
 */
class CQRSServiceProvider extends ServiceProvider
{
    /**
     * Register services in the container
     */
    public function register(): void
    {
        // Registrar Validators
        $this->registerValidators();

        // Registrar QueryBus como singleton con handlers pre-registrados
        $this->app->singleton(QueryBus::class, function ($app) {
            $bus = new QueryBus($app);
            $this->registerQueries($bus);
            return $bus;
        });

        // Registrar CommandBus como singleton con handlers pre-registrados
        $this->app->singleton(CommandBus::class, function ($app) {
            $bus = new CommandBus($app);
            $this->registerCommands($bus);
            return $bus;
        });

        // Aliases para compatibilidad
        $this->app->alias(QueryBus::class, 'query.bus');
        $this->app->alias(CommandBus::class, 'command.bus');

        // Registrar Query Handlers
        $this->registerQueryHandlers();

        // Registrar Command Handlers
        $this->registerCommandHandlers();
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Los handlers se registran dentro de las factory closures del singleton.
        // boot() ya no necesita hacer nada.
    }

    /**
     * Registrar Validators
     */
    private function registerValidators(): void
    {
        $this->app->singleton(PedidoValidator::class, function ($app) {
            return new PedidoValidator();
        });

        $this->app->singleton(PrendaValidator::class, function ($app) {
            return new PrendaValidator();
        });

        $this->app->singleton(EstadoValidator::class, function ($app) {
            return new EstadoValidator();
        });
    }

    /**
     * Registrar Query Handlers
     */
    private function registerQueryHandlers(): void
    {
        // ===== PEDIDOS QUERY HANDLERS =====
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

        // ===== EPP QUERY HANDLERS =====
        $this->app->bind(BuscarEppHandler::class, function ($app) {
            return new BuscarEppHandler($app->make(\App\Domain\Epp\Services\EppDomainService::class));
        });

        $this->app->bind(ObtenerEppPorIdHandler::class, function ($app) {
            return new ObtenerEppPorIdHandler($app->make(\App\Domain\Epp\Services\EppDomainService::class));
        });

        $this->app->bind(ObtenerEppPorCategoriaHandler::class, function ($app) {
            return new ObtenerEppPorCategoriaHandler($app->make(\App\Domain\Epp\Services\EppDomainService::class));
        });

        $this->app->bind(ListarEppActivosHandler::class, function ($app) {
            return new ListarEppActivosHandler($app->make(\App\Domain\Epp\Services\EppDomainService::class));
        });

        $this->app->bind(ListarCategoriasEppHandler::class, function ($app) {
            return new ListarCategoriasEppHandler($app->make(\App\Domain\Epp\Services\EppDomainService::class));
        });

        $this->app->bind(ObtenerEppDelPedidoHandler::class, function ($app) {
            return new ObtenerEppDelPedidoHandler($app->make(\App\Domain\Epp\Repositories\PedidoEppRepositoryInterface::class));
        });
    }

    /**
     * Registrar Command Handlers
     */
    private function registerCommandHandlers(): void
    {
        // ===== PEDIDOS COMMAND HANDLERS =====
        
        // CrearPedidoHandler
        $this->app->bind(CrearPedidoHandler::class, function ($app) {
            return new CrearPedidoHandler(
                $app->make(\App\Models\PedidoProduccion::class),
                $app->make(\App\Domain\Shared\DomainEventDispatcher::class),
                $app->make(PedidoValidator::class),
            );
        });

        // CrearPedidoCompletoHandler - Orquestador principal
        $this->app->bind(CrearPedidoCompletoHandler::class, function ($app) {
            return new CrearPedidoCompletoHandler(
                $app->make(CommandBus::class),
                $app->make(\App\Models\PedidoProduccion::class),
            );
        });

        // AgregarPrendaAlPedidoHandler
        $this->app->bind(AgregarPrendaAlPedidoHandler::class, function ($app) {
            return new AgregarPrendaAlPedidoHandler(
                $app->make(\App\Models\PedidoProduccion::class),
                $app->make(\App\Application\Services\PedidoPrendaService::class),
                $app->make(PrendaValidator::class)
            );
        });

        // ActualizarPedidoHandler
        $this->app->bind(ActualizarPedidoHandler::class, function ($app) {
            return new ActualizarPedidoHandler(
                $app->make(\App\Models\PedidoProduccion::class),
                $app->make(PedidoValidator::class),
            );
        });

        // CambiarEstadoPedidoHandler
        $this->app->bind(CambiarEstadoPedidoHandler::class, function ($app) {
            return new CambiarEstadoPedidoHandler(
                $app->make(\App\Models\PedidoProduccion::class),
                $app->make(EstadoValidator::class)
            );
        });

        // EliminarPedidoHandler
        $this->app->bind(EliminarPedidoHandler::class, function ($app) {
            return new EliminarPedidoHandler(
                $app->make(\App\Models\PedidoProduccion::class)
            );
        });

        // ActualizarVariantePrendaHandler - Actualiza variantes con merge de datos
        $this->app->bind(ActualizarVariantePrendaHandler::class, function ($app) {
            return new ActualizarVariantePrendaHandler();
        });

        // ===== EPP COMMAND HANDLERS =====
        $this->app->bind(AgregarEppAlPedidoHandler::class, function ($app) {
            return new AgregarEppAlPedidoHandler(
                $app->make(\App\Domain\Epp\Repositories\PedidoEppRepositoryInterface::class)
            );
        });

        $this->app->bind(EliminarEppDelPedidoHandler::class, function ($app) {
            return new EliminarEppDelPedidoHandler(
                $app->make(\App\Domain\Epp\Repositories\PedidoEppRepositoryInterface::class)
            );
        });

        // ===== APPLICATION HANDLERS =====
        $this->app->bind(CrearEppHandler::class, function ($app) {
            return new CrearEppHandler();
        });
    }

    /**
     * Registrar Queries en el QueryBus
     */
    private function registerQueries(QueryBus $queryBus): void
    {
        // ===== PEDIDOS QUERIES =====
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

        // ===== EPP QUERIES =====
        $queryBus->register(
            BuscarEppQuery::class,
            BuscarEppHandler::class
        );

        $queryBus->register(
            ObtenerEppPorIdQuery::class,
            ObtenerEppPorIdHandler::class
        );

        $queryBus->register(
            ObtenerEppPorCategoriaQuery::class,
            ObtenerEppPorCategoriaHandler::class
        );

        $queryBus->register(
            ListarEppActivosQuery::class,
            ListarEppActivosHandler::class
        );

        $queryBus->register(
            ListarCategoriasEppQuery::class,
            ListarCategoriasEppHandler::class
        );

        $queryBus->register(
            ObtenerEppDelPedidoQuery::class,
            ObtenerEppDelPedidoHandler::class
        );
    }

    /**
     * Registrar Commands en el CommandBus
     */
    private function registerCommands(CommandBus $commandBus): void
    {
        // ===== PEDIDOS COMMANDS =====
        
        // CrearPedidoCommand - Comando base para crear pedido
        $commandBus->register(
            CrearPedidoCommand::class,
            CrearPedidoHandler::class
        );

        // CrearPedidoCompletoCommand - Orquestador principal
        $commandBus->register(
            CrearPedidoCompletoCommand::class,
            CrearPedidoCompletoHandler::class
        );

        // AgregarPrendaAlPedidoCommand
        $commandBus->register(
            AgregarPrendaAlPedidoCommand::class,
            AgregarPrendaAlPedidoHandler::class
        );

        // ActualizarPedidoCommand
        $commandBus->register(
            ActualizarPedidoCommand::class,
            ActualizarPedidoHandler::class
        );

        // CambiarEstadoPedidoCommand
        $commandBus->register(
            CambiarEstadoPedidoCommand::class,
            CambiarEstadoPedidoHandler::class
        );

        // EliminarPedidoCommand
        $commandBus->register(
            EliminarPedidoCommand::class,
            EliminarPedidoHandler::class
        );

        // ActualizarVariantePrendaCommand - Actualización con merge de datos
        $commandBus->register(
            ActualizarVariantePrendaCommand::class,
            ActualizarVariantePrendaHandler::class
        );

        // ===== EPP COMMANDS =====
        $commandBus->register(
            AgregarEppAlPedidoCommand::class,
            AgregarEppAlPedidoHandler::class
        );

        $commandBus->register(
            EliminarEppDelPedidoCommand::class,
            EliminarEppDelPedidoHandler::class
        );

        // ===== APPLICATION COMMANDS =====
        $commandBus->register(
            CrearEppCommand::class,
            CrearEppHandler::class
        );
    }
}
