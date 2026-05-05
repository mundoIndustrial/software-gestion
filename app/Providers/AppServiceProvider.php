<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\ConsecutivoReciboPedido;
use App\Models\TablaOriginalBodega;
use App\Models\BodegaDetalleTalla;
use App\Models\ProcesoPrenda;
use App\Models\PedidoProduccion;
use App\Models\User;
use App\Models\PrendaPedido;
use App\Models\Cotizacion;
use App\Observers\TablaOriginalBodegaObserver;
use App\Observers\BodegaDetalleTallaObserver;
use App\Observers\PrendaPedidoUpdateReciboObserver;
use App\Observers\ProcesoPrendaObserver;
use App\Observers\PedidoProduccionObserver;
use App\Observers\PrendaPedidoObserver;
use App\Domain\Operario\Repositories\OperarioRepository;
use App\Infrastructure\Persistence\Eloquent\OperarioRepositoryImpl;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Domain\Operario\Repositories\ReciboNotificacionesRepository;
use App\Domain\Operario\Repositories\NovedadReciboRepository;
use App\Domain\Operario\Repositories\PedidoProduccionOperarioReadRepository;
use App\Domain\Operario\Repositories\ReciboParcialReadRepository;
use App\Domain\Operario\Repositories\PedidoProduccionNovedadesRepository;
use App\Domain\Operario\Repositories\TablaOriginalBodegaNovedadesRepository;
use App\Domain\Operario\Repositories\ReciboDistribucionReadRepository;
use App\Domain\Operario\Services\ControlCalidadWorkflow;
use App\Domain\Operario\Services\OperarioDashboardReadService;
use App\Domain\Operario\Services\OperarioPedidosReadService;
use App\Domain\Operario\Services\OperarioPrendasRecibosReadService;
use App\Domain\Operario\Services\ReciboOperarioWorkflow;
use App\Domain\Operario\Services\PedidoFotosReadService;
use App\Domain\Bodega\Services\BodegaAuditoriaServiceContract;
use App\Domain\Bodega\Services\BodegaDatosServiceContract;
use App\Domain\Bodega\Services\BodegaFiltroServiceContract;
use App\Domain\Bodega\Services\BodegaGuardadoServiceContract;
use App\Domain\Bodega\Services\BodegaNotaServiceContract;
use App\Domain\Bodega\Services\BodegaNotificacionServiceContract;
use App\Domain\Bodega\Services\BodegaPedidoServiceContract;
use App\Domain\Bodega\Services\BodegaRepositoryContract;
use App\Domain\Bodega\Services\BodegaUpdateServiceContract;
use App\Domain\Bodega\Services\PedidoEstadoCalculatorContract;
use App\Domain\Pedidos\Services\PedidoDetalleReadService;
use App\Domain\Pedidos\Services\CaracteristicasPrendaCatalogServiceContract;
use App\Domain\Pedidos\Services\EppTransformadorServiceContract;
use App\Domain\Pedidos\Services\FacturaPedidoServiceContract;
use App\Domain\Pedidos\Services\PrendaPedidoQuantityCalculatorContract;
use App\Domain\Pedidos\Services\PrendaTransformadorServiceContract;
use App\Domain\Pedidos\Services\PrendaTransformerServiceContract;
use App\Domain\Pedidos\Services\ReciboPedidoServiceContract;
use App\Domain\Pedidos\Despacho\Services\DespachoEstadoServiceContract;
use App\Domain\Pedidos\Despacho\Services\DespachoValidadorServiceContract;
use App\Domain\Pedidos\CommandHandlers\ActualizarVariantePrendaHandlerContract;
use App\Domain\Pedidos\CommandHandlers\CrearPedidoCompletoHandlerContract;
use App\Domain\Pedidos\UseCases\ActualizarPrendaCompletaUseCaseContract;
use App\Domain\Pedidos\UseCases\ActualizarPrendaPedidoUseCaseContract;
use App\Domain\Pedidos\UseCases\CrearProcesoUseCaseContract;
use App\Domain\Pedidos\UseCases\EliminarEppUseCaseContract;
use App\Domain\Pedidos\UseCases\HomologarEppUseCaseContract;
use App\Domain\Pedidos\UseCases\EliminarPrendaPedidoUseCaseContract;
use App\Domain\Pedidos\UseCases\EliminarProcesoUseCaseContract;
use App\Domain\Pedidos\Despacho\UseCases\GuardarDespachoUseCaseContract;
use App\Infrastructure\Persistence\Eloquent\ConsecutivoReciboPedidoRepositoryImpl;
use App\Infrastructure\Persistence\Eloquent\ReciboNotificacionesRepositoryImpl;
use App\Infrastructure\Persistence\Eloquent\ProcesoPrendaRepositoryImpl;
use App\Infrastructure\Persistence\Eloquent\NovedadReciboRepositoryImpl;
use App\Infrastructure\Persistence\Eloquent\PedidoProduccionOperarioReadRepositoryImpl;
use App\Infrastructure\Persistence\Eloquent\ReciboParcialReadRepositoryImpl;
use App\Infrastructure\Persistence\Eloquent\PedidoProduccionNovedadesRepositoryImpl;
use App\Infrastructure\Persistence\Eloquent\TablaOriginalBodegaNovedadesRepositoryImpl;
use App\Infrastructure\Persistence\Eloquent\ReciboDistribucionReadRepositoryImpl;
use App\Infrastructure\Services\Operario\ControlCalidadWorkflowService;
use App\Infrastructure\Services\Operario\OperarioDashboardReadServiceImpl;
use App\Infrastructure\Services\Operario\ReciboOperarioWorkflowService;
use App\Infrastructure\Services\Operario\PedidoFotosReadServiceImpl;
use App\Infrastructure\Providers\AsesoresServiceProvider;
use App\Infrastructure\Providers\PedidosLogoServiceProvider;
use App\Infrastructure\Providers\PedidosProduccionServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerCoreProviders();
        $this->registerOperarioBindings();
        $this->registerBodegaAndPedidoServiceBindings();
        $this->registerPedidoUseCaseBindings();
        $this->registerProcesoAndReciboBindings();
        $this->registerEppAndCotizacionBindings();
        $this->registerSharedInfrastructureBindings();
        $this->registerDomainRepositoryBindings();
    }

    private function registerCoreProviders(): void
    {
        $this->app->register(AsesoresServiceProvider::class);
        $this->app->register(PedidosLogoServiceProvider::class);
        $this->app->register(PedidosProduccionServiceProvider::class);
    }

    private function registerOperarioBindings(): void
    {
        $this->app->bind(OperarioRepository::class, OperarioRepositoryImpl::class);
        $this->app->bind(ConsecutivoReciboPedidoRepository::class, ConsecutivoReciboPedidoRepositoryImpl::class);
        $this->app->bind(ProcesoPrendaRepository::class, ProcesoPrendaRepositoryImpl::class);
        $this->app->bind(ReciboNotificacionesRepository::class, ReciboNotificacionesRepositoryImpl::class);
        $this->app->bind(NovedadReciboRepository::class, NovedadReciboRepositoryImpl::class);
        $this->app->bind(PedidoProduccionOperarioReadRepository::class, PedidoProduccionOperarioReadRepositoryImpl::class);
        $this->app->bind(ReciboParcialReadRepository::class, ReciboParcialReadRepositoryImpl::class);
        $this->app->bind(PedidoProduccionNovedadesRepository::class, PedidoProduccionNovedadesRepositoryImpl::class);
        $this->app->bind(TablaOriginalBodegaNovedadesRepository::class, TablaOriginalBodegaNovedadesRepositoryImpl::class);
        $this->app->bind(ReciboDistribucionReadRepository::class, ReciboDistribucionReadRepositoryImpl::class);
        $this->app->bind(ControlCalidadWorkflow::class, ControlCalidadWorkflowService::class);
        $this->app->bind(OperarioPedidosReadService::class, \App\Infrastructure\Services\Operario\ObtenerPedidosOperarioService::class);
        $this->app->bind(OperarioPrendasRecibosReadService::class, \App\Infrastructure\Services\Operario\ObtenerPrendasRecibosService::class);
        $this->app->bind(OperarioDashboardReadService::class, OperarioDashboardReadServiceImpl::class);
        $this->app->bind(ReciboOperarioWorkflow::class, ReciboOperarioWorkflowService::class);
        $this->app->bind(PedidoFotosReadService::class, PedidoFotosReadServiceImpl::class);
    }

    private function registerBodegaAndPedidoServiceBindings(): void
    {
        $this->app->bind(BodegaAuditoriaServiceContract::class, \App\Infrastructure\Services\Bodega\BodegaAuditoriaService::class);
        $this->app->bind(BodegaDatosServiceContract::class, \App\Infrastructure\Services\Bodega\BodegaDatosService::class);
        $this->app->bind(BodegaFiltroServiceContract::class, \App\Infrastructure\Services\Bodega\BodegaFiltroService::class);
        $this->app->bind(BodegaGuardadoServiceContract::class, \App\Infrastructure\Services\Bodega\BodegaGuardadoService::class);
        $this->app->bind(BodegaNotaServiceContract::class, \App\Infrastructure\Services\Bodega\BodegaNotaService::class);
        $this->app->bind(BodegaNotificacionServiceContract::class, \App\Infrastructure\Services\Bodega\BodegaNotificacionService::class);
        $this->app->bind(BodegaPedidoServiceContract::class, \App\Infrastructure\Services\Bodega\BodegaPedidoService::class);
        $this->app->bind(BodegaRepositoryContract::class, \App\Infrastructure\Services\Bodega\BodegaRepository::class);
        $this->app->bind(BodegaUpdateServiceContract::class, \App\Infrastructure\Services\Bodega\BodegaUpdateService::class);
        $this->app->bind(PedidoEstadoCalculatorContract::class, \App\Infrastructure\Services\Bodega\PedidoEstadoCalculator::class);
        $this->app->bind(PedidoDetalleReadService::class, \App\Infrastructure\Services\Pedidos\PedidoDetalleReadServiceImpl::class);
        $this->app->bind(\App\Domain\Pedidos\Validators\PedidoValidatorContract::class, \App\Infrastructure\Validators\Pedidos\PedidoValidator::class);
        $this->app->bind(CaracteristicasPrendaCatalogServiceContract::class, \App\Infrastructure\Services\Pedidos\CaracteristicasPrendaCatalogService::class);
        $this->app->bind(\App\Domain\Pedidos\Services\ColorTelaCatalogServiceContract::class, \App\Infrastructure\Services\Pedidos\ColorTelaCatalogService::class);
        $this->app->bind(EppTransformadorServiceContract::class, \App\Infrastructure\Services\Pedidos\EppTransformadorService::class);
        $this->app->bind(FacturaPedidoServiceContract::class, \App\Infrastructure\Services\Pedidos\FacturaPedidoService::class);
        $this->app->bind(PrendaPedidoQuantityCalculatorContract::class, \App\Infrastructure\Services\Pedidos\PrendaPedidoQuantityCalculator::class);
        $this->app->bind(PrendaTransformadorServiceContract::class, \App\Infrastructure\Services\Pedidos\PrendaTransformadorService::class);
        $this->app->bind(PrendaTransformerServiceContract::class, \App\Infrastructure\Services\Pedidos\PrendaTransformerService::class);
        $this->app->bind(ReciboPedidoServiceContract::class, \App\Infrastructure\Services\Pedidos\ReciboPedidoService::class);
        $this->app->bind(DespachoEstadoServiceContract::class, \App\Infrastructure\Services\Pedidos\DespachoEstadoService::class);
        $this->app->bind(DespachoValidadorServiceContract::class, \App\Infrastructure\Services\Pedidos\DespachoValidadorService::class);
    }

    private function registerPedidoUseCaseBindings(): void
    {
        $this->app->bind(ActualizarPrendaCompletaUseCaseContract::class, \App\Application\Pedidos\UseCases\ActualizarPrendaCompletaUseCase::class);
        $this->app->bind(ActualizarPrendaPedidoUseCaseContract::class, \App\Application\Pedidos\UseCases\ActualizarPrendaPedidoUseCase::class);
        $this->app->bind(CrearProcesoUseCaseContract::class, \App\Application\Pedidos\UseCases\CrearProcesoUseCase::class);
        $this->app->bind(EliminarEppUseCaseContract::class, \App\Application\Pedidos\UseCases\EliminarEppUseCase::class);
        $this->app->bind(HomologarEppUseCaseContract::class, \App\Application\Pedidos\UseCases\HomologarEppUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\AgregarPrendaCompletaUseCaseContract::class, \App\Application\Pedidos\UseCases\AgregarPrendaCompletaUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\CrearProduccionPedidoUseCaseContract::class, \App\Application\Pedidos\UseCases\CrearProduccionPedidoUseCase::class);
        $this->app->bind(EliminarPrendaPedidoUseCaseContract::class, \App\Application\Pedidos\UseCases\EliminarPrendaPedidoUseCase::class);
        $this->app->bind(EliminarProcesoUseCaseContract::class, \App\Application\Pedidos\UseCases\EliminarProcesoUseCase::class);
        $this->app->bind(GuardarDespachoUseCaseContract::class, \App\Application\Pedidos\Despacho\UseCases\GuardarDespachoUseCase::class);
        $this->app->bind(ActualizarVariantePrendaHandlerContract::class, \App\Infrastructure\CommandHandlers\Pedidos\ActualizarVariantePrendaHandler::class);
        $this->app->bind(CrearPedidoCompletoHandlerContract::class, \App\Infrastructure\CommandHandlers\Pedidos\CrearPedidoCompletoHandler::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\AgregarColorTelaUseCaseContract::class, \App\Application\Pedidos\UseCases\AgregarColorTelaUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\AgregarEppUseCaseContract::class, \App\Application\Pedidos\UseCases\AgregarEppUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\AgregarImagenEppUseCaseContract::class, \App\Application\Pedidos\UseCases\AgregarImagenEppUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\AgregarImagenProcesoUseCaseContract::class, \App\Application\Pedidos\UseCases\AgregarImagenProcesoUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\AgregarImagenTelaUseCaseContract::class, \App\Application\Pedidos\UseCases\AgregarImagenTelaUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\AgregarProcesoPrendaUseCaseContract::class, \App\Application\Pedidos\UseCases\AgregarProcesoPrendaUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\AgregarTallaPrendaUseCaseContract::class, \App\Application\Pedidos\UseCases\AgregarTallaPrendaUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\AgregarTallaProcesoPrendaUseCaseContract::class, \App\Application\Pedidos\UseCases\AgregarTallaProcesoPrendaUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\CalcularFechaEntregaEstimadaUseCaseContract::class, \App\Application\Pedidos\UseCases\CalcularFechaEntregaEstimadaUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\CambiarEstadoPedidoUseCaseContract::class, \App\Application\Pedidos\UseCases\CambiarEstadoPedidoUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\EditarProcesoUseCaseContract::class, \App\Application\Pedidos\UseCases\EditarProcesoUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\EliminarImagenPedidoUseCaseContract::class, \App\Application\Pedidos\UseCases\EliminarImagenPedidoUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\EliminarProcesosListaUseCaseContract::class, \App\Application\Pedidos\UseCases\EliminarProcesosListaUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\ObtenerAnchoMetrajePrendaUseCaseContract::class, \App\Application\Pedidos\UseCases\ObtenerAnchoMetrajePrendaUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\ObtenerCotizacionesUseCaseContract::class, \App\Application\Pedidos\UseCases\ObtenerCotizacionesUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\ObtenerDatosEdicionUseCaseContract::class, \App\Application\Pedidos\UseCases\ObtenerDatosEdicionUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\ObtenerDatosParaCrearPedidoUseCaseContract::class, \App\Application\Pedidos\UseCases\ObtenerDatosParaCrearPedidoUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\ObtenerProcesosPorPedidoUseCaseContract::class, \App\Application\Pedidos\UseCases\ObtenerProcesosPorPedidoUseCase::class);
        $this->app->bind(\App\Domain\Pedidos\UseCases\PrepararCreacionProduccionPedidoUseCaseContract::class, \App\Application\Pedidos\UseCases\PrepararCreacionProduccionPedidoUseCase::class);
    }

    private function registerProcesoAndReciboBindings(): void
    {
        $this->app->bind(\App\Domain\Procesos\Repositories\TipoProcesoRepository::class, \App\Repositories\EloquentTipoProcesoRepository::class);
        $this->app->bind(\App\Domain\Procesos\Repositories\ProcesoPrendaDetalleRepository::class, \App\Repositories\EloquentProcesoPrendaDetalleRepository::class);
        $this->app->bind(\App\Domain\Procesos\Repositories\ProcesoPrendaImagenRepository::class, \App\Repositories\EloquentProcesoPrendaImagenRepository::class);
        $this->app->singleton(\App\Repositories\PrendaPedidoTallaRepository::class, function () {
            return new \App\Repositories\PrendaPedidoTallaRepository();
        });
        $this->app->singleton(\App\Repositories\ConsecutivoReciboPedidoRepository::class, function () {
            return new \App\Repositories\ConsecutivoReciboPedidoRepository();
        });
        $this->app->singleton(\App\Application\Services\DiaLaboralCalculator::class, function () {
            return new \App\Application\Services\DiaLaboralCalculator();
        });
        $this->app->singleton(\App\Application\Services\CantidadCalculator::class, function ($app) {
            return new \App\Application\Services\CantidadCalculator(
                $app->make(\App\Repositories\PrendaPedidoTallaRepository::class)
            );
        });
        $this->app->singleton(\App\Application\Services\ReceiptEnricherService::class, function ($app) {
            return new \App\Application\Services\ReceiptEnricherService(
                $app->make(\App\Application\Services\CantidadCalculator::class)
            );
        });
    }

    private function registerEppAndCotizacionBindings(): void
    {
        $this->app->bind(\App\Domain\Epp\Repositories\EppRepositoryInterface::class, \App\Domain\Epp\Repositories\EppRepository::class);
        $this->app->bind(\App\Domain\Epp\Repositories\PedidoEppRepositoryInterface::class, \App\Domain\Epp\Repositories\PedidoEppRepository::class);
        $this->app->singleton(\App\Domain\Epp\Services\EppDomainService::class, function ($app) {
            return new \App\Domain\Epp\Services\EppDomainService(
                $app->make(\App\Domain\Epp\Repositories\EppRepositoryInterface::class)
            );
        });
        $this->app->singleton(\App\Application\Cotizacion\Services\GenerarNumeroCotizacionService::class, function () {
            return new \App\Application\Cotizacion\Services\GenerarNumeroCotizacionService();
        });
        $this->app->singleton('image', function () {
            return new \Intervention\Image\ImageManager(
                new \Intervention\Image\Drivers\Gd\Driver()
            );
        });
    }

    private function registerSharedInfrastructureBindings(): void
    {
        $this->app->bind(\App\Application\Shared\Contracts\AuditRepositoryInterface::class, \App\Infrastructure\Services\NewsAuditRepository::class);
        $this->app->bind(\App\Application\Shared\Contracts\TransactionManagerInterface::class, \App\Infrastructure\Services\EloquentTransactionManager::class);
        $this->app->bind(\App\Application\Shared\Contracts\OrdenEventDispatcherInterface::class, \App\Infrastructure\Services\BroadcastOrdenEventDispatcher::class);
    }

    private function registerDomainRepositoryBindings(): void
    {
        $this->app->bind(
            \App\Domain\Pedidos\Repositories\PrendaPedidoReadRepository::class,
            \App\Infrastructure\Pedidos\Persistence\Eloquent\PrendaPedidoReadRepositoryImpl::class
        );
        $this->app->bind(
            \App\Domain\Pedidos\Repositories\PrendaPedidoTallaReadRepository::class,
            \App\Infrastructure\Pedidos\Persistence\Eloquent\PrendaPedidoTallaReadRepositoryImpl::class
        );
        $this->app->bind(
            \App\Domain\Pedidos\Repositories\EliminarPrendaPedidoRepository::class,
            \App\Infrastructure\Pedidos\Persistence\Eloquent\EliminarPrendaPedidoRepositoryImpl::class
        );
        $this->app->bind(
            \App\Domain\InventarioTelas\Repositories\InventarioTelaRepositoryInterface::class,
            \App\Infrastructure\Repositories\InventarioTelas\InventarioTelaRepository::class
        );
        $this->app->bind(
            \App\Domain\BodegaNota\Repositories\BodegaNotaRepositoryInterface::class,
            \App\Infrastructure\Repositories\Bodega\BodegaNotaRepository::class
        );
        $this->app->bind(
            \App\Domain\BodegaDetalleTalla\Repositories\BodegaDetalleTallaRepositoryInterface::class,
            \App\Infrastructure\Repositories\Bodega\BodegaDetalleTallaRepository::class
        );
        $this->app->bind(
            \App\Domain\Cotizacion\Repositories\CotizacionDetalleRepositoryInterface::class,
            \App\Infrastructure\Repositories\Cotizacion\CotizacionDetalleRepository::class
        );
    }
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Cmixin\BusinessDay::enable('Carbon\Carbon', 'co-national');

        // Registrar helper global para versioning de assets
        if (!function_exists('asset_with_version')) {
            function asset_with_version(string $path): string {
                return \App\Helpers\AssetVersionHelper::asset_with_version($path);
            }
        }

        // Blade directive: @jsDefer('js/path/file.js')
        // En producción carga .min.js, en desarrollo el original
        Blade::directive('jsDefer', function (string $expression) {
            return "<script defer src=\"<?php echo js_asset({$expression}); ?>?v=<?php echo config('app.asset_version'); ?>\"></script>";
        });

        // Los Observers de TablaOriginal han sido eliminados
        // La sincronización ocurre automáticamente a través de PedidoProduccion
        // y sus relaciones con PrendaPedido y ProcesoPrenda.

        // Registrar el Observer para TablaOriginalBodega (Bodega)
        // Esto sincroniza automáticamente los cambios en 'descripcion' y 'cliente'
        // del padre hacia los registros hijos en 'registros_por_orden_bodega'
        // TablaOriginalBodega::observe(TablaOriginalBodegaObserver::class);

        // Registrar Observer para ProcesoPrenda
        // Actualiza automáticamente el campo 'area' en pedidos_produccion
        // cada vez que se crea o modifica un proceso
        ProcesoPrenda::observe(ProcesoPrendaObserver::class);

        // Registrar Observer para BodegaDetalleTalla
        // Captura automáticamente la fecha cuando se cambia el estado a "Pendiente"
        BodegaDetalleTalla::observe(BodegaDetalleTallaObserver::class);

        // Registrar Observer para PedidoProduccion
        // Crea notificaciones cuando se asigna la fecha estimada de entrega
        PedidoProduccion::observe(PedidoProduccionObserver::class);

        // Registrar Observer para PrendaPedido
        PrendaPedido::observe(PrendaPedidoObserver::class);

        // Registrar Observer para PrendaPedido que actualiza recibos
        // Actualiza ultima_actividad en todos los recibos del pedido cuando cambia una prenda
        PrendaPedido::observe(PrendaPedidoUpdateReciboObserver::class);

        // View Composer para el sidebar del contador
        View::composer('contador.sidebar', function ($view) {
            $cotizacionesAprobadas = Cotizacion::where('estado', 'APROBADA_POR_APROBADOR')
                ->where('es_borrador', 0)
                ->get();
            $cotizacionesRechazadas = Cotizacion::where('estado', 'EN_CORRECCION')
                ->where('es_borrador', 0)
                ->get();
            $view->with('cotizacionesAprobadas', $cotizacionesAprobadas);
            $view->with('cotizacionesRechazadas', $cotizacionesRechazadas);
        });

        View::composer(['layouts.sidebar', 'components.sidebars.sidebar-asesores'], function ($view) {
            $badgeCount = 0;
            $user = Auth::user();
            if ($user && $user->hasRole('asesor')) {
                $badgeCount = ConsecutivoReciboPedido::query()
                    ->where('activo', 1)
                    ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', ['COSTURA'])
                    ->whereRaw("UPPER(REPLACE(TRIM(COALESCE(estado, '')), ' ', '_')) IN (?, ?)", [
                        'DEVUELTO_ASESOR',
                        'DEVUELTO_A_ASESOR',
                    ])
                    ->whereHas('pedido', static function ($pedidoQuery) use ($user) {
                        $pedidoQuery->where('asesor_id', $user->id);
                    })
                    ->count();
            }

            $view->with('revisarPrendaBadgeCount', (int) $badgeCount);
        });

        // View Composer para el sidebar de Despacho (Asesoras)
        View::composer(['components.sidebars.sidebar-despacho', 'despacho.index'], function ($view) {
            $states = ['Pendiente', 'En Ejecucion', 'No iniciado', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'DEVUELTO_A_ASESORA', 'pendiente_cartera', 'RECHAZADO_CARTERA'];
            
            $asesores = User::whereHas('pedidosAsesora', function ($q) use ($states) {
                $q->whereIn('estado', $states)
                  ->whereNotNull('numero_pedido')
                  ->where('numero_pedido', '!=', '');
            })->withCount(['pedidosAsesora' => function ($q) use ($states) {
                $q->whereIn('estado', $states)
                  ->whereNotNull('numero_pedido')
                  ->where('numero_pedido', '!=', '');
            }])->get(['id', 'name']);

            $view->with('sidebarAsesores', $asesores);
            $view->with('currentAsesorId', request('asesor_id'));
        });
    }
}
