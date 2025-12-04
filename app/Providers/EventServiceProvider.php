<?php

namespace App\Providers;

use App\Events\PedidoCreado;
use App\Listeners\NotificarSupervisoresPedidoCreado;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        PedidoCreado::class => [
            NotificarSupervisoresPedidoCreado::class,
        ],
    ];

    /**
     * Register any other events for your application.
     */
    public function boot(): void
    {
        //
    }
}
