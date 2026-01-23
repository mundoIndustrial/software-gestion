<?php

namespace App\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

// Use Cases (DDD)
use App\Application\Pedidos\UseCases\ListarProduccionPedidosUseCase;
use App\Application\Pedidos\UseCases\ObtenerProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\CrearProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ActualizarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\AnularProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\CambiarEstadoPedidoUseCase;
use App\Application\Pedidos\UseCases\AgregarPrendaAlPedidoUseCase;
use App\Application\Pedidos\UseCases\FiltrarPedidosPorEstadoUseCase;
use App\Application\Pedidos\UseCases\BuscarPedidoPorNumeroUseCase;
use App\Application\Pedidos\UseCases\ObtenerPrendasPedidoUseCase;
use App\Application\Pedidos\UseCases\ActualizarPrendaPedidoUseCase;
use App\Application\Pedidos\UseCases\AgregarPrendaCompletaUseCase;
use App\Application\Pedidos\UseCases\ActualizarPrendaCompletaUseCase;
use App\Application\Pedidos\UseCases\RenderItemCardUseCase;
use App\Application\Pedidos\UseCases\AgregarVariantePrendaUseCase;
use App\Application\Pedidos\UseCases\AgregarColorTelaUseCase;
use App\Application\Pedidos\UseCases\AgregarTallaPrendaUseCase;
use App\Application\Pedidos\UseCases\AgregarProcesoPrendaUseCase;
use App\Application\Pedidos\UseCases\AgregarEppUseCase;
use App\Application\Pedidos\UseCases\AgregarTallaProcesoPrendaUseCase;
use App\Application\Pedidos\UseCases\AgregarImagenProcesoUseCase;
use App\Application\Pedidos\UseCases\AgregarImagenEppUseCase;
use App\Application\Pedidos\UseCases\AgregarImagenTelaUseCase;

// Repositories
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use App\Domain\Shared\CQRS\QueryBus;
use App\Domain\Shared\CQRS\CommandBus;

/**
 * PedidosProduccionServiceProvider
 * 
 * Registra todos los servicios y dependencias para PedidosProduccionController
 * 
 * Responsabilidades:
 * - Inyectar Use Cases en el controlador
 * - Centralizar configuración de dependencias
 * 
 * Beneficios:
 * - Constructor limpio
 * - Fácil testing (inyección clara)
 * - Fácil mantenimiento (cambios centralizados)
 */
class PedidosProduccionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // ===== USE CASES (DDD) =====

        // Listar Pedidos
        $this->app->singleton(ListarProduccionPedidosUseCase::class, function ($app) {
            return new ListarProduccionPedidosUseCase(
                $app->make(PedidoProduccionRepository::class)
            );
        });

        // Obtener Pedido
        $this->app->singleton(ObtenerProduccionPedidoUseCase::class, function ($app) {
            return new ObtenerProduccionPedidoUseCase(
                $app->make(PedidoRepository::class)
            );
        });

        // Crear Pedido
        $this->app->singleton(CrearProduccionPedidoUseCase::class, function ($app) {
            return new CrearProduccionPedidoUseCase(
                $app->make(PedidoRepository::class),
                $app->make('events')
            );
        });

        // Actualizar Pedido
        $this->app->singleton(ActualizarProduccionPedidoUseCase::class, function ($app) {
            return new ActualizarProduccionPedidoUseCase(
                $app->make(PedidoRepository::class),
                $app->make('events')
            );
        });

        // Anular Pedido
        $this->app->singleton(AnularProduccionPedidoUseCase::class, function ($app) {
            return new AnularProduccionPedidoUseCase(
                $app->make(PedidoRepository::class)
            );
        });

        // Cambiar Estado de Pedido
        $this->app->singleton(CambiarEstadoPedidoUseCase::class, function ($app) {
            return new CambiarEstadoPedidoUseCase(
                $app->make(PedidoRepository::class)
            );
        });

        // Agregar Prenda al Pedido
        $this->app->singleton(AgregarPrendaAlPedidoUseCase::class, function ($app) {
            return new AgregarPrendaAlPedidoUseCase(
                $app->make(PedidoRepository::class)
            );
        });

        // Filtrar Pedidos por Estado
        $this->app->singleton(FiltrarPedidosPorEstadoUseCase::class, function ($app) {
            return new FiltrarPedidosPorEstadoUseCase(
                $app->make(PedidoRepository::class)
            );
        });

        // Buscar Pedido por Número
        $this->app->singleton(BuscarPedidoPorNumeroUseCase::class, function ($app) {
            return new BuscarPedidoPorNumeroUseCase(
                $app->make(PedidoRepository::class)
            );
        });

        // Obtener Prendas de Pedido
        $this->app->singleton(ObtenerPrendasPedidoUseCase::class, function ($app) {
            return new ObtenerPrendasPedidoUseCase(
                $app->make(PedidoRepository::class)
            );
        });

        // Actualizar Prenda de Pedido
        $this->app->singleton(ActualizarPrendaPedidoUseCase::class, function ($app) {
            return new ActualizarPrendaPedidoUseCase(
                $app->make(PedidoRepository::class)
            );
        });

        // Agregar Prenda Completa
        $this->app->singleton(AgregarPrendaCompletaUseCase::class, function ($app) {
            return new AgregarPrendaCompletaUseCase(
                $app->make(PedidoRepository::class)
            );
        });

        // Actualizar Prenda Completa
        $this->app->singleton(ActualizarPrendaCompletaUseCase::class, function ($app) {
            return new ActualizarPrendaCompletaUseCase(
                $app->make(PedidoRepository::class)
            );
        });

        // Render Item Card
        $this->app->singleton(RenderItemCardUseCase::class, function ($app) {
            return new RenderItemCardUseCase();
        });

        // Agregar Variante a Prenda
        $this->app->singleton(AgregarVariantePrendaUseCase::class, function ($app) {
            return new AgregarVariantePrendaUseCase();
        });

        // Agregar Color-Tela a Prenda
        $this->app->singleton(AgregarColorTelaUseCase::class, function ($app) {
            return new AgregarColorTelaUseCase();
        });

        // Agregar Talla a Prenda
        $this->app->singleton(AgregarTallaPrendaUseCase::class, function ($app) {
            return new AgregarTallaPrendaUseCase();
        });

        // Agregar Proceso a Prenda
        $this->app->singleton(AgregarProcesoPrendaUseCase::class, function ($app) {
            return new AgregarProcesoPrendaUseCase();
        });

        // Agregar EPP al Pedido
        $this->app->singleton(AgregarEppUseCase::class, function ($app) {
            return new AgregarEppUseCase();
        });

        // Agregar Talla a Proceso de Prenda
        $this->app->singleton(AgregarTallaProcesoPrendaUseCase::class, function ($app) {
            return new AgregarTallaProcesoPrendaUseCase();
        });

        // Agregar Imagen a Proceso
        $this->app->singleton(AgregarImagenProcesoUseCase::class, function ($app) {
            return new AgregarImagenProcesoUseCase();
        });

        // Agregar Imagen a EPP
        $this->app->singleton(AgregarImagenEppUseCase::class, function ($app) {
            return new AgregarImagenEppUseCase();
        });

        // Agregar Imagen a Tela
        $this->app->singleton(AgregarImagenTelaUseCase::class, function ($app) {
            return new AgregarImagenTelaUseCase();
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
