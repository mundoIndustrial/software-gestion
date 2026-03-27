<?php

namespace App\Infrastructure\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Services\StorageService;
use Illuminate\Http\Response;

class StorageController extends Controller
{
    /**
     * Inyección de dependencia del servicio de storage
     */
    public function __construct(private StorageService $storageService)
    {
    }

    /**
     * Servir archivo de storage con tipo parametrizado
     * 
     * Ruta: /storage/{tipo}/{path}
     * Ejemplo: /storage/cotizaciones/2024/image.png
     * 
     * Delegación a StorageService para lógica centralizada
     */
    public function serve(string $tipo, string $path): Response
    {
        return $this->storageService->serve($tipo, $path);
    }

    /**
     * Servir archivo de cotizaciones
     * 
     * Ruta: /storage/cotizaciones/{path}
     * Delegación conveniente para rutas específicas
     */
    public function serveCotizaciones(string $path): Response
    {
        return $this->storageService->serve('cotizaciones', $path);
    }

    /**
     * Servir archivo de prendas
     * 
     * Ruta: /storage/prendas/{path}
     */
    public function servePrendas(string $path): Response
    {
        return $this->storageService->serve('prendas', $path);
    }

    /**
     * Servir archivo de pedidos
     * 
     * Ruta: /storage/pedidos/{path}
     */
    public function servePedidos(string $path): Response
    {
        return $this->storageService->serve('pedidos', $path);
    }
}
