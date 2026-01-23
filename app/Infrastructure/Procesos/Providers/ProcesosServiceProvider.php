<?php

namespace App\Infrastructure\Procesos\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Procesos\Repositories\ProcesoPrendaDetalleRepository;
use App\Infrastructure\Procesos\Persistence\Eloquent\ProcesoPrendaDetalleRepositoryImpl;

class ProcesosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProcesoPrendaDetalleRepository::class,
            ProcesoPrendaDetalleRepositoryImpl::class
        );
    }

    public function boot(): void
    {
        //
    }
}
