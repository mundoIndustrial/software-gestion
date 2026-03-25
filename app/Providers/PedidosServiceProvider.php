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
use App\Domain\Pedidos\Despacho\Repositories\DesparChoParcialesRepository;
use App\Infrastructure\Repositories\PedidoProduccionRepository;
use App\Infrastructure\Repositories\ConsecutivosRecibosRepository;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Infrastructure\Services\Pedidos\ImagenRelocalizadorService;
use App\Infrastructure\Repositories\Pedidos\Despacho\DesparChoParcialesRepositoryImpl;
use App\Application\Pedidos\Despacho\UseCases\ObtenerFilasDespachoUseCase;
use App\Application\Pedidos\Despacho\UseCases\GuardarDespachoUseCase;
use App\Application\Shared\Contracts\AuditRepositoryInterface;
use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Application\Shared\Contracts\OrdenEventDispatcherInterface;
use App\Infrastructure\Services\NewsAuditRepository;
use App\Infrastructure\Services\EloquentTransactionManager;
use App\Infrastructure\Services\BroadcastOrdenEventDispatcher;
use App\Application\UseCases\RegistroOrden\GetSeguimientoPorPrendaUseCase;
use App\Application\UseCases\RegistroOrden\GetDescripcionPrendasUseCase;
use App\Application\UseCases\RegistroOrden\GetConsecutivoCosturaUseCase;
use App\Application\UseCases\RegistroOrden\CalcularDiasUseCase;
use App\Application\UseCases\RegistroOrden\CalcularDiasBatchUseCase;
use App\Application\UseCases\RegistroOrden\CalcularFechaEstimadaUseCase;
use App\Application\UseCases\RegistroOrden\GetRecibosDatosUseCase;
use App\Application\UseCases\RegistroOrden\GetNovedadesUseCase;
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

        // ========================================
        // USE CASES DE REGISTRO ORDEN (APPLICATION LAYER)
        // ========================================

        // Registrar GetSeguimientoPorPrendaUseCase con repositorios
        $this->app->bind(GetSeguimientoPorPrendaUseCase::class, function ($app) {
            return new GetSeguimientoPorPrendaUseCase(
                $app->make(PedidoProduccionRepository::class),
                $app->make(ConsecutivosRecibosRepository::class)
            );
        });

        // Registrar GetDescripcionPrendasUseCase con repositorio
        $this->app->bind(GetDescripcionPrendasUseCase::class, function ($app) {
            return new GetDescripcionPrendasUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Registrar GetConsecutivoCosturaUseCase con repositorios
        $this->app->bind(GetConsecutivoCosturaUseCase::class, function ($app) {
            return new GetConsecutivoCosturaUseCase(
                $app->make(PedidoProduccionRepository::class),
                $app->make(ConsecutivosRecibosRepository::class)
            );
        });

        // Registrar CalcularDiasUseCase (sin depedencias)
        $this->app->singleton(CalcularDiasUseCase::class, function ($app) {
            return new CalcularDiasUseCase();
        });

        // Registrar CalcularDiasBatchUseCase (sin dependencias)
        $this->app->singleton(CalcularDiasBatchUseCase::class, function ($app) {
            return new CalcularDiasBatchUseCase();
        });

        // Registrar CalcularFechaEstimadaUseCase (sin dependencias)
        $this->app->singleton(CalcularFechaEstimadaUseCase::class, function ($app) {
            return new CalcularFechaEstimadaUseCase();
        });

        // Registrar GetRecibosDatosUseCase (sin dependencias - resuelve internamente)
        $this->app->singleton(GetRecibosDatosUseCase::class, function ($app) {
            return new GetRecibosDatosUseCase();
        });

        // Registrar GetNovedadesUseCase (sin dependencias)
        $this->app->singleton(GetNovedadesUseCase::class, function ($app) {
            return new GetNovedadesUseCase();
        });

        // Registrar ImagenRelocalizadorService como singleton
        $this->app->singleton(ImagenRelocalizadorService::class, function ($app) {
            return new ImagenRelocalizadorService();
        });

        // ========================================
        // CONTRATOS DE INFRAESTRUCTURA (PORTS)
        // ========================================

        $this->app->bind(AuditRepositoryInterface::class, NewsAuditRepository::class);
        $this->app->bind(TransactionManagerInterface::class, EloquentTransactionManager::class);
        $this->app->bind(OrdenEventDispatcherInterface::class, BroadcastOrdenEventDispatcher::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
