<?php

namespace App\Providers;

use App\Services\Pedidos\PedidoProduccionCreatorService;
use App\Services\Pedidos\PrendaProcessorService;
use App\Application\Services\PedidoPrendaService;
use App\Application\Services\PedidoLogoService;
use App\Application\Services\CopiarImagenesCotizacionAPedidoService;
use App\Application\Services\ColorGeneroMangaBrocheService;
use App\Domain\Pedidos\Despacho\Services\DespachoGeneradorService;
use App\Domain\Pedidos\Despacho\Services\DespachoValidadorService;
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
     * Register services.
     */
    public function register(): void
    {
        // Registrar PrendaProcessorService como singleton
        // (reutilizable sin estado)
        $this->app->singleton(PrendaProcessorService::class, function ($app) {
            return new PrendaProcessorService();
        });

        // Registrar PedidoProduccionCreatorService con inyección de PrendaProcessorService
        $this->app->bind(PedidoProduccionCreatorService::class, function ($app) {
            return new PedidoProduccionCreatorService(
                $app->make(PrendaProcessorService::class)
            );
        });

        // Registrar PedidoPrendaService como singleton (DDD)
        $this->app->singleton(PedidoPrendaService::class, function ($app) {
            return new PedidoPrendaService(
                $app->make(ColorGeneroMangaBrocheService::class)
            );
        });

        // Registrar PedidoLogoService como singleton (DDD)
        $this->app->singleton(PedidoLogoService::class, function ($app) {
            return new PedidoLogoService();
        });

        // Registrar CopiarImagenesCotizacionAPedidoService como singleton
        $this->app->singleton(CopiarImagenesCotizacionAPedidoService::class, function ($app) {
            return new CopiarImagenesCotizacionAPedidoService();
        });

        // ========================================
        // SERVICIOS DE DESPACHO (DOMAIN LAYER)
        // ========================================

        // Registrar ImagenRelocalizadorService como singleton
        $this->app->singleton(ImagenRelocalizadorService::class, function ($app) {
            return new ImagenRelocalizadorService();
        });

        // Registrar DespachoGeneradorService como singleton
        $this->app->singleton(DespachoGeneradorService::class, function ($app) {
            return new DespachoGeneradorService();
        });

        // Registrar DespachoValidadorService como singleton
        $this->app->singleton(DespachoValidadorService::class, function ($app) {
            return new DespachoValidadorService();
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
                $app->make(DespachoValidadorService::class)
            );
        });

        // Registrar ImagenRelocalizadorService como singleton
        $this->app->singleton(ImagenRelocalizadorService::class, function ($app) {
            return new ImagenRelocalizadorService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
