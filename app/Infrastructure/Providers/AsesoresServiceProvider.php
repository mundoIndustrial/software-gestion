<?php

namespace App\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

// Services (Legacy - mantener temporal)
use App\Application\Services\Asesores\DashboardService;
use App\Application\Services\Asesores\NotificacionesService;
use App\Application\Services\Asesores\PerfilService;

// Repositories
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;

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
use App\Application\Pedidos\UseCases\ObtenerEstadisticasDashboardUseCase;
use App\Application\Pedidos\UseCases\ObtenerDatosGraficasDashboardUseCase;
use App\Application\Pedidos\UseCases\ObtenerNotificacionesUseCase;
use App\Application\Pedidos\UseCases\MarcarNotificacionLeidaUseCase;
use App\Application\Pedidos\UseCases\ObtenerPerfilAsesorUseCase;
use App\Application\Pedidos\UseCases\ActualizarPerfilAsesorUseCase;
use App\Application\Asesores\UseCases\ObtenerNotasPedidoUseCase;
use App\Application\Asesores\UseCases\ContarPendientesAsesorUseCase;
use App\Application\Asesores\UseCases\ObtenerPendientesAsesorUseCase;
use App\Application\Asesores\UseCases\ObtenerDatosCotizacionEditarUseCase;
use App\Application\Asesores\UseCases\GuardarPedidoUseCase;
use App\Application\Services\Asesores\GuardarPedidoLogoService;
use App\Application\Services\Asesores\ProcesarFotosTelasService;

// Infrastructure Services (Image Mapping - DDD Refactored)
use App\Infrastructure\Services\Pedidos\ImagenesService;
use App\Infrastructure\Mappers\Imagenes\PrendaImagenesMapper;
use App\Infrastructure\Mappers\Imagenes\TelaImagenesMapper;
use App\Infrastructure\Mappers\Imagenes\ImagenDTOToPrendaArrayMapper;
use App\Infrastructure\Mappers\Imagenes\ImagenDTOToTelaArrayMapper;
use App\Application\Services\ColorTelaService;

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
        $this->app->singleton(PedidoProduccionRepository::class);

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

        // ===== INFRASTRUCTURE SERVICES (DDD - Image Mapping) =====
        
        $this->app->singleton(ImagenDTOToPrendaArrayMapper::class, function ($app) {
            return new ImagenDTOToPrendaArrayMapper();
        });

        $this->app->singleton(ImagenDTOToTelaArrayMapper::class, function ($app) {
            return new ImagenDTOToTelaArrayMapper();
        });

        $this->app->singleton(PrendaImagenesMapper::class, function ($app) {
            return new PrendaImagenesMapper(
                $app->make(ImagenDTOToPrendaArrayMapper::class)
            );
        });

        $this->app->singleton(TelaImagenesMapper::class, function ($app) {
            return new TelaImagenesMapper(
                $app->make(ImagenDTOToTelaArrayMapper::class),
                $app->make(ColorTelaService::class)
            );
        });

        $this->app->singleton(ImagenesService::class, function ($app) {
            return new ImagenesService(
                $app->make(PrendaImagenesMapper::class),
                $app->make(TelaImagenesMapper::class)
            );
        });

        // ===== USE CASES (DDD - NUEVA ARQUITECTURA) =====
        
        // Crear Pedido
        $this->app->singleton(CrearProduccionPedidoUseCase::class, function ($app) {
            return new CrearProduccionPedidoUseCase(
                $app->make(PedidoRepository::class)
            );
        });

        // Confirmar Pedido
        $this->app->singleton(ConfirmarProduccionPedidoUseCase::class, function ($app) {
            return new ConfirmarProduccionPedidoUseCase(
                $app->make(PedidoRepository::class)
            );
        });

        // Actualizar Pedido
        $this->app->singleton(ActualizarProduccionPedidoUseCase::class, function ($app) {
            return new ActualizarProduccionPedidoUseCase(
                $app->make(PedidoRepository::class)
            );
        });

        // Anular Pedido
        $this->app->singleton(AnularProduccionPedidoUseCase::class, function ($app) {
            return new AnularProduccionPedidoUseCase(
                $app->make(PedidoRepository::class)
            );
        });

        // Obtener Pedido
        $this->app->singleton(ObtenerProduccionPedidoUseCase::class, function ($app) {
            return new ObtenerProduccionPedidoUseCase(
                $app->make(PedidoRepository::class)
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
                $app->make(PedidoRepository::class)
            );
        });

        // Obtener Próximo Número Pedido
        $this->app->singleton(ObtenerProximoNumeroPedidoUseCase::class, function ($app) {
            return new ObtenerProximoNumeroPedidoUseCase(
                $app->make(PedidoRepository::class)
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

        // ===== USE CASES PRESENTACIÓN (DDD - NUEVA ARQUITECTURA) =====

        // Obtener Estadísticas Dashboard
        $this->app->singleton(ObtenerEstadisticasDashboardUseCase::class, function ($app) {
            return new ObtenerEstadisticasDashboardUseCase(
                $app->make(DashboardService::class)
            );
        });

        // Obtener Datos Gráficas Dashboard
        $this->app->singleton(ObtenerDatosGraficasDashboardUseCase::class, function ($app) {
            return new ObtenerDatosGraficasDashboardUseCase(
                $app->make(DashboardService::class)
            );
        });

        // Obtener Notificaciones
        $this->app->singleton(ObtenerNotificacionesUseCase::class, function ($app) {
            return new ObtenerNotificacionesUseCase(
                $app->make(NotificacionesService::class)
            );
        });

        // Marcar Notificación Leída
        $this->app->singleton(MarcarNotificacionLeidaUseCase::class, function ($app) {
            return new MarcarNotificacionLeidaUseCase(
                $app->make(NotificacionesService::class)
            );
        });

        // Obtener Perfil Asesor
        $this->app->singleton(ObtenerPerfilAsesorUseCase::class, function ($app) {
            return new ObtenerPerfilAsesorUseCase();
        });

        // Actualizar Perfil Asesor
        $this->app->singleton(ActualizarPerfilAsesorUseCase::class, function ($app) {
            return new ActualizarPerfilAsesorUseCase(
                $app->make(PerfilService::class)
            );
        });

        // Registrar servicios que GuardarPedidoUseCase necesita
        $this->app->singleton(GuardarPedidoLogoService::class, function ($app) {
            return new GuardarPedidoLogoService();
        });

        $this->app->singleton(ProcesarFotosTelasService::class, function ($app) {
            return new ProcesarFotosTelasService();
        });

        // Guardar Pedido (con transacción)
        $this->app->singleton(GuardarPedidoUseCase::class, function ($app) {
            return new GuardarPedidoUseCase(
                $app->make(CrearProduccionPedidoUseCase::class),
                $app->make(GuardarPedidoLogoService::class),
                $app->make(ProcesarFotosTelasService::class)
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
