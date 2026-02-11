<?php

namespace App\Providers;

use App\Domain\Pedidos\Services\PrendaTransformadorService;
use App\Domain\Pedidos\Services\TallaProcessorService;
use App\Domain\Pedidos\Services\VariacionProcessorService;
use App\Domain\Pedidos\Services\ProcesoProcessorService;
use App\Application\Pedidos\Services\PrendaEditorService;
use App\Domain\Pedidos\Repositories\PrendaRepositoryInterface;
use App\Domain\Pedidos\Repositories\CotizacionRepositoryInterface;
use App\Infrastructure\Repositories\EloquentPrendaRepository;
use App\Infrastructure\Repositories\EloquentCotizacionRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider para el módulo de edición de prendas
 * 
 * Registra todos los servicios y repositorios relacionados con la edición
 * de prendas siguiendo la arquitectura DDD.
 */
class PrendaEditorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        // Domain Services
        $this->app->singleton(TallaProcessorService::class, function ($app) {
            return new TallaProcessorService();
        });
        
        $this->app->singleton(VariacionProcessorService::class, function ($app) {
            return new VariacionProcessorService();
        });
        
        $this->app->singleton(ProcesoProcessorService::class, function ($app) {
            return new ProcesoProcessorService();
        });
        
        $this->app->singleton(PrendaTransformadorService::class, function ($app) {
            return new PrendaTransformadorService(
                $app->make(TallaProcessorService::class),
                $app->make(VariacionProcessorService::class),
                $app->make(ProcesoProcessorService::class)
            );
        });
        
        // Application Services
        $this->app->singleton(PrendaEditorService::class, function ($app) {
            return new PrendaEditorService(
                $app->make(PrendaRepositoryInterface::class),
                $app->make(CotizacionRepositoryInterface::class),
                $app->make(PrendaTransformadorService::class)
            );
        });
        
        // Repository Interfaces
        $this->app->bind(PrendaRepositoryInterface::class, EloquentPrendaRepository::class);
        $this->app->bind(CotizacionRepositoryInterface::class, EloquentCotizacionRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Publicar configuraciones si es necesario
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/prenda-editor.php' => config_path('prenda-editor.php'),
            ], 'prenda-editor-config');
        }
        
        // Cargar rutas específicas del módulo
        $this->loadRoutesFrom(__DIR__.'/../routes/prenda-editor.php');
        
        // Cargar vistas si es necesario
        $this->loadViewsFrom(__DIR__.'/../resources/views/prenda-editor', 'prenda-editor');
    }
    
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            PrendaTransformadorService::class,
            PrendaEditorService::class,
            PrendaRepositoryInterface::class,
            CotizacionRepositoryInterface::class,
        ];
    }
}
