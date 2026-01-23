<?php

namespace App\Infrastructure\Pedidos\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Infrastructure\Pedidos\Persistence\Eloquent\PedidoRepositoryImpl;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase;
use App\Application\Pedidos\Listeners\PedidoCreadoListener;
use App\Domain\Pedidos\Events\PedidoCreado;

/**
 * Service Provider: Pedidos
 * 
 * Registra bindings de DI para el módulo de Pedidos
 */
class PedidoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PedidoRepository::class,
            PedidoRepositoryImpl::class
        );

        $this->app->bind(
            CrearPedidoUseCase::class,
            fn($app) => new CrearPedidoUseCase(
                $app->make(PedidoRepository::class)
            )
        );

        $this->app->bind(
            ConfirmarPedidoUseCase::class,
            fn($app) => new ConfirmarPedidoUseCase(
                $app->make(PedidoRepository::class)
            )
        );
    }

    public function boot(): void
    {
        $this->app['events']->listen(
            PedidoCreado::class,
            PedidoCreadoListener::class
        );
    }
}
