<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Ordenes\Repositories\OrdenRepositoryInterface;
use App\Repositories\EloquentOrdenRepository;
use App\Domain\Ordenes\Services\CrearOrdenService;
use App\Domain\Ordenes\Services\ActualizarOrdenService;
use App\Domain\Ordenes\Services\CancelarOrdenService;
use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentCotizacionRepository;
use App\Application\Cotizacion\Handlers\Commands\CrearCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\EliminarCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\CambiarEstadoCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\AceptarCotizacionHandler;
use App\Application\Cotizacion\Handlers\Commands\SubirImagenCotizacionHandler;
use App\Application\Cotizacion\Handlers\Queries\ListarCotizacionesHandler;
use App\Application\Cotizacion\Handlers\Queries\ObtenerCotizacionHandler;
use App\Infrastructure\Storage\ImagenAlmacenador;
use Intervention\Image\ImageManager;

// ======================================== DDD PEDIDOS
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Domain\Pedidos\Repositories\ProcesoPedidoWriteRepository;
use App\Domain\Pedidos\Services\PedidoDetalleReadService;
use App\Infrastructure\Pedidos\Persistence\Eloquent\EloquentPedidoProduccionRepository;
use App\Infrastructure\Pedidos\Persistence\Eloquent\PedidoRepositoryImpl;
use App\Infrastructure\Pedidos\Persistence\Eloquent\ProcesoPedidoWriteRepositoryImpl;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Application\Pedidos\UseCases\ListarPedidosPorClienteUseCase;
use App\Application\Pedidos\UseCases\ActualizarDescripcionPedidoUseCase;
use App\Application\Pedidos\UseCases\IniciarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\CompletarPedidoUseCase;
use App\Application\Pedidos\UseCases\AgregarItemPedidoUseCase;
use App\Application\Pedidos\UseCases\EliminarItemPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerItemsPedidoUseCase;
use App\Application\Pedidos\UseCases\GuardarPedidoDesdeJSONUseCase;
use App\Application\Pedidos\UseCases\ValidarPedidoDesdeJSONUseCase;
use App\Application\Pedidos\UseCases\ObtenerProcesosPorPedidoUseCase;
use App\Application\Pedidos\UseCases\EditarProcesoUseCase;
use App\Application\Pedidos\UseCases\EliminarProcesoUseCase;
use App\Application\Pedidos\UseCases\CrearProcesoUseCase;
use App\Application\Pedidos\UseCases\ObtenerHistorialProcesosUseCase;

// ======================================== ASESORES
use App\Application\Services\Asesores\AsesoresApplicationFacadeService;

// ======================================== INTERFACES PEDIDOS (Fase 14)
use App\Application\Services\Pedidos\Contracts\ObtenerItemsServiceInterface;
use App\Application\Services\Pedidos\Contracts\PrepararCrearPedidoServiceInterface;
use App\Application\Services\Pedidos\Contracts\CargarDatosCompartidosServiceInterface;
use App\Application\Services\Pedidos\ObtenerItemsEppCotizacionService;
use App\Application\Services\Pedidos\PrepararCrearPedidoNuevoService;
use App\Application\Services\Pedidos\CargarDatosCompartidosService;

use App\Application\Services\Asesores\DashboardService;
use App\Application\Services\Asesores\NotificacionesService;
use App\Application\Services\Asesores\PerfilService;
use App\Application\Services\Asesores\EliminarPedidoService;

