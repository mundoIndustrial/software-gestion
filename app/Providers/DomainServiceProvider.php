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
use App\Infrastructure\Pedidos\Persistence\Eloquent\PedidoRepositoryImpl;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Application\Pedidos\UseCases\ListarPedidosPorClienteUseCase;
use App\Application\Pedidos\UseCases\CancelarPedidoUseCase;
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
use App\Application\Services\Asesores\DashboardService;
use App\Application\Services\Asesores\NotificacionesService;
use App\Application\Services\Asesores\PerfilService;
use App\Application\Services\Asesores\EliminarPedidoService;
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

        // Registrar Use Cases como singletons
        $this->app->singleton(CrearPedidoUseCase::class, function ($app) {
            return new CrearPedidoUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(ConfirmarPedidoUseCase::class, function ($app) {
            return new ConfirmarPedidoUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(ObtenerPedidoUseCase::class, function ($app) {
            return new ObtenerPedidoUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(ListarPedidosPorClienteUseCase::class, function ($app) {
            return new ListarPedidosPorClienteUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(CancelarPedidoUseCase::class, function ($app) {
            return new CancelarPedidoUseCase($app->make(PedidoRepository::class));
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

        // Registrar Use Cases como singletons
        $this->app->singleton(CrearPedidoUseCase::class, function ($app) {
            return new CrearPedidoUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(ConfirmarPedidoUseCase::class, function ($app) {
            return new ConfirmarPedidoUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(ObtenerPedidoUseCase::class, function ($app) {
            return new ObtenerPedidoUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(ListarPedidosPorClienteUseCase::class, function ($app) {
            return new ListarPedidosPorClienteUseCase($app->make(PedidoRepository::class));
        });

        $this->app->singleton(CancelarPedidoUseCase::class, function ($app) {
            return new CancelarPedidoUseCase($app->make(PedidoRepository::class));
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
