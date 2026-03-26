<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\SupervisorPedidos\Repositories\OrderRepository;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;
use App\Infrastructure\Repositories\SupervisorPedidos\EloquentOrderRepository;
use App\Infrastructure\Repositories\SupervisorPedidos\EloquentReceiptRepository;
use App\Application\SupervisorPedidos\UseCases\ApproveOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\ReturnOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\ActivateSewingReceiptUseCase;
use App\Application\SupervisorPedidos\UseCases\ListPendingOrdersUseCase;
use App\Application\SupervisorPedidos\UseCases\ListOrdersUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderDetailsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingSewingReceiptsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingEmbroideryStampingReceiptsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingQualityControlReceiptsUseCase;
use App\Application\SupervisorPedidos\UseCases\UpdateProfileUseCase;
use App\Application\SupervisorPedidos\UseCases\GetComparisonDataUseCase;
use App\Application\SupervisorPedidos\UseCases\GetFilterOptionsUseCase;
use App\Application\SupervisorPedidos\UseCases\UpdateOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\GetSewingReceiptFilterOptionsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetQualityControlReceiptFilterOptionsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingOrdersCountUseCase;
use App\Application\SupervisorPedidos\UseCases\ToggleOrderVisibilityUseCase;
use App\Application\SupervisorPedidos\UseCases\DownloadOrderPdfUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderDetailsViewUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderDescriptionUseCase;
use App\Application\SupervisorPedidos\Services\OrderDescriptionBuilder;
use App\Application\Pedidos\Services\PrendaPedidoDescriptionFormatter;
use App\Application\SupervisorPedidos\UseCases\CancelSewingReceiptUseCase;
use App\Application\SupervisorPedidos\UseCases\SaveReceiptArrivalDateUseCase;
use App\Application\SupervisorPedidos\UseCases\ChangeOrderStatusUseCase;
use App\Application\SupervisorPedidos\UseCases\ApproveOrderDetailedUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderDisplayUseCase;
use App\Application\SupervisorPedidos\UseCases\GetNotificationsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetReceiptDetailsUseCase;
use App\Application\SupervisorPedidos\UseCases\ApproveReceiptUseCase;
use App\Application\SupervisorPedidos\UseCases\SaveSewingReceiptColorUseCase;
use App\Application\SupervisorPedidos\UseCases\SelectOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\DeselectOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderSelectionsUseCase;
use App\Application\SupervisorPedidos\UseCases\MarkAllNotificationsAsReadUseCase;
use App\Application\SupervisorPedidos\UseCases\DeleteImageUseCase;
use App\Application\SupervisorPedidos\UseCases\ToggleNewsVistoUseCase;
use App\Application\SupervisorPedidos\UseCases\TogglePedidoVistoUseCase;
use App\Application\SupervisorPedidos\UseCases\MarkNotificationAsReadUseCase;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;
use App\Repositories\EloquentProcesoPrendaDetalleRepository;

class SupervisorPedidosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerRepositories();
        $this->registerOrderUseCases();
        $this->registerReceiptUseCases();
        $this->registerOtherUseCases();
    }

    private function registerRepositories(): void
    {
        // Repositories - Bindings explícitos para evitar problemas de resolución
        $this->app->singleton(OrderRepository::class, function () {
            return new EloquentOrderRepository();
        });

        $this->app->singleton(ReceiptRepository::class, function () {
            return new EloquentReceiptRepository();
        });
    }

    private function registerOrderUseCases(): void
    {

        $this->app->bind(
            ApproveOrderUseCase::class,
            function ($app) {
                return new ApproveOrderUseCase(
                    $app->make(OrderRepository::class)
                );
            }
        );

        $this->app->bind(
            ReturnOrderUseCase::class,
            function ($app) {
                return new ReturnOrderUseCase(
                    $app->make(OrderRepository::class)
                );
            }
        );

        $this->app->bind(
            ListPendingOrdersUseCase::class,
            function ($app) {
                return new ListPendingOrdersUseCase(
                    $app->make(OrderRepository::class)
                );
            }
        );

        $this->app->bind(
            GetOrderDetailsUseCase::class,
            function ($app) {
                return new GetOrderDetailsUseCase(
                    $app->make(OrderRepository::class),
                    $app->make(PrendaPedidoDescriptionFormatter::class)
                );
            }
        );

        $this->app->bind(
            GetComparisonDataUseCase::class,
            function ($app) {
                return new GetComparisonDataUseCase(
                    $app->make(PrendaPedidoDescriptionFormatter::class)
                );
            }
        );

        $this->app->bind(
            ListOrdersUseCase::class,
            function ($app) {
                return new ListOrdersUseCase(
                    $app->make(PedidoProduccionReadService::class)
                );
            }
        );

        $this->app->bind(
            ChangeOrderStatusUseCase::class,
            function ($app) {
                return new ChangeOrderStatusUseCase(
                    $app->make(OrderRepository::class)
                );
            }
        );

        $this->app->bind(
            ApproveOrderDetailedUseCase::class,
            function ($app) {
                return new ApproveOrderDetailedUseCase(
                    $app->make(OrderRepository::class)
                );
            }
        );

        $this->app->bind(
            GetOrderDisplayUseCase::class,
            function ($app) {
                return new GetOrderDisplayUseCase(
                    $app->make(OrderRepository::class)
                );
            }
        );
    }

    private function registerReceiptUseCases(): void
    {
        $this->app->bind(
            ActivateSewingReceiptUseCase::class,
            function ($app) {
                return new ActivateSewingReceiptUseCase(
                    $app->make(OrderRepository::class),
                    $app->make(ReceiptRepository::class)
                );
            }
        );

        $this->app->bind(
            GetPendingSewingReceiptsUseCase::class,
            function () {
                return new GetPendingSewingReceiptsUseCase();
            }
        );

        $this->app->bind(
            GetPendingEmbroideryStampingReceiptsUseCase::class,
            function () {
                return new GetPendingEmbroideryStampingReceiptsUseCase();
            }
        );

        $this->app->bind(
            GetPendingQualityControlReceiptsUseCase::class,
            function () {
                return new GetPendingQualityControlReceiptsUseCase();
            }
        );

        $this->app->bind(
            CancelSewingReceiptUseCase::class,
            function ($app) {
                return new CancelSewingReceiptUseCase(
                    $app->make(ReceiptRepository::class)
                );
            }
        );

        $this->app->bind(
            SaveReceiptArrivalDateUseCase::class,
            function ($app) {
                return new SaveReceiptArrivalDateUseCase(
                    $app->make(ReceiptRepository::class)
                );
            }
        );

        $this->app->bind(
            GetReceiptDetailsUseCase::class,
            function ($app) {
                return new GetReceiptDetailsUseCase(
                    $app->make(ReceiptRepository::class)
                );
            }
        );

        $this->app->bind(
            ApproveReceiptUseCase::class,
            function ($app) {
                return new ApproveReceiptUseCase(
                    $app->make(ReceiptRepository::class)
                );
            }
        );

        $this->app->bind(
            SaveSewingReceiptColorUseCase::class,
            function ($app) {
                return new SaveSewingReceiptColorUseCase(
                    $app->make(ReceiptRepository::class)
                );
            }
        );
    }

    private function registerOtherUseCases(): void
    {
        $this->app->bind(
            UpdateProfileUseCase::class,
            function () {
                return new UpdateProfileUseCase();
            }
        );

        $this->app->bind(
            GetFilterOptionsUseCase::class,
            function () {
                return new GetFilterOptionsUseCase();
            }
        );

        $this->app->bind(
            UpdateOrderUseCase::class,
            function () {
                return new UpdateOrderUseCase();
            }
        );

        $this->app->bind(
            GetSewingReceiptFilterOptionsUseCase::class,
            function () {
                return new GetSewingReceiptFilterOptionsUseCase();
            }
        );

        $this->app->bind(
            GetPendingOrdersCountUseCase::class,
            function () {
                return new GetPendingOrdersCountUseCase();
            }
        );

        $this->app->bind(
            ToggleOrderVisibilityUseCase::class,
            function () {
                return new ToggleOrderVisibilityUseCase();
            }
        );

        $this->app->bind(
            DownloadOrderPdfUseCase::class,
            function () {
                return new DownloadOrderPdfUseCase();
            }
        );

        $this->app->bind(
            GetOrderDetailsViewUseCase::class,
            function () {
                return new GetOrderDetailsViewUseCase();
            }
        );

        $this->app->bind(
            OrderDescriptionBuilder::class,
            function ($app) {
                return new OrderDescriptionBuilder(
                    $app->make(EloquentProcesoPrendaDetalleRepository::class),
                    $app->make(PrendaPedidoDescriptionFormatter::class)
                );
            }
        );

        $this->app->bind(
            GetOrderDescriptionUseCase::class,
            function ($app) {
                return new GetOrderDescriptionUseCase(
                    $app->make(OrderDescriptionBuilder::class)
                );
            }
        );

        $this->app->bind(
            GetNotificationsUseCase::class,
            function ($app) {
                return new GetNotificationsUseCase(
                    $app->make(\Illuminate\Auth\AuthManager::class)
                );
            }
        );

        $this->app->bind(
            SelectOrderUseCase::class,
            function () {
                return new SelectOrderUseCase();
            }
        );

        $this->app->bind(
            DeselectOrderUseCase::class,
            function () {
                return new DeselectOrderUseCase();
            }
        );

        $this->app->bind(
            GetOrderSelectionsUseCase::class,
            function () {
                return new GetOrderSelectionsUseCase();
            }
        );

        $this->app->bind(
            MarkAllNotificationsAsReadUseCase::class,
            function () {
                return new MarkAllNotificationsAsReadUseCase();
            }
        );

        $this->app->bind(
            DeleteImageUseCase::class,
            function () {
                return new DeleteImageUseCase();
            }
        );

        $this->app->bind(
            ToggleNewsVistoUseCase::class,
            function () {
                return new ToggleNewsVistoUseCase();
            }
        );

        $this->app->bind(
            TogglePedidoVistoUseCase::class,
            function () {
                return new TogglePedidoVistoUseCase();
            }
        );

        $this->app->bind(
            MarkNotificationAsReadUseCase::class,
            function () {
                return new MarkNotificationAsReadUseCase();
            }
        );

        $this->app->bind(
            GetQualityControlReceiptFilterOptionsUseCase::class,
            function () {
                return new GetQualityControlReceiptFilterOptionsUseCase();
            }
        );
    }

    public function boot(): void
    {
        // Boot logic here if needed
    }
}
