<?php

namespace App\Providers;

use App\Application\Bodega\Services\BodegaPedidoService;
use App\Application\Bodega\Services\BodegaRoleService;
use App\Application\Bodega\Services\BodegaNotaService;
use App\Application\Bodega\Services\BodegaAuditoriaService;
use Illuminate\Support\ServiceProvider;

class BodegaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // BodegaPedidoService - Lógica principal de negocio
        $this->app->singleton(BodegaPedidoService::class, function ($app) {
            return new BodegaPedidoService(
                $app->make(\App\Application\Pedidos\UseCases\ObtenerPedidoUseCase::class),
                $app->make(\App\Domain\Pedidos\Repositories\PedidoProduccionRepository::class),
                $app->make(BodegaRoleService::class),
                $app->make(\App\Application\Bodega\Services\BodegaRepository::class)
            );
        });

        // BodegaRoleService - Gestión de roles y permisos
        $this->app->singleton(BodegaRoleService::class, function ($app) {
            return new BodegaRoleService();
        });

        // BodegaNotaService - Gestión de notas
        $this->app->singleton(BodegaNotaService::class, function ($app) {
            return new BodegaNotaService(
                $app->make(BodegaRoleService::class)
            );
        });

        // BodegaAuditoriaService - Lógica de auditoría
        $this->app->singleton(BodegaAuditoriaService::class, function ($app) {
            return new BodegaAuditoriaService(
                $app->make(BodegaRoleService::class)
            );
        });

        // BodegaRepository - Consultas de base de datos
        $this->app->singleton(\App\Application\Bodega\Services\BodegaRepository::class, function ($app) {
            return new \App\Application\Bodega\Services\BodegaRepository();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publicar configuraciones si es necesario
        // $this->publishes([
        //     __DIR__.'/../config/bodega.php' => config_path('bodega.php'),
        // ], 'bodega-config');
    }
}
