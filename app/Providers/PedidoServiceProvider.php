<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Pedidos\Contracts\PedidoRepository;
use App\Domain\Pedidos\Contracts\ConsecutivosService;
use App\Domain\Pedidos\Contracts\ImagenesEppService;
use App\Application\Pedidos\Contracts\PedidoTransformService;
use App\Application\Pedidos\Contracts\PedidoFilterService;
use App\Application\Pedidos\Contracts\PedidoEnricherService;
use App\Infrastructure\Repositories\EloquentPedidoRepository;
use App\Infrastructure\Services\Pedidos\ConsecutivosServiceImpl;
use App\Infrastructure\Services\Pedidos\ImagenesEppServiceImpl;
use App\Application\Pedidos\Services\PedidoTransformServiceImpl;
use App\Application\Pedidos\Services\PedidoFilterServiceImpl;
use App\Application\Pedidos\Services\PedidoEnricherServiceImpl;

// RegistrosOrdenes - Domain Contracts
use App\Domain\RegistrosOrdenes\Contracts\RegistroOrdenRepository;
use App\Domain\RegistrosOrdenes\Contracts\SeguimientoOrdenService;
use App\Domain\RegistrosOrdenes\Contracts\DescripcionOrdenService;
use App\Domain\RegistrosOrdenes\Contracts\ImagenesOrdenService;

// RegistrosOrdenes - Application Contracts
use App\Application\RegistrosOrdenes\Contracts\FiltrosOrdenService;
use App\Application\RegistrosOrdenes\Contracts\BusquedaOrdenService;
use App\Application\RegistrosOrdenes\Contracts\TransformacionOrdenService;

// RegistrosOrdenes - Infrastructure Implementations
use App\Infrastructure\Repositories\RegistrosOrdenes\EloquentRegistroOrdenRepository;
use App\Infrastructure\Services\RegistrosOrdenes\SeguimientoOrdenServiceImpl;
use App\Infrastructure\Services\RegistrosOrdenes\DescripcionOrdenServiceImpl;
use App\Infrastructure\Services\RegistrosOrdenes\ImagenesOrdenServiceImpl;

// RegistrosOrdenes - Application Services
use App\Application\RegistrosOrdenes\Services\FiltrosOrdenServiceImpl;
use App\Application\RegistrosOrdenes\Services\BusquedaOrdenServiceImpl;
use App\Application\RegistrosOrdenes\Services\TransformacionOrdenServiceImpl;

/**
 * PedidoServiceProvider
 * 
 * Registra los bindings de DDD para los módulos de Pedidos y RegistrosOrdenes
 * Implementa Dependency Inversion Principle (SOLID)
 */
class PedidoServiceProvider extends ServiceProvider
{
    /**
     * Register servicios de Dominio
     */
    public function register(): void
    {
        // === PEDIDOS MODULE ===
        // Domain Services
        $this->app->bind(PedidoRepository::class, EloquentPedidoRepository::class);
        $this->app->bind(ConsecutivosService::class, ConsecutivosServiceImpl::class);
        $this->app->bind(ImagenesEppService::class, ImagenesEppServiceImpl::class);

        // Application Services
        $this->app->bind(PedidoTransformService::class, PedidoTransformServiceImpl::class);
        $this->app->bind(PedidoFilterService::class, PedidoFilterServiceImpl::class);
        $this->app->bind(PedidoEnricherService::class, PedidoEnricherServiceImpl::class);

        // === REGISTROS ORDENES MODULE ===
        // Domain Services
        $this->app->bind(RegistroOrdenRepository::class, EloquentRegistroOrdenRepository::class);
        $this->app->bind(SeguimientoOrdenService::class, SeguimientoOrdenServiceImpl::class);
        $this->app->bind(DescripcionOrdenService::class, DescripcionOrdenServiceImpl::class);
        $this->app->bind(ImagenesOrdenService::class, ImagenesOrdenServiceImpl::class);

        // Application Services
        $this->app->bind(FiltrosOrdenService::class, FiltrosOrdenServiceImpl::class);
        $this->app->bind(BusquedaOrdenService::class, BusquedaOrdenServiceImpl::class);
        $this->app->bind(TransformacionOrdenService::class, TransformacionOrdenServiceImpl::class);
    }

    /**
     * Bootstrap servicios de aplicación
     */
    public function boot(): void
    {
        //
    }
}
