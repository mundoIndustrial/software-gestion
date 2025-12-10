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
