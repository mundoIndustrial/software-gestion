<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// ====== Application Layer (UseCases) ======
use App\Application\UseCases\Orders\{
    CreateOrderUseCase,
    UpdateOrderUseCase,
    DeleteOrderUseCase,
    GetOrderUseCase,
    EditFullOrderUseCase,
    AddNovedadUseCase,
    SaveDiaEntregaUseCase
};
use App\Application\UseCases\Receipts\GetSewingReceiptsUseCase;
use App\Application\Services\ReceiptEnricherService;
use App\Application\Services\CantidadCalculator;
use App\Application\Services\DiaLaboralCalculator;
use App\Repositories\ConsecutivoReciboPedidoRepository;
use App\Repositories\PrendaPedidoTallaRepository;

// ====== Infrastructure Layer (Services) ======
use App\Infrastructure\QueryServices\OrderQueryService;
use App\Domain\Services\OrderCalculationService;
use App\Domain\Services\OrderFilteringService;

// ====== Existing Services =======
use App\Services\{
    RegistroOrdenValidationService,
    RegistroOrdenCreationService,
    RegistroOrdenUpdateService,
    RegistroOrdenDeletionService,
    RegistroOrdenNumberService,
    RegistroOrdenPrendaService,
    RegistroOrdenCacheService,
    RegistroOrdenEntregasService,
    FestivosColombiaService,
    ReciboCosturaQueryService
};

/**
 * DDD Service Provider
 * 
 * Registra todos los UseCases y Domain Services
 * para inyección de dependencias
 */
class DDDServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerDomainServices();
        $this->registerApplicationUseCases();
        $this->registerInfrastructureServices();
    }

    /**
     * Registrar Domain Services
     */
    private function registerDomainServices(): void
    {
        // Singleton: OrderCalculationService
        $this->app->singleton(OrderCalculationService::class, function ($app) {
            return new OrderCalculationService(
                $app->make(FestivosColombiaService::class)
            );
        });

        // Singleton: OrderFilteringService
        $this->app->singleton(OrderFilteringService::class, function ($app) {
            return new OrderFilteringService();
        });
    }

    /**
     * Registrar Application UseCases
     */
    private function registerApplicationUseCases(): void
    {
        // CreateOrderUseCase
        $this->app->bind(CreateOrderUseCase::class, function ($app) {
            return new CreateOrderUseCase(
                $app->make(RegistroOrdenValidationService::class),
                $app->make(RegistroOrdenCreationService::class),
            );
        });

        // UpdateOrderUseCase
        $this->app->bind(UpdateOrderUseCase::class, function ($app) {
            return new UpdateOrderUseCase(
                $app->make(RegistroOrdenValidationService::class),
                $app->make(RegistroOrdenUpdateService::class),
            );
        });

        // DeleteOrderUseCase
        $this->app->bind(DeleteOrderUseCase::class, function ($app) {
            return new DeleteOrderUseCase(
                $app->make(RegistroOrdenDeletionService::class),
            );
        });

        // GetOrderUseCase (no requiere dependencias)
        $this->app->bind(GetOrderUseCase::class, function ($app) {
            return new GetOrderUseCase();
        });

        // EditFullOrderUseCase
        $this->app->bind(EditFullOrderUseCase::class, function ($app) {
            return new EditFullOrderUseCase(
                $app->make(RegistroOrdenValidationService::class),
                $app->make(RegistroOrdenPrendaService::class),
                $app->make(RegistroOrdenCacheService::class),
            );
        });

        // AddNovedadUseCase (no requiere dependencias)
        $this->app->bind(AddNovedadUseCase::class, function ($app) {
            return new AddNovedadUseCase();
        });

        // SaveDiaEntregaUseCase
        $this->app->bind(SaveDiaEntregaUseCase::class, function ($app) {
            return new SaveDiaEntregaUseCase(
                $app->make(OrderCalculationService::class),
            );
        });

        // GetSewingReceiptsUseCase
        $this->app->bind(CantidadCalculator::class, function ($app) {
            return new CantidadCalculator(
                $app->make(PrendaPedidoTallaRepository::class),
            );
        });

        $this->app->bind(ReceiptEnricherService::class, function ($app) {
            return new ReceiptEnricherService(
                $app->make(DiaLaboralCalculator::class),
                $app->make(CantidadCalculator::class),
            );
        });

        $this->app->bind(GetSewingReceiptsUseCase::class, function ($app) {
            return new GetSewingReceiptsUseCase(
                $app->make(ConsecutivoReciboPedidoRepository::class),
                $app->make(ReceiptEnricherService::class),
            );
        });
    }

    /**
     * Registrar Infrastructure Services
     */
    private function registerInfrastructureServices(): void
    {
        // OrderQueryService (Singleton)
        $this->app->singleton(OrderQueryService::class, function ($app) {
            return new OrderQueryService();
        });

        // Nota: ReciboCosturaQueryService ya está registrado
        // en app/Services si es necesario
    }
}
