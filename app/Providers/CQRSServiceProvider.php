<?php

namespace App\Providers;

use App\Application\Commands\CrearEppCommand;
use App\Application\Handlers\CrearEppHandler;
use App\Application\Pedidos\CommandHandlers\ActualizarPedidoHandler;
use App\Application\Pedidos\CommandHandlers\ActualizarVariantePrendaHandler;
use App\Application\Pedidos\CommandHandlers\AgregarPrendaAlPedidoHandler;
use App\Application\Pedidos\CommandHandlers\CambiarEstadoPedidoHandler;
use App\Application\Pedidos\CommandHandlers\CrearPedidoCompletoHandler;
use App\Application\Pedidos\CommandHandlers\CrearPedidoHandler;
use App\Application\Pedidos\CommandHandlers\EliminarPedidoHandler;
use App\Application\Pedidos\QueryHandlers\BuscarPedidoPorNumeroHandler;
use App\Application\Pedidos\QueryHandlers\FiltrarPedidosPorEstadoHandler;
use App\Application\Pedidos\QueryHandlers\ListarPedidosHandler;
use App\Application\Pedidos\QueryHandlers\ObtenerPedidoHandler;
use App\Application\Services\PedidoPrendaService;
use App\Domain\Epp\CommandHandlers\AgregarEppAlPedidoHandler;
use App\Domain\Epp\CommandHandlers\EliminarEppDelPedidoHandler;
use App\Domain\Epp\Commands\AgregarEppAlPedidoCommand;
use App\Domain\Epp\Commands\EliminarEppDelPedidoCommand;
use App\Domain\Epp\QueryHandlers\BuscarEppHandler;
use App\Domain\Epp\QueryHandlers\ListarCategoriasEppHandler;
use App\Domain\Epp\QueryHandlers\ListarEppActivosHandler;
use App\Domain\Epp\QueryHandlers\ObtenerEppDelPedidoHandler;
use App\Domain\Epp\QueryHandlers\ObtenerEppPorCategoriaHandler;
use App\Domain\Epp\QueryHandlers\ObtenerEppPorIdHandler;
use App\Domain\Epp\Queries\BuscarEppQuery;
use App\Domain\Epp\Queries\ListarCategoriasEppQuery;
use App\Domain\Epp\Queries\ListarEppActivosQuery;
use App\Domain\Epp\Queries\ObtenerEppDelPedidoQuery;
use App\Domain\Epp\Queries\ObtenerEppPorCategoriaQuery;
use App\Domain\Epp\Queries\ObtenerEppPorIdQuery;
use App\Domain\Epp\Repositories\PedidoEppRepositoryInterface;
use App\Domain\Epp\Services\EppDomainService;
use App\Domain\Pedidos\Commands\ActualizarPedidoCommand;
use App\Domain\Pedidos\Commands\ActualizarVariantePrendaCommand;
use App\Domain\Pedidos\Commands\AgregarPrendaAlPedidoCommand;
use App\Domain\Pedidos\Commands\CambiarEstadoPedidoCommand;
use App\Domain\Pedidos\Commands\CrearPedidoCommand;
use App\Domain\Pedidos\Commands\CrearPedidoCompletoCommand;
use App\Domain\Pedidos\Commands\EliminarPedidoCommand;
use App\Domain\Pedidos\Queries\BuscarPedidoPorNumeroQuery;
use App\Domain\Pedidos\Queries\FiltrarPedidosPorEstadoQuery;
use App\Domain\Pedidos\Queries\ListarPedidosQuery;
use App\Domain\Pedidos\Queries\ObtenerPedidoQuery;
use App\Domain\Pedidos\Validators\EstadoValidator;
use App\Application\Pedidos\Validators\PedidoValidator;
use App\Domain\Pedidos\Validators\PrendaValidator;
use App\Domain\Shared\CQRS\CommandBus;
use App\Domain\Shared\CQRS\QueryBus;
use App\Domain\Shared\DomainEventDispatcher;
use App\Models\PedidoProduccion;
use Illuminate\Support\ServiceProvider;

class CQRSServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerValidators();

        $this->app->singleton(QueryBus::class, function ($app) {
            $bus = new QueryBus($app);
            $this->registerQueries($bus);
            return $bus;
        });

        $this->app->singleton(CommandBus::class, function ($app) {
            $bus = new CommandBus($app);
            $this->registerCommands($bus);
            return $bus;
        });

        $this->app->alias(QueryBus::class, 'query.bus');
        $this->app->alias(CommandBus::class, 'command.bus');

        $this->registerQueryHandlers();
        $this->registerCommandHandlers();
    }

    public function boot(): void
    {
        // No-op.
    }

    private function registerValidators(): void
    {
        $this->app->singleton(PedidoValidator::class, fn ($app) => new PedidoValidator(
            $app->make(\App\Domain\Pedidos\Validators\PedidoValidatorContract::class)
        ));
        $this->app->singleton(PrendaValidator::class, fn () => new PrendaValidator());
        $this->app->singleton(EstadoValidator::class, fn () => new EstadoValidator());
    }

    private function registerQueryHandlers(): void
    {
        $this->registerPedidosQueryHandlers();
        $this->registerEppQueryHandlers();
    }

    private function registerPedidosQueryHandlers(): void
    {
        $this->app->bind(ObtenerPedidoHandler::class, fn ($app) => new ObtenerPedidoHandler($app->make(PedidoProduccion::class)));
        $this->app->bind(ListarPedidosHandler::class, fn ($app) => new ListarPedidosHandler($app->make(PedidoProduccion::class)));
        $this->app->bind(FiltrarPedidosPorEstadoHandler::class, fn ($app) => new FiltrarPedidosPorEstadoHandler($app->make(PedidoProduccion::class)));
        $this->app->bind(BuscarPedidoPorNumeroHandler::class, fn ($app) => new BuscarPedidoPorNumeroHandler($app->make(PedidoProduccion::class)));
    }

    private function registerEppQueryHandlers(): void
    {
        $this->app->bind(BuscarEppHandler::class, fn ($app) => new BuscarEppHandler($app->make(EppDomainService::class)));
        $this->app->bind(ObtenerEppPorIdHandler::class, fn ($app) => new ObtenerEppPorIdHandler($app->make(EppDomainService::class)));
        $this->app->bind(ObtenerEppPorCategoriaHandler::class, fn ($app) => new ObtenerEppPorCategoriaHandler($app->make(EppDomainService::class)));
        $this->app->bind(ListarEppActivosHandler::class, fn ($app) => new ListarEppActivosHandler($app->make(EppDomainService::class)));
        $this->app->bind(ListarCategoriasEppHandler::class, fn ($app) => new ListarCategoriasEppHandler($app->make(EppDomainService::class)));
        $this->app->bind(ObtenerEppDelPedidoHandler::class, fn ($app) => new ObtenerEppDelPedidoHandler($app->make(PedidoEppRepositoryInterface::class)));
    }

    private function registerCommandHandlers(): void
    {
        $this->registerPedidosCommandHandlers();
        $this->registerEppCommandHandlers();
        $this->registerApplicationCommandHandlers();
    }

    private function registerPedidosCommandHandlers(): void
    {
        $this->app->bind(CrearPedidoHandler::class, fn ($app) => new CrearPedidoHandler(
            $app->make(PedidoProduccion::class),
            $app->make(DomainEventDispatcher::class),
            $app->make(PedidoValidator::class),
        ));

        $this->app->bind(CrearPedidoCompletoHandler::class, fn ($app) => new CrearPedidoCompletoHandler(
            $app->make(\App\Domain\Pedidos\CommandHandlers\CrearPedidoCompletoHandlerContract::class),
        ));

        $this->app->bind(AgregarPrendaAlPedidoHandler::class, fn ($app) => new AgregarPrendaAlPedidoHandler(
            $app->make(PedidoProduccion::class),
            $app->make(PedidoPrendaService::class),
            $app->make(PrendaValidator::class)
        ));

        $this->app->bind(ActualizarPedidoHandler::class, fn ($app) => new ActualizarPedidoHandler(
            $app->make(PedidoProduccion::class),
            $app->make(PedidoValidator::class),
        ));

        $this->app->bind(CambiarEstadoPedidoHandler::class, fn ($app) => new CambiarEstadoPedidoHandler(
            $app->make(PedidoProduccion::class),
            $app->make(EstadoValidator::class)
        ));

        $this->app->bind(EliminarPedidoHandler::class, fn ($app) => new EliminarPedidoHandler(
            $app->make(PedidoProduccion::class)
        ));

        $this->app->bind(ActualizarVariantePrendaHandler::class, fn ($app) => new ActualizarVariantePrendaHandler(
            $app->make(\App\Domain\Pedidos\CommandHandlers\ActualizarVariantePrendaHandlerContract::class)
        ));
    }

    private function registerEppCommandHandlers(): void
    {
        $this->app->bind(AgregarEppAlPedidoHandler::class, fn ($app) => new AgregarEppAlPedidoHandler(
            $app->make(PedidoEppRepositoryInterface::class)
        ));

        $this->app->bind(EliminarEppDelPedidoHandler::class, fn ($app) => new EliminarEppDelPedidoHandler(
            $app->make(PedidoEppRepositoryInterface::class)
        ));
    }

    private function registerApplicationCommandHandlers(): void
    {
        $this->app->bind(CrearEppHandler::class, fn () => new CrearEppHandler());
    }

    private function registerQueries(QueryBus $queryBus): void
    {
        $this->registerPedidosQueries($queryBus);
        $this->registerEppQueries($queryBus);
    }

    private function registerPedidosQueries(QueryBus $queryBus): void
    {
        $queryBus->register(ObtenerPedidoQuery::class, ObtenerPedidoHandler::class);
        $queryBus->register(ListarPedidosQuery::class, ListarPedidosHandler::class);
        $queryBus->register(FiltrarPedidosPorEstadoQuery::class, FiltrarPedidosPorEstadoHandler::class);
        $queryBus->register(BuscarPedidoPorNumeroQuery::class, BuscarPedidoPorNumeroHandler::class);
    }

    private function registerEppQueries(QueryBus $queryBus): void
    {
        $queryBus->register(BuscarEppQuery::class, BuscarEppHandler::class);
        $queryBus->register(ObtenerEppPorIdQuery::class, ObtenerEppPorIdHandler::class);
        $queryBus->register(ObtenerEppPorCategoriaQuery::class, ObtenerEppPorCategoriaHandler::class);
        $queryBus->register(ListarEppActivosQuery::class, ListarEppActivosHandler::class);
        $queryBus->register(ListarCategoriasEppQuery::class, ListarCategoriasEppHandler::class);
        $queryBus->register(ObtenerEppDelPedidoQuery::class, ObtenerEppDelPedidoHandler::class);
    }

    private function registerCommands(CommandBus $commandBus): void
    {
        $this->registerPedidosCommands($commandBus);
        $this->registerEppCommands($commandBus);
        $this->registerApplicationCommands($commandBus);
    }

    private function registerPedidosCommands(CommandBus $commandBus): void
    {
        $commandBus->register(CrearPedidoCommand::class, CrearPedidoHandler::class);
        $commandBus->register(CrearPedidoCompletoCommand::class, CrearPedidoCompletoHandler::class);
        $commandBus->register(AgregarPrendaAlPedidoCommand::class, AgregarPrendaAlPedidoHandler::class);
        $commandBus->register(ActualizarPedidoCommand::class, ActualizarPedidoHandler::class);
        $commandBus->register(CambiarEstadoPedidoCommand::class, CambiarEstadoPedidoHandler::class);
        $commandBus->register(EliminarPedidoCommand::class, EliminarPedidoHandler::class);
        $commandBus->register(ActualizarVariantePrendaCommand::class, ActualizarVariantePrendaHandler::class);
    }

    private function registerEppCommands(CommandBus $commandBus): void
    {
        $commandBus->register(AgregarEppAlPedidoCommand::class, AgregarEppAlPedidoHandler::class);
        $commandBus->register(EliminarEppDelPedidoCommand::class, EliminarEppDelPedidoHandler::class);
    }

    private function registerApplicationCommands(CommandBus $commandBus): void
    {
        $commandBus->register(CrearEppCommand::class, CrearEppHandler::class);
    }
}
