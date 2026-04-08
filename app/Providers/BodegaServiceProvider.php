<?php

namespace App\Providers;

use App\Application\Bodega\Services\BodegaAuditoriaService;
use App\Application\Bodega\Services\BodegaNotaService;
use App\Application\Bodega\Services\BodegaPedidoService;
use App\Application\Bodega\Services\BodegaRoleService;
use App\Domain\Bodega\Services\BodegaAuditoriaServiceContract;
use App\Domain\Bodega\Services\BodegaNotaServiceContract;
use App\Domain\Bodega\Services\BodegaPedidoServiceContract;
use App\Domain\Bodega\Services\BodegaRepositoryContract;
use App\Domain\Bodega\Services\PedidoEstadoCalculatorContract;
use Illuminate\Support\ServiceProvider;

class BodegaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BodegaPedidoService::class, function ($app) {
            return new BodegaPedidoService($app->make(BodegaPedidoServiceContract::class));
        });

        $this->app->singleton(BodegaRoleService::class, function () {
            return new BodegaRoleService();
        });

        $this->app->singleton(BodegaNotaService::class, function ($app) {
            return new BodegaNotaService($app->make(BodegaNotaServiceContract::class));
        });

        $this->app->singleton(BodegaAuditoriaService::class, function ($app) {
            return new BodegaAuditoriaService($app->make(BodegaAuditoriaServiceContract::class));
        });

        $this->app->singleton(\App\Application\Bodega\Services\BodegaRepository::class, function ($app) {
            return new \App\Application\Bodega\Services\BodegaRepository($app->make(BodegaRepositoryContract::class));
        });

        $this->app->singleton(\App\Application\Bodega\Calculators\PedidoEstadoCalculator::class, function ($app) {
            return new \App\Application\Bodega\Calculators\PedidoEstadoCalculator($app->make(PedidoEstadoCalculatorContract::class));
        });
    }

    public function boot(): void
    {
    }
}
