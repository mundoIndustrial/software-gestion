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

/**
 * PedidoDDDServiceProvider
 * 
 * Registra los bindings de DDD para el módulo de Pedidos
 * Implementa Dependency Inversion Principle (SOLID)
 */
class PedidoDDDServiceProvider extends ServiceProvider
{
    /**
     * Register servicios de Dominio
     */
    public function register(): void
    {
        // Domain Services
        $this->app->bind(PedidoRepository::class, EloquentPedidoRepository::class);
        $this->app->bind(ConsecutivosService::class, ConsecutivosServiceImpl::class);
        $this->app->bind(ImagenesEppService::class, ImagenesEppServiceImpl::class);

        // Application Services
        $this->app->bind(PedidoTransformService::class, PedidoTransformServiceImpl::class);
        $this->app->bind(PedidoFilterService::class, PedidoFilterServiceImpl::class);
        $this->app->bind(PedidoEnricherService::class, PedidoEnricherServiceImpl::class);
    }

    /**
     * Bootstrap servicios de aplicación
     */
    public function boot(): void
    {
        //
    }
}
