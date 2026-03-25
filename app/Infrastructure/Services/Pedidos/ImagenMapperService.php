<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Infrastructure\Mappers\Imagenes\PrendaImagenesMapper;
use App\Infrastructure\Mappers\Imagenes\TelaImagenesMapper;
use Illuminate\Support\Facades\Log;

/**
 * ImagenMapperService - DEPRECATED (Puente de migración)
 * 
 * Este servicio está siendo reemplazado por:
 * - ImagenesService (fachada)
 * - PrendaImagenesMapper (orquestador de prendas)
 * - TelaImagenesMapper (orquestador de telas)
 * - ImagenPrenda (VO)
 * - ImagenTela (VO)
 * 
 * Se mantiene por compatibilidad con código existente.
 * 
 * MIGRACIÓN:
 * ❌ OLD: $mapper->mapearImagenesPrenda($item)
 * ✅ NEW: $service->mapearImagenesPrenda($item)
 * 
 * Los nuevos mappers son más limpios y testables.
 */
class ImagenMapperService
{
    public function __construct(
        private ColorTelaService $colorTelaService,
        private PrendaImagenesMapper $prendaMapper,
        private TelaImagenesMapper $telaMapper,
    ) {}

    /**
     * Mapear imagenes de prenda desde JSON a formato esperado
     * 
     * @deprecated Usar ImagenesService::mapearImagenesPrenda()
     */
    public function mapearImagenesPrenda(array $item): array
    {
        Log::warning('[ImagenMapperService] mapearImagenesPrenda() es deprecated. Usar ImagenesService::mapearImagenesPrenda()');
        
        return $this->prendaMapper->mapear($item['imagenes'] ?? []);
    }

    /**
     * Mapear imagenes de telas desde JSON a formato esperado
     * TAMBIEN obtiene/crea IDs de colores y telas
     * 
     * @deprecated Usar ImagenesService::mapearImagenesTelas()
     */
    public function mapearImagenesTelas(array $telas): array
    {
        Log::warning('[ImagenMapperService] mapearImagenesTelas() es deprecated. Usar ImagenesService::mapearImagenesTelas()');
        
        return $this->telaMapper->mapear($telas);
    }
}



