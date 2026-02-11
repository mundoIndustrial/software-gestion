<?php

namespace App\Infrastructure\Providers;

use App\Domain\PedidosLogo\Repositories\DisenoLogoPedidoRepositoryInterface;
use App\Domain\PedidosLogo\Repositories\LogoDesignStorageInterface;
use App\Domain\PedidosLogo\Repositories\ProcesoPrendaDetalleReadRepositoryInterface;
use App\Domain\PedidosLogo\Repositories\SeguimientoAreaRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\PedidosLogo\DisenoLogoPedidoRepository;
use App\Infrastructure\Persistence\Eloquent\PedidosLogo\ProcesoPrendaDetalleReadRepository;
use App\Infrastructure\Persistence\Eloquent\PedidosLogo\SeguimientoAreaRepository;
use App\Infrastructure\Storage\PedidosLogo\LogoDesignStorage;
use Illuminate\Support\ServiceProvider;

final class PedidosLogoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProcesoPrendaDetalleReadRepositoryInterface::class,
            ProcesoPrendaDetalleReadRepository::class
        );

        $this->app->bind(
            SeguimientoAreaRepositoryInterface::class,
            SeguimientoAreaRepository::class
        );

        $this->app->bind(
            DisenoLogoPedidoRepositoryInterface::class,
            DisenoLogoPedidoRepository::class
        );

        $this->app->bind(
            LogoDesignStorageInterface::class,
            LogoDesignStorage::class
        );
    }
}
