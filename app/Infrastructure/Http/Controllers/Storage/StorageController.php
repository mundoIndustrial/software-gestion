<?php

namespace App\Infrastructure\Http\Controllers\Storage;

use App\Http\Controllers\Controller;
use App\Services\StorageService;
use Illuminate\Http\Response;

final class StorageController extends Controller
{
    public function __construct(
        private readonly StorageService $storageService
    ) {
    }

    public function serve(string $tipo, string $path): Response
    {
        return $this->storageService->serve($tipo, $path);
    }

    public function serveCotizaciones(string $path): Response
    {
        return $this->storageService->serve('cotizaciones', $path);
    }

    public function servePrendas(string $path): Response
    {
        return $this->storageService->serve('prendas', $path);
    }

    public function servePedidos(string $path): Response
    {
        return $this->storageService->serve('pedidos', $path);
    }

    /**
     * Compatibilidad con ruta historica /storage-serve/{path}
     * Espera formato: {tipo}/{ruta-interna}
     */
    public function serveLegacy(string $path): Response
    {
        $segments = explode('/', ltrim($path, '/'), 2);
        $tipo = $segments[0] ?? '';
        $innerPath = $segments[1] ?? '';

        if ($innerPath === '') {
            abort(404, 'Ruta de storage invalida');
        }

        return $this->storageService->serve($tipo, $innerPath);
    }
}
