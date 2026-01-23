<?php

namespace App\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

// Services (Legacy - mantener temporal)
use App\Application\Services\Asesores\DashboardService;
use App\Application\Services\Asesores\NotificacionesService;
use App\Application\Services\Asesores\PerfilService;

// Repositories
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use App\Infrastructure\Persistence\EloquentPedidoProduccionRepository;

// Use Cases (DDD)
use App\Application\Pedidos\UseCases\CrearProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ActualizarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\AnularProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ListarProduccionPedidosUseCase;
use App\Application\Pedidos\UseCases\PrepararCreacionProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\AgregarPrendaSimpleUseCase;
use App\Application\Pedidos\UseCases\ObtenerProximoNumeroPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerFacturaUseCase;
use App\Application\Pedidos\UseCases\ObtenerRecibosUseCase;

/**
 * AsesoresServiceProvider
 * 
 * Registra todos los servicios y dependencias para el módulo de Asesores
 * 
 * Responsabilidades:
 * - Inyectar repositorios en Use Cases
 * - Registrar servicios legacy (gradualmente siendo reemplazados)
 * - Centralizar configuración de dependencias
 * 
 * Beneficios:
 * - Constructor limpio (12 parámetros vs 23 anteriores)
 * - Fácil testing (inyección clara)
 * - Fácil mantenimiento (cambios centralizados)
 * - Explícito (se ve qué depende de qué)
 */
class AsesoresServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // ===== REPOSITORIES =====
        $this->app->singleton(
            PedidoProduccionRepository::class,
            EloquentPedidoProduccionRepository::class
        );

        // ===== LEGACY SERVICES (Gradualmente siendo eliminados) =====
        $this->app->singleton(DashboardService::class, function ($app) {
            return new DashboardService();
        });

        $this->app->singleton(NotificacionesService::class, function ($app) {
            return new NotificacionesService();
        });

        $this->app->singleton(PerfilService::class, function ($app) {
            return new PerfilService();
        });

        // ===== USE CASES (DDD - NUEVA ARQUITECTURA) =====
        
        // Crear Pedido
        $this->app->singleton(CrearProduccionPedidoUseCase::class, function ($app) {
            return new CrearProduccionPedidoUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Confirmar Pedido
        $this->app->singleton(ConfirmarProduccionPedidoUseCase::class, function ($app) {
            return new ConfirmarProduccionPedidoUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Actualizar Pedido
        $this->app->singleton(ActualizarProduccionPedidoUseCase::class, function ($app) {
            return new ActualizarProduccionPedidoUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Anular Pedido
        $this->app->singleton(AnularProduccionPedidoUseCase::class, function ($app) {
            return new AnularProduccionPedidoUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Obtener Pedido
        $this->app->singleton(ObtenerProduccionPedidoUseCase::class, function ($app) {
            return new ObtenerProduccionPedidoUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Listar Pedidos
        $this->app->singleton(ListarProduccionPedidosUseCase::class, function ($app) {
            return new ListarProduccionPedidosUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Preparar Creación Pedido
        $this->app->singleton(PrepararCreacionProduccionPedidoUseCase::class, function ($app) {
            return new PrepararCreacionProduccionPedidoUseCase();
        });

        // Agregar Prenda Simple
        $this->app->singleton(AgregarPrendaSimpleUseCase::class, function ($app) {
            return new AgregarPrendaSimpleUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Obtener Próximo Número Pedido
        $this->app->singleton(ObtenerProximoNumeroPedidoUseCase::class, function ($app) {
            return new ObtenerProximoNumeroPedidoUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Obtener Factura
        $this->app->singleton(ObtenerFacturaUseCase::class, function ($app) {
            return new ObtenerFacturaUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Obtener Recibos
        $this->app->singleton(ObtenerRecibosUseCase::class, function ($app) {
            return new ObtenerRecibosUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Aquí irían eventos, listeners, etc.
    }
}
