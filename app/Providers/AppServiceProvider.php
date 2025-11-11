<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\TablaOriginal;
use App\Models\TablaOriginalBodega;
use App\Observers\TablaOriginalObserver;
use App\Observers\TablaOriginalBodegaObserver;

class AppServiceProvider extends ServiceProvider
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
        // Registrar el Observer para TablaOriginal (Pedidos)
        // Esto sincroniza automáticamente los cambios en 'descripcion' y 'cliente'
        // del padre hacia los registros hijos en 'registros_por_orden'
        TablaOriginal::observe(TablaOriginalObserver::class);

        // Registrar el Observer para TablaOriginalBodega (Bodega)
        // Esto sincroniza automáticamente los cambios en 'descripcion' y 'cliente'
        // del padre hacia los registros hijos en 'registros_por_orden_bodega'
        TablaOriginalBodega::observe(TablaOriginalBodegaObserver::class);
    }
}
