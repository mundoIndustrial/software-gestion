<?php

namespace App\Modules\Pedidos\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class PedidosServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Cargar rutas del módulo
        $this->loadRoutesFrom(__DIR__ . '/../../Routes/api.php');
    }
}
