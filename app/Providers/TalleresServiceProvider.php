<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Talleres\Repositories\OrdenTallerRepositoryInterface;
use App\Domain\Talleres\Services\CalculadorProgresoServiceContract;
use App\Domain\Talleres\Services\FiltroOrdenesServiceContract;
use App\Infrastructure\Talleres\Repositories\EloquentOrdenTallerRepository;
use App\Infrastructure\Talleres\Services\CalculadorProgresoService;
use App\Infrastructure\Talleres\Services\FiltroOrdenesService;

class TalleresServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar Repository
        $this->app->bind(
            OrdenTallerRepositoryInterface::class,
            EloquentOrdenTallerRepository::class
        );

        // Registrar Services
        $this->app->bind(
            CalculadorProgresoServiceContract::class,
            CalculadorProgresoService::class
        );

        $this->app->bind(
            FiltroOrdenesServiceContract::class,
            FiltroOrdenesService::class
        );
    }

    public function boot(): void
    {
        //
    }
}
