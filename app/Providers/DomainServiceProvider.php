<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Ordenes\Repositories\OrdenRepositoryInterface;
use App\Repositories\EloquentOrdenRepository;
use App\Domain\Ordenes\Services\CrearOrdenService;
use App\Domain\Ordenes\Services\ActualizarOrdenService;
use App\Domain\Ordenes\Services\CancelarOrdenService;
use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCotizacionRepository;
use App\Application\Cotizacion\Handlers\Commands\CrearCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\EliminarCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\CambiarEstadoCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\AceptarCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\SubirImagenCotizacionHandler;
use App\Application\Cotizacion\Handlers\Queries\ListarCotizacionesHandler;
use App\Application\Cotizacion\Handlers\Queries\ObtenerCotizacionHandler;
use App\Infrastructure\Storage\ImagenAlmacenador;
use Intervention\Image\ImageManager;

// ======================================== DDD PEDIDOS
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Infrastructure\Pedidos\Persistence\Eloquent\PedidoRepositoryImpl;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Application\Pedidos\UseCases\ListarPedidosPorClienteUseCase;
use App\Application\Pedidos\UseCases\CancelarPedidoUseCase;
use App\Application\Pedidos\UseCases\ActualizarDescripcionPedidoUseCase;
use App\Application\Pedidos\UseCases\IniciarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\CompletarPedidoUseCase;

/**
 * Domain Service Provider
 * 
 * Registra todas las dependencias de DDD en el contenedor.
 * Permite inyección de dependencias transparente.
 */
class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ========================================
        // ORDENES - Registrar Repository Interface con implementación Eloquent
        // ========================================
        $this->app->bind(
            OrdenRepositoryInterface::class,
            EloquentOrdenRepository::class
        );

        // ========================================
        // COTIZACIONES - Registrar Repository y Handlers
        // ========================================
        // Registrar Repository
        $this->app->singleton(
            CotizacionRepositoryInterface::class,
            EloquentCotizacionRepository::class
        );

        // Registrar Command Handlers
        $this->app->singleton(CrearCotizacionHandler::class);
        $this->app->singleton(EliminarCotizacionHandler::class);
        $this->app->singleton(CambiarEstadoCotizacionHandler::class);
        $this->app->singleton(AceptarCotizacionHandler::class);
        $this->app->singleton(SubirImagenCotizacionHandler::class);

        // Registrar Query Handlers
        $this->app->singleton(ObtenerCotizacionHandler::class);
        $this->app->singleton(ListarCotizacionesHandler::class);

        // Registrar Servicios de Storage
        $this->app->singleton(ImagenAlmacenador::class, function () {
            return new ImagenAlmacenador(ImageManager::gd());
        });

        // Registrar Application Services
        $this->app->bind(
            CrearOrdenService::class,
            function ($app) {
                return new CrearOrdenService(
                    $app->make(OrdenRepositoryInterface::class)
                );
            }
        );

        // Estos se crearán en FASE 3
        // $this->app->bind(ActualizarOrdenService::class, ...);
        // $this->app->bind(CancelarOrdenService::class, ...);

        // ========================================
        // PEDIDOS DDD (Fase 3+)
        // ========================================
        // Registrar Repository Interface con implementación
        $this->app->bind(
            PedidoRepository::class,
            PedidoRepositoryImpl::class
        );

        // Registrar Use Cases como singletons
        $this->app->singleton(CrearPedidoUseCase::class, function ($app) {
            return new CrearPedidoUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(ConfirmarPedidoUseCase::class, function ($app) {
            return new ConfirmarPedidoUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(ObtenerPedidoUseCase::class, function ($app) {
            return new ObtenerPedidoUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(ListarPedidosPorClienteUseCase::class, function ($app) {
            return new ListarPedidosPorClienteUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(CancelarPedidoUseCase::class, function ($app) {
            return new CancelarPedidoUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(ActualizarDescripcionPedidoUseCase::class, function ($app) {
            return new ActualizarDescripcionPedidoUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(IniciarProduccionPedidoUseCase::class, function ($app) {
            return new IniciarProduccionPedidoUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(CompletarPedidoUseCase::class, function ($app) {
            return new CompletarPedidoUseCase($app->make(PedidoRepository::class));
        });
    }

    public function boot(): void
    {
        // Registrar event listeners para Domain Events
        $this->registerDomainEventListeners();
    }

    private function registerDomainEventListeners(): void
    {
        // Los listeners se registran aquí cuando estén listos
        // Ejemplo:
        // $this->app['events']->listen(\App\Domain\Ordenes\Events\OrdenCreada::class, ...);
    }
}
