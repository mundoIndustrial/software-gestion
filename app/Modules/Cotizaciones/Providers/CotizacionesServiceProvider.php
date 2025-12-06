<?php

namespace App\Modules\Cotizaciones\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Cotizaciones\Contracts\CotizacionRepositoryInterface;
use App\Modules\Cotizaciones\Contracts\CotizacionQueryServiceInterface;
use App\Modules\Cotizaciones\Contracts\CotizacionCommandServiceInterface;
use App\Modules\Cotizaciones\Contracts\CotizacionTransformerInterface;
use App\Modules\Cotizaciones\Repositories\CotizacionRepository;
use App\Modules\Cotizaciones\Services\CotizacionQueryService;
use App\Modules\Cotizaciones\Services\CotizacionCommandService;
use App\Modules\Cotizaciones\Services\CotizacionFacadeService;
use App\Modules\Cotizaciones\Transformers\CotizacionListTransformer;

/**
 * CotizacionesServiceProvider
 * 
 * Registra las dependencias del módulo Cotizaciones
 * Aplicada inversión de control (IoC)
 * Principio: Dependency Inversion (DIP)
 */
class CotizacionesServiceProvider extends ServiceProvider
{
    /**
     * Registrar servicios en el contenedor
     */
    public function register()
    {
        // Repositorio
        $this->app->bind(
            CotizacionRepositoryInterface::class,
            CotizacionRepository::class
        );

        // Servicios de lectura
        $this->app->bind(
            CotizacionQueryServiceInterface::class,
            CotizacionQueryService::class
        );

        // Servicios de escritura
        $this->app->bind(
            CotizacionCommandServiceInterface::class,
            CotizacionCommandService::class
        );

        // Transformador
        $this->app->bind(
            CotizacionTransformerInterface::class,
            CotizacionListTransformer::class
        );

        // Fachada (Singleton para compartir entre controladores)
        $this->app->singleton(
            CotizacionFacadeService::class,
            function ($app) {
                return new CotizacionFacadeService(
                    $app->make(CotizacionQueryServiceInterface::class),
                    $app->make(CotizacionCommandServiceInterface::class),
                    $app->make(CotizacionRepositoryInterface::class),
                    $app->make(CotizacionTransformerInterface::class)
                );
            }
        );
    }

    /**
     * Bootstrap servicios
     */
    public function boot()
    {
        // Cargar rutas si existen
        if (file_exists(__DIR__ . '/../Routes/api.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        }

        // Cargar vistas si existen
        if (is_dir(__DIR__ . '/../Resources/views')) {
            $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'cotizaciones');
        }
    }
}
