<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Bodega\Repositories\OrdenBodegaRepositoryInterface;
use App\Repositories\EloquentOrdenBodegaRepository;
use App\Domain\Bodega\Services\CrearOrdenBodegaService;
use App\Domain\Bodega\Services\ActualizarEstadoOrdenBodegaService;
use App\Domain\Bodega\Services\CancelarOrdenBodegaService;
use App\Domain\Bodega\Services\ObtenerOrdenBodegaService;

class BodegaDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar Repository Interface
        $this->app->singleton(
            OrdenBodegaRepositoryInterface::class,
            EloquentOrdenBodegaRepository::class
        );

        // Registrar Application Services
        $this->app->singleton(
            CrearOrdenBodegaService::class,
            fn($app) => new CrearOrdenBodegaService(
                $app->make(OrdenBodegaRepositoryInterface::class)
            )
        );

        $this->app->singleton(
            ActualizarEstadoOrdenBodegaService::class,
            fn($app) => new ActualizarEstadoOrdenBodegaService(
                $app->make(OrdenBodegaRepositoryInterface::class)
            )
        );

        $this->app->singleton(
            CancelarOrdenBodegaService::class,
            fn($app) => new CancelarOrdenBodegaService(
                $app->make(OrdenBodegaRepositoryInterface::class)
            )
        );

        $this->app->singleton(
            ObtenerOrdenBodegaService::class,
            fn($app) => new ObtenerOrdenBodegaService(
                $app->make(OrdenBodegaRepositoryInterface::class)
            )
        );
    }

    public function boot(): void
    {
        //
    }
}
