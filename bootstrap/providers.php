<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\BladeDirectivesServiceProvider::class,
    App\Providers\DomainServiceProvider::class,
    App\Providers\CQRSServiceProvider::class,
    Intervention\Image\ImageManagerServiceProvider::class,
    App\Modules\Pedidos\Infrastructure\Providers\PedidosServiceProvider::class,
];