// ======================================== PROCESO SEGUIMIENTO
use App\Domain\ProcesoSeguimiento\Repositories\ProcesoPrendaSeguimientoRepository;
use App\Domain\ProcesoSeguimiento\Repositories\ConsecutivoReciboPedidoRepository;
use App\Infrastructure\ProcesoSeguimiento\Persistence\Eloquent\EloquentProcesoPrendaSeguimientoRepository;
use App\Infrastructure\ProcesoSeguimiento\Persistence\Eloquent\EloquentConsecutivoReciboPedidoRepository;
use App\Application\ProcesoSeguimiento\Services\ProcesoSeguimientoBroadcastService;
use App\Application\ProcesoSeguimiento\UseCases\GuardarProcesoSeguimientoUseCase;
use App\Application\ProcesoSeguimiento\UseCases\ActualizarProcesoSeguimientoUseCase;
use App\Application\ProcesoSeguimiento\UseCases\ActualizarEstadoProcesoUseCase;
use App\Application\ProcesoSeguimiento\UseCases\EliminarProcesoSeguimientoUseCase;
use App\Application\Services\Asesores\ObtenerFotosService;
use App\Application\Services\Asesores\AnularPedidoService;
use App\Application\Services\Asesores\ObtenerPedidosService;
use App\Application\Services\Asesores\ObtenerProximoPedidoService;
use App\Application\Services\Asesores\ObtenerDatosFacturaService;
use App\Application\Services\Asesores\ObtenerDatosRecibosService;
use App\Application\Services\Asesores\ProcesarFotosTelasService;
use App\Application\Services\Asesores\GuardarPedidoLogoService;
use App\Application\Services\Asesores\GuardarPedidoProduccionService;
use App\Application\Services\Asesores\ConfirmarPedidoService;
use App\Application\Services\Asesores\ActualizarPedidoService;
use App\Application\Services\Asesores\ObtenerPedidoDetalleService;

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
        // ========================================
        // ORDENES - Registrar Repository Interface con implementación Eloquent
        // ========================================
        $this->app->bind(
            OrdenRepositoryInterface::class,
            EloquentOrdenRepository::class
        );

        // ========================================
        // COTIZACIONES - Registrar Repository y Handlers
        // ========================================
        // Registrar Repository
        $this->app->singleton(
            CotizacionRepositoryInterface::class,
            EloquentCotizacionRepository::class
        );

        // Registrar Command Handlers
        $this->app->singleton(CrearCotizacionHandler::class);
        $this->app->singleton(EliminarCotizacionHandler::class);
        $this->app->singleton(CambiarEstadoCotizacionHandler::class);
        $this->app->singleton(AceptarCotizacionHandler::class);
        $this->app->singleton(SubirImagenCotizacionHandler::class);

        // Registrar Query Handlers
        $this->app->singleton(ObtenerCotizacionHandler::class);
        $this->app->singleton(ListarCotizacionesHandler::class);

        // Registrar Servicios de Storage
        $this->app->singleton(ImagenAlmacenador::class, function () {
            return new ImagenAlmacenador(ImageManager::gd());
        });

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

        // ========================================
        // PEDIDOS DDD (Fase 3+)
        // ========================================
        // Registrar Repository Interface con implementación
        $this->app->bind(
            PedidoRepository::class,
            PedidoRepositoryImpl::class
        );

        $this->app->bind(
            PedidoProduccionReadRepository::class,
            EloquentPedidoProduccionRepository::class
        );

        $this->app->bind(
            \App\Domain\Pedidos\Repositories\ProcesoPedidoReadRepository::class,
            \App\Infrastructure\Pedidos\Persistence\Eloquent\ProcesoPedidoReadRepositoryImpl::class
        );

        $this->app->bind(
            ProcesoPedidoWriteRepository::class,
            ProcesoPedidoWriteRepositoryImpl::class
        );

        // Registrar Use Cases como singletons
        $this->app->singleton(CrearPedidoUseCase::class, function ($app) {
            return new CrearPedidoUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(ConfirmarPedidoUseCase::class, function ($app) {
            return new ConfirmarPedidoUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(ObtenerPedidoUseCase::class, function ($app) {
            return new ObtenerPedidoUseCase(
                $app->make(PedidoRepository::class),
                $app->make(PedidoDetalleReadService::class)
            );
        });

        $this->app->singleton(ListarPedidosPorClienteUseCase::class, function ($app) {
            return new ListarPedidosPorClienteUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(ActualizarDescripcionPedidoUseCase::class, function ($app) {
            return new ActualizarDescripcionPedidoUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(IniciarProduccionPedidoUseCase::class, function ($app) {
            return new IniciarProduccionPedidoUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(CompletarPedidoUseCase::class, function ($app) {
            return new CompletarPedidoUseCase($app->make(PedidoRepository::class));
        });

        // ========================================
        // PEDIDOS - Items (Editable, Agregar, Eliminar)
        // ========================================
        $this->app->singleton(AgregarItemPedidoUseCase::class);
        $this->app->singleton(EliminarItemPedidoUseCase::class);
        $this->app->singleton(ObtenerItemsPedidoUseCase::class);

        // ========================================
        // PEDIDOS - JSON (Guardar y Validar desde JSON)
        // ========================================
        $this->app->singleton(GuardarPedidoDesdeJSONUseCase::class);
        $this->app->singleton(ValidarPedidoDesdeJSONUseCase::class);

        // ========================================
        // PEDIDOS - PROCESOS (Procesos de Producción)
        // ========================================
        $this->app->singleton(ObtenerProcesosPorPedidoUseCase::class);
        $this->app->singleton(EditarProcesoUseCase::class);
        $this->app->singleton(EliminarProcesoUseCase::class);
        $this->app->singleton(CrearProcesoUseCase::class);
        $this->app->singleton(ObtenerHistorialProcesosUseCase::class);

        // ========================================
        // ASESORES - Facade Service Pattern
        // ========================================
        // Registrar todos los servicios individuales como singletons
        $this->app->singleton(DashboardService::class);
        $this->app->singleton(NotificacionesService::class);
        $this->app->singleton(PerfilService::class);
        $this->app->singleton(EliminarPedidoService::class);
        $this->app->singleton(ObtenerFotosService::class);
        $this->app->singleton(AnularPedidoService::class);
        $this->app->singleton(ObtenerPedidosService::class);
        $this->app->singleton(ObtenerProximoPedidoService::class);
        $this->app->singleton(ObtenerDatosFacturaService::class);
        $this->app->singleton(ObtenerDatosRecibosService::class);
        $this->app->singleton(ProcesarFotosTelasService::class);
        $this->app->singleton(GuardarPedidoLogoService::class);
        $this->app->singleton(GuardarPedidoProduccionService::class);
        $this->app->singleton(ConfirmarPedidoService::class);
        $this->app->singleton(ActualizarPedidoService::class);
        $this->app->singleton(ObtenerPedidoDetalleService::class);

        // Registrar Facade Service que agrupa todos los servicios
        $this->app->singleton(AsesoresApplicationFacadeService::class, function ($app) {
            return new AsesoresApplicationFacadeService(
                $app->make(DashboardService::class),
                $app->make(NotificacionesService::class),
                $app->make(PerfilService::class),
                $app->make(EliminarPedidoService::class),
                $app->make(ObtenerFotosService::class),
                $app->make(AnularPedidoService::class),
                $app->make(ObtenerPedidosService::class),
                $app->make(ObtenerProximoPedidoService::class),
                $app->make(ObtenerDatosFacturaService::class),
                $app->make(ObtenerDatosRecibosService::class),
                $app->make(ProcesarFotosTelasService::class),
                $app->make(GuardarPedidoLogoService::class),
                $app->make(GuardarPedidoProduccionService::class),
                $app->make(ConfirmarPedidoService::class),
                $app->make(ActualizarPedidoService::class),
                $app->make(ObtenerPedidoDetalleService::class),
            );
        });

        // ========================================
        // PEDIDOS EDITABLE - Interfaces → Implementaciones (Fase 14)
        // ========================================
        $this->app->bind(
            ObtenerItemsServiceInterface::class,
            ObtenerItemsEppCotizacionService::class
        );

        $this->app->bind(
            PrepararCrearPedidoServiceInterface::class,
            PrepararCrearPedidoNuevoService::class
        );

        $this->app->bind(
            CargarDatosCompartidosServiceInterface::class,
            CargarDatosCompartidosService::class
        );

        // ========================================
        // PROCESO SEGUIMIENTO - Repos + Use Cases
        // ========================================
        $this->app->singleton(
            ProcesoPrendaSeguimientoRepository::class,
            EloquentProcesoPrendaSeguimientoRepository::class
        );

        $this->app->singleton(
            ConsecutivoReciboPedidoRepository::class,
            EloquentConsecutivoReciboPedidoRepository::class
        );

        $this->app->singleton(ProcesoSeguimientoBroadcastService::class);
        $this->app->singleton(GuardarProcesoSeguimientoUseCase::class);
        $this->app->singleton(ActualizarProcesoSeguimientoUseCase::class);
        $this->app->singleton(ActualizarEstadoProcesoUseCase::class);
        $this->app->singleton(EliminarProcesoSeguimientoUseCase::class);

        // ========================================
        // INSUMOS DDD - Repositories + Use Cases
        // ========================================
        $this->app->bind(
            \App\Domain\Insumos\Repositories\MaterialesReadRepository::class,
            \App\Infrastructure\Insumos\Persistence\Eloquent\EloquentMaterialesReadRepository::class
        );

        $this->app->bind(
            \App\Domain\Insumos\Repositories\MaterialesWriteRepository::class,
            \App\Infrastructure\Insumos\Persistence\Eloquent\EloquentMaterialesWriteRepository::class
        );

        $this->app->singleton(\App\Application\Insumos\UseCases\ObtenerMaterialesPedidoUseCase::class);
        $this->app->singleton(\App\Application\Insumos\UseCases\GuardarMaterialesDetalladosUseCase::class);
        $this->app->singleton(\App\Application\Insumos\UseCases\EliminarMaterialPorNombreUseCase::class);
        $this->app->singleton(\App\Application\Insumos\UseCases\GuardarObservacionesMaterialUseCase::class);
        $this->app->singleton(\App\Application\Insumos\UseCases\ObtenerPrendasPedidoInsumosUseCase::class);
        $this->app->singleton(\App\Application\Insumos\UseCases\ObtenerReciboPrendaInsumosUseCase::class);
        $this->app->singleton(\App\Application\Insumos\UseCases\ObtenerOpcionesFiltroInsumosUseCase::class);
        $this->app->singleton(\App\Application\Insumos\UseCases\MarcarNotificacionesInsumosLeidasUseCase::class);

        $this->app->bind(
            \App\Domain\Insumos\Repositories\RecibosPendientesRepository::class,
            \App\Infrastructure\Insumos\Persistence\Eloquent\EloquentRecibosPendientesRepository::class
        );

        $this->app->bind(
            \App\Domain\Insumos\Repositories\PedidoWorkflowRepository::class,
            \App\Infrastructure\Insumos\Persistence\Eloquent\EloquentPedidoWorkflowRepository::class
        );

        $this->app->bind(
            \App\Domain\Insumos\Repositories\PrendaMaterialMetricsRepository::class,
            \App\Infrastructure\Insumos\Persistence\Eloquent\EloquentPrendaMaterialMetricsRepository::class
        );

        $this->app->singleton(\App\Application\Insumos\UseCases\CambiarEstadoReciboInsumosUseCase::class);
        $this->app->singleton(\App\Application\Insumos\UseCases\CambiarEstadoPedidoInsumosUseCase::class);
        $this->app->singleton(\App\Application\Insumos\UseCases\ObtenerResumenRecibosPendientesInsumosUseCase::class);
        $this->app->singleton(\App\Application\Insumos\UseCases\ObtenerRecibosCosturaPendientesInsumosUseCase::class);
        $this->app->singleton(\App\Application\Insumos\UseCases\MarcarReciboVistoInsumosUseCase::class);
        $this->app->singleton(\App\Application\Insumos\UseCases\ObtenerColoresPrendaInsumosUseCase::class);
        $this->app->singleton(\App\Application\Insumos\UseCases\ObtenerAnchoMetrajePrendaInsumosUseCase::class);
        $this->app->singleton(\App\Application\Insumos\UseCases\GuardarAnchoMetrajePrendaInsumosUseCase::class);
        $this->app->singleton(\App\Application\Insumos\UseCases\EliminarAnchoMetrajePrendaInsumosUseCase::class);
    }

    public function boot(): void
    {
        // Registrar event listeners para Domain Events
        $this->registerDomainEventListeners();
    }

    private function registerDomainEventListeners(): void
    {
        // Domain Events de Pedidos - Fase 14
        // Los listeners concretos se agregan aquí conforme se implementen.
        // Ejemplo:
        // Event::listen(PedidoCreatedEvent::class, NotificarPedidoCreadoListener::class);
        // Event::listen(PedidoValidatedEvent::class, LogValidacionListener::class);
        // Event::listen(ItemsObtuvieronEvent::class, AuditItemsListener::class);
    }
}
