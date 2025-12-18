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
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar implementación de OperarioRepository
        $this->app->bind(
            OperarioRepository::class,
            OperarioRepositoryImpl::class
        );

        // Registrar el servicio de generación de números de cotización
        $this->app->singleton(
            \App\Application\Cotizacion\Services\GenerarNumeroCotizacionService::class,
            function ($app) {
                return new \App\Application\Cotizacion\Services\GenerarNumeroCotizacionService();
            }
        );
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
