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
use App\Domain\Pedidos\Despacho\Services\DesparChoParcialesPersistenceService;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Domain\Pedidos\Services\ImagenRelocalizadorService;
use App\Application\Pedidos\Despacho\UseCases\ObtenerFilasDespachoUseCase;
use App\Application\Pedidos\Despacho\UseCases\GuardarDespachoUseCase;

// NUEVOS: Gestión de Items en Pedidos (REFACTOR - DDD)
use App\Domain\Pedidos\Repositories\ItemPedidoRepository as ItemPedidoRepositoryInterface;
use App\Repositories\EloquentItemPedidoRepository;
use App\Domain\Pedidos\DomainServices\GestorItemsPedidoDomainService;
use App\Domain\Pedidos\CommandHandlers\AgregarItemAlPedidoHandler;
use App\Domain\Pedidos\CommandHandlers\EliminarItemDelPedidoHandler;
use App\Application\Pedidos\UseCases\AgregarItemAlPedidoUseCase;
use App\Application\Pedidos\UseCases\EliminarItemDelPedidoUseCase;

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
        // REPOSITORIOS (DOMAIN INTERFACES)
        // ========================================

        // Registrar interfaz DesparChoParcialesRepository con implementación
        $this->app->bind(DesparChoParcialesRepository::class, function ($app) {
            return new DesparChoParcialesRepositoryImpl();
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
            return new DespachoGeneradorService(
                $app->make(ObtenerPedidoUseCase::class)
            );
        });

        // Registrar DespachoValidadorService como singleton
        $this->app->singleton(DespachoValidadorService::class, function ($app) {
            return new DespachoValidadorService();
        });

        // Registrar DesparChoParcialesPersistenceService como singleton
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

        // Registrar ImagenRelocalizadorService como singleton
        $this->app->singleton(ImagenRelocalizadorService::class, function ($app) {
            return new ImagenRelocalizadorService();
        });

        // ========================================
        // GESTIÓN DE ITEMS EN PEDIDOS (REFACTOR - DDD)
        // ========================================

        // Registrar Repository Interface con implementación Eloquent
        $this->app->bind(ItemPedidoRepositoryInterface::class, function ($app) {
            return new EloquentItemPedidoRepository();
        });

        // Registrar Domain Service
        $this->app->singleton(GestorItemsPedidoDomainService::class, function ($app) {
            return new GestorItemsPedidoDomainService();
        });

        // Registrar Command Handlers
        $this->app->bind(AgregarItemAlPedidoHandler::class, function ($app) {
            return new AgregarItemAlPedidoHandler(
                $app->make(ItemPedidoRepositoryInterface::class),
                $app->make(GestorItemsPedidoDomainService::class)
            );
        });

        $this->app->bind(EliminarItemDelPedidoHandler::class, function ($app) {
            return new EliminarItemDelPedidoHandler(
                $app->make(ItemPedidoRepositoryInterface::class),
                $app->make(GestorItemsPedidoDomainService::class)
            );
        });

        // Registrar Use Cases (Application Services)
        $this->app->bind(AgregarItemAlPedidoUseCase::class, function ($app) {
            return new AgregarItemAlPedidoUseCase(
                $app->make(AgregarItemAlPedidoHandler::class),
                $app->make(ItemPedidoRepositoryInterface::class),
                $app->make(GestorItemsPedidoDomainService::class)
            );
        });

        $this->app->bind(EliminarItemDelPedidoUseCase::class, function ($app) {
            return new EliminarItemDelPedidoUseCase(
                $app->make(EliminarItemDelPedidoHandler::class),
                $app->make(ItemPedidoRepositoryInterface::class),
                $app->make(GestorItemsPedidoDomainService::class)
            );
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
