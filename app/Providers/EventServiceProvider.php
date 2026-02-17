<?php

namespace App\Providers;

use App\Events\PedidoCreado;
use App\Listeners\NotificarSupervisoresPedidoCreado;
use App\Listeners\CrearProcesosParaCotizacionReflectivo;
use App\Domain\Shared\DomainEventDispatcher;
use App\Domain\PedidoProduccion\Events\PedidoProduccionCreado;
use App\Domain\PedidoProduccion\Events\PrendaPedidoAgregada;
use App\Domain\PedidoProduccion\Listeners\NotificarClientePedidoCreado;
use App\Domain\PedidoProduccion\Listeners\ActualizarCachePedidos;
use App\Domain\PedidoProduccion\Listeners\RegistrarAuditoriaPedido;
use App\Domain\PedidoProduccion\Listeners\ActualizarEstadisticasPrendas;
use App\Observers\DespachoParcialesObserver;
use App\Models\DesparChoParcialesModel;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Events\Dispatcher as LaravelDispatcher;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        PedidoCreado::class => [
            NotificarSupervisoresPedidoCreado::class,
            CrearProcesosParaCotizacionReflectivo::class,
        ],
    ];

    /**
     * Register any other events for your application.
     */
    public function register(): void
    {
        // Registrar DomainEventDispatcher como singleton
        $this->app->singleton(DomainEventDispatcher::class, function ($app) {
            return new DomainEventDispatcher(
                $app->make(LaravelDispatcher::class)
            );
        });
    }

    /**
     * Bootstrap de servicios
     */
    public function boot(): void
    {
        // Obtener dispatcher del contenedor
        $dispatcher = $this->app->make(DomainEventDispatcher::class);
        
        // Registrar listeners de dominio
        $this->registerDomainEventListeners($dispatcher);

        // Registrar observers
        DesparChoParcialesModel::observe(DespachoParcialesObserver::class);
    }

    /**
     * Registrar listeners para eventos de dominio
     */
    private function registerDomainEventListeners(DomainEventDispatcher $dispatcher): void
    {
        // Notificar cliente cuando se crea pedido
        $dispatcher->subscribe(
            PedidoProduccionCreado::class,
            function (PedidoProduccionCreado $event) {
                (new NotificarClientePedidoCreado())($event);
            },
            async: false
        );

        // Actualizar cachés cuando se crea pedido
        $dispatcher->subscribe(
            PedidoProduccionCreado::class,
            function (PedidoProduccionCreado $event) {
                (new ActualizarCachePedidos())($event);
            },
            async: false
        );

        // Registrar auditoría cuando se crea pedido
        $dispatcher->subscribe(
            PedidoProduccionCreado::class,
            function (PedidoProduccionCreado $event) {
                (new RegistrarAuditoriaPedido())($event);
            },
            async: false
        );

        // Actualizar estadísticas cuando se agrega prenda
        $dispatcher->subscribe(
            PrendaPedidoAgregada::class,
            function (PrendaPedidoAgregada $event) {
                (new ActualizarEstadisticasPrendas())($event);
            },
            async: false
        );
    }
}
