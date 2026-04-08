<?php

namespace Tests\Feature\CQRS;

use App\Application\Pedidos\CommandHandlers\AgregarPrendaAlPedidoHandler;
use App\Application\Pedidos\QueryHandlers\BuscarPedidoPorNumeroHandler;
use App\Application\Pedidos\CommandHandlers\CambiarEstadoPedidoHandler;
use App\Application\Pedidos\CommandHandlers\CrearPedidoCompletoHandler;
use App\Application\Pedidos\CommandHandlers\CrearPedidoHandler;
use App\Domain\Shared\CQRS\CommandBus;
use App\Domain\Shared\CQRS\QueryBus;
use App\Application\Pedidos\QueryHandlers\FiltrarPedidosPorEstadoHandler;
use App\Application\Pedidos\QueryHandlers\ListarPedidosHandler;
use App\Application\Pedidos\QueryHandlers\ObtenerPedidoHandler;
use Tests\TestCase;

class PedidosCommandBusResolutionTest extends TestCase
{
    public function test_command_bus_y_handlers_de_pedidos_resuelven_desde_application(): void
    {
        $commandBus = app(CommandBus::class);

        $this->assertInstanceOf(CommandBus::class, $commandBus);
        $this->assertInstanceOf(CrearPedidoHandler::class, app(CrearPedidoHandler::class));
        $this->assertInstanceOf(CrearPedidoCompletoHandler::class, app(CrearPedidoCompletoHandler::class));
        $this->assertInstanceOf(AgregarPrendaAlPedidoHandler::class, app(AgregarPrendaAlPedidoHandler::class));
        $this->assertInstanceOf(CambiarEstadoPedidoHandler::class, app(CambiarEstadoPedidoHandler::class));
    }

    public function test_query_bus_y_handlers_de_pedidos_resuelven_desde_application(): void
    {
        $queryBus = app(QueryBus::class);

        $this->assertInstanceOf(QueryBus::class, $queryBus);
        $this->assertInstanceOf(ObtenerPedidoHandler::class, app(ObtenerPedidoHandler::class));
        $this->assertInstanceOf(ListarPedidosHandler::class, app(ListarPedidosHandler::class));
        $this->assertInstanceOf(FiltrarPedidosPorEstadoHandler::class, app(FiltrarPedidosPorEstadoHandler::class));
        $this->assertInstanceOf(BuscarPedidoPorNumeroHandler::class, app(BuscarPedidoPorNumeroHandler::class));
    }
}
