<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Prenda\Repositories\PrendaRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\EloquentPrendaRepository;
use App\Domain\Prenda\DomainServices\{
    AplicarOrigenAutomaticoDomainService,
    ValidarPrendaDomainService,
    NormalizarDatosPrendaDomainService
};
use App\Application\Prenda\Services\{
    ObtenerPrendaParaEdicionApplicationService,
    GuardarPrendaApplicationService
};

class PrendaServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // ===== REPOSITORIES =====
        // Registrar la interfaz - cuando se inyecte PrendaRepositoryInterface,
        // se inyectará la implementación EloquentPrendaRepository
        $this->app->bind(
            PrendaRepositoryInterface::class,
            EloquentPrendaRepository::class
        );

        // ===== DOMAIN SERVICES =====
        // Registrar como singletons (una sola instancia para toda la app)
        $this->app->singleton(AplicarOrigenAutomaticoDomainService::class);
        $this->app->singleton(ValidarPrendaDomainService::class);
        $this->app->singleton(NormalizarDatosPrendaDomainService::class);

        // ===== APPLICATION SERVICES =====
        // Registrar - Laravel inyectará automáticamente las dependencias
        $this->app->bind(ObtenerPrendaParaEdicionApplicationService::class, function ($app) {
            return new ObtenerPrendaParaEdicionApplicationService(
                $app->make(PrendaRepositoryInterface::class),
                $app->make(NormalizarDatosPrendaDomainService::class)
            );
        });

        $this->app->bind(GuardarPrendaApplicationService::class, function ($app) {
            return new GuardarPrendaApplicationService(
                $app->make(PrendaRepositoryInterface::class),
                $app->make(AplicarOrigenAutomaticoDomainService::class),
                $app->make(ValidarPrendaDomainService::class),
                $app->make(NormalizarDatosPrendaDomainService::class)
            );
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        //
    }
}
