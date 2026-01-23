<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\TablaOriginalBodega;
use App\Models\ProcesoPrenda;
use App\Models\PedidoProduccion;
use App\Models\Cotizacion;
use App\Observers\TablaOriginalBodegaObserver;
use App\Observers\ProcesoPrendaObserver;
use App\Observers\PedidoProduccionObserver;
use App\Domain\Operario\Repositories\OperarioRepository;
use App\Infrastructure\Persistence\Eloquent\OperarioRepositoryImpl;
use App\Infrastructure\Providers\AsesoresServiceProvider;
use App\Infrastructure\Providers\PedidosProduccionServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar Asesores Service Provider
        $this->app->register(AsesoresServiceProvider::class);
        // Registrar PedidosProduccionController Service Provider
        $this->app->register(PedidosProduccionServiceProvider::class);
        // Registrar implementación de OperarioRepository
        $this->app->bind(
            OperarioRepository::class,
            OperarioRepositoryImpl::class
        );

        // Registrar implementaciones de Procesos (DDD)
        $this->app->bind(
            \App\Domain\Procesos\Repositories\TipoProcesoRepository::class,
            \App\Repositories\EloquentTipoProcesoRepository::class
        );

        $this->app->bind(
            \App\Domain\Procesos\Repositories\ProcesoPrendaDetalleRepository::class,
            \App\Repositories\EloquentProcesoPrendaDetalleRepository::class
        );

        $this->app->bind(
            \App\Domain\Procesos\Repositories\ProcesoPrendaImagenRepository::class,
            \App\Repositories\EloquentProcesoPrendaImagenRepository::class
        );

        // Registrar implementaciones de EPP (DDD)
        $this->app->bind(
            \App\Domain\Epp\Repositories\EppRepositoryInterface::class,
            \App\Domain\Epp\Repositories\EppRepository::class
        );

        $this->app->bind(
            \App\Domain\Epp\Repositories\PedidoEppRepositoryInterface::class,
            \App\Domain\Epp\Repositories\PedidoEppRepository::class
        );

        // Registrar servicio de dominio de EPP
        $this->app->singleton(
            \App\Domain\Epp\Services\EppDomainService::class,
            function ($app) {
                return new \App\Domain\Epp\Services\EppDomainService(
                    $app->make(\App\Domain\Epp\Repositories\EppRepositoryInterface::class)
                );
            }
        );

        // Registrar el servicio de generación de números de cotización
        $this->app->singleton(
            \App\Application\Cotizacion\Services\GenerarNumeroCotizacionService::class,
            function ($app) {
                return new \App\Application\Cotizacion\Services\GenerarNumeroCotizacionService();
            }
        );

        // Registrar la façade de Intervention Image
        $this->app->singleton('image', function ($app) {
            return new \Intervention\Image\ImageManager(
                new \Intervention\Image\Drivers\Gd\Driver()
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Los Observers de TablaOriginal han sido eliminados
        // La sincronización ocurre automáticamente a través de PedidoProduccion
        // y sus relaciones con PrendaPedido y ProcesoPrenda.

        // Registrar el Observer para TablaOriginalBodega (Bodega)
        // Esto sincroniza automáticamente los cambios en 'descripcion' y 'cliente'
        // del padre hacia los registros hijos en 'registros_por_orden_bodega'
        // TablaOriginalBodega::observe(TablaOriginalBodegaObserver::class);

        // Registrar Observer para ProcesoPrenda
        // Actualiza automáticamente el campo 'area' en pedidos_produccion
        // cada vez que se crea o modifica un proceso
        ProcesoPrenda::observe(ProcesoPrendaObserver::class);

        // Registrar Observer para PedidoProduccion
        // Crea notificaciones cuando se asigna la fecha estimada de entrega
        PedidoProduccion::observe(PedidoProduccionObserver::class);

        // View Composer para el sidebar del contador
        View::composer('contador.sidebar', function ($view) {
            $cotizacionesAprobadas = Cotizacion::where('estado', 'APROBADA_CONTADOR')
                ->where('es_borrador', 0)
                ->get();
            $view->with('cotizacionesAprobadas', $cotizacionesAprobadas);
        });
    }
}
