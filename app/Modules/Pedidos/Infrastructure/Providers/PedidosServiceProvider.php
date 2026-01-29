<?php

namespace App\Modules\Pedidos\Infrastructure\Providers;

use App\Services\Pedidos\PedidoProduccionCreatorService;
use App\Services\Pedidos\PrendaProcessorService;
use App\Application\Services\PedidoPrendaService;
use App\Application\Services\PedidoLogoService;
use App\Application\Services\CopiarImagenesCotizacionAPedidoService;
use App\Application\Services\ColorGeneroMangaBrocheService;
use App\Domain\Pedidos\Despacho\Services\DespachoGeneradorService;
use App\Domain\Pedidos\Despacho\Services\DespachoValidadorService;
use App\Domain\Pedidos\Despacho\Services\DesparChoParcialesPersistenceService;
use App\Domain\Pedidos\Despacho\Repositories\DesparChoParcialesRepository;
use App\Infrastructure\Repositories\Pedidos\Despacho\DesparChoParcialesRepositoryImpl;
use App\Domain\Pedidos\Services\ImagenRelocalizadorService;
use App\Application\Pedidos\Despacho\UseCases\ObtenerFilasDespachoUseCase;
use App\Application\Pedidos\Despacho\UseCases\GuardarDespachoUseCase;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider para servicios de Pedidos
 * 
 * Responsabilidad: Registrar y configurar servicios con binding
 * Patrón: Service Provider + Dependency Injection
 * Principio: DIP - Dependency Inversion Principle
 */
class PedidosServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar PrendaProcessorService como singleton
        $this->app->singleton(PrendaProcessorService::class, function ($app) {
            return new PrendaProcessorService();
        });

        // Registrar PedidoProduccionCreatorService
        $this->app->bind(PedidoProduccionCreatorService::class, function ($app) {
            return new PedidoProduccionCreatorService(
                $app->make(PrendaProcessorService::class)
            );
        });

        // Registrar PedidoPrendaService
        $this->app->singleton(PedidoPrendaService::class, function ($app) {
            return new PedidoPrendaService(
                $app->make(ColorGeneroMangaBrocheService::class)
            );
        });

        // Registrar PedidoLogoService
        $this->app->singleton(PedidoLogoService::class, function ($app) {
            return new PedidoLogoService();
        });

        // Registrar CopiarImagenesCotizacionAPedidoService
        $this->app->singleton(CopiarImagenesCotizacionAPedidoService::class, function ($app) {
            return new CopiarImagenesCotizacionAPedidoService();
        });

        // ========================================
        // REPOSITORIOS (DOMAIN INTERFACES)
        // ========================================

        // Registrar interfaz DesparChoParcialesRepository con implementación
        $this->app->bind(DesparChoParcialesRepository::class, function ($app) {
            return new DesparChoParcialesRepositoryImpl();
        });

        // ========================================
        // SERVICIOS DE DESPACHO (DOMAIN LAYER)
        // ========================================

        // Registrar ImagenRelocalizadorService
        $this->app->singleton(ImagenRelocalizadorService::class, function ($app) {
            return new ImagenRelocalizadorService();
        });

        // Registrar DespachoGeneradorService
        $this->app->singleton(DespachoGeneradorService::class, function ($app) {
            return new DespachoGeneradorService();
        });

        // Registrar DespachoValidadorService
        $this->app->singleton(DespachoValidadorService::class, function ($app) {
            return new DespachoValidadorService();
        });

        // Registrar DesparChoParcialesPersistenceService
        $this->app->singleton(DesparChoParcialesPersistenceService::class, function ($app) {
            return new DesparChoParcialesPersistenceService(
                $app->make(DesparChoParcialesRepository::class)
            );
        });

        // ========================================
        // USE CASES DE DESPACHO (APPLICATION LAYER)
        // ========================================

        // Registrar ObtenerFilasDespachoUseCase
        $this->app->bind(ObtenerFilasDespachoUseCase::class, function ($app) {
            return new ObtenerFilasDespachoUseCase(
                $app->make(DespachoGeneradorService::class)
            );
        });

        // Registrar GuardarDespachoUseCase
        $this->app->bind(GuardarDespachoUseCase::class, function ($app) {
            return new GuardarDespachoUseCase(
                $app->make(DespachoValidadorService::class),
                $app->make(DesparChoParcialesPersistenceService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Cargar rutas del módulo
        $this->loadRoutesFrom(__DIR__ . '/../../Routes/api.php');
    }
}
