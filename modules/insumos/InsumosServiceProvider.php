<?php

namespace Modules\Insumos;

use Illuminate\Support\ServiceProvider;
use Modules\Insumos\Backend\Repositories\MaterialesRepository;
use Modules\Insumos\Backend\Services\MaterialesService;

/**
 * Service Provider para el módulo Insumos
 * 
 * Registra y bootstrapea todos los componentes del módulo
 */
class InsumosServiceProvider extends ServiceProvider
{
    /**
     * Ruta base del módulo
     */
    protected $modulePath = __DIR__;

    /**
     * Register services
     */
    public function register()
    {
        // Cargar configuración del módulo
        $this->mergeConfigFrom(
            $this->modulePath . '/config.php',
            'insumos'
        );

        // Registrar Repository
        $this->app->singleton(MaterialesRepository::class, function () {
            return new MaterialesRepository();
        });

        // Registrar Service
        $this->app->singleton(MaterialesService::class, function ($app) {
            return new MaterialesService($app->make(MaterialesRepository::class));
        });
    }

    /**
     * Bootstrap services
     */
    public function boot()
    {
        // Publicar configuración
        $this->publishes([
            $this->modulePath . '/config.php' => config_path('insumos.php'),
        ], 'insumos-config');

        // Registrar vistas
        $this->loadViewsFrom(
            $this->modulePath . '/frontend/views',
            'insumos'
        );

        // Registrar componentes
        $this->loadViewComponentsAs('insumos', [
            // Los componentes se cargarán aquí
        ]);

        // Cargar rutas del módulo
        $this->loadRoutesFrom($this->modulePath . '/backend/Routes/web.php');
    }

    /**
     * Get the services provided by the provider
     */
    public function provides()
    {
        return [
            MaterialesRepository::class,
            MaterialesService::class,
        ];
    }
}
