<?php

namespace App\Infrastructure\Providers;

use App\Application\Cotizacion\Handlers\Commands\AceptarCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\CambiarEstadoCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\CrearCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\EliminarCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\SubirImagenCotizacionHandler;
use App\Application\Cotizacion\Handlers\Queries\ListarCotizacionesHandler;
use App\Application\Cotizacion\Handlers\Queries\ObtenerCotizacionHandler;
use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCotizacionRepository;
use App\Infrastructure\Storage\ImagenAlmacenador;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\ImageManager;

/**
 * CotizacionServiceProvider - Service Provider para el módulo de cotizaciones
 *
 * Registra todas las dependencias del módulo DDD
 */
final class CotizacionServiceProvider extends ServiceProvider
{
    /**
     * Registrar servicios
     */
    public function register(): void
    {
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
    }

    /**
     * Boot servicios
     */
    public function boot(): void
    {
        // Aquí se pueden registrar event listeners, migrations, etc.
    }
}
