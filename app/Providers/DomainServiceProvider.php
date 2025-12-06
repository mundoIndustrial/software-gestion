<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Ordenes\Repositories\OrdenRepositoryInterface;
use App\Repositories\EloquentOrdenRepository;
use App\Domain\Ordenes\Services\CrearOrdenService;
use App\Domain\Ordenes\Services\ActualizarOrdenService;
use App\Domain\Ordenes\Services\CancelarOrdenService;

/**
 * Domain Service Provider
 * 
 * Registra todas las dependencias de DDD en el contenedor.
 * Permite inyección de dependencias transparente.
 */
class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar Repository Interface con implementación Eloquent
        $this->app->bind(
            OrdenRepositoryInterface::class,
            EloquentOrdenRepository::class
        );

        // Registrar Application Services
        $this->app->bind(
            CrearOrdenService::class,
            function ($app) {
                return new CrearOrdenService(
                    $app->make(OrdenRepositoryInterface::class)
                );
            }
        );

        // Estos se crearán en FASE 3
        // $this->app->bind(ActualizarOrdenService::class, ...);
        // $this->app->bind(CancelarOrdenService::class, ...);
    }

    public function boot(): void
    {
        // Registrar event listeners para Domain Events
        $this->registerDomainEventListeners();
    }

    private function registerDomainEventListeners(): void
    {
        // Los listeners se registran aquí cuando estén listos
        // Ejemplo:
        // $this->app['events']->listen(\App\Domain\Ordenes\Events\OrdenCreada::class, ...);
    }
}
