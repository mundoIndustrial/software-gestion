<?php

namespace App\Domain\Epp\Services;

use App\Domain\Epp\Aggregates\EppAggregate;
use App\Domain\Epp\Repositories\EppRepositoryInterface;
use App\Domain\Epp\ValueObjects\CodigoEpp;
use App\Domain\Epp\ValueObjects\CategoriaEpp;
use Illuminate\Support\Collection;

/**
 * Servicio de Dominio para EPP
 * 
 * Encapsula la lógica de negocio relacionada con EPP
 * Orquesta consultas usando el repositorio
 */
class EppDomainService
{
    public function __construct(
        private EppRepositoryInterface $eppRepository
    ) {}

    /**
     * Buscar EPP y construir DTO para respuesta
     * Nota: epp_imagenes no existe, se ignorará tabla
     * 
     * @return Collection<array>
     */
    public function buscarEpp(string $termino): Collection
    {
        \Illuminate\Support\Facades\Log::debug('📋 [EPP-SERVICE] Buscando EPP sin epp_imagenes', [
            'termino' => $termino,
        ]);
        
        $epps = $this->eppRepository->buscar($termino);

        return $epps->map(fn($epp) => $this->formatearEppParaApi($epp));
    }

    /**
     * Obtener todos los EPP activos (sin tabla epp_imagenes)
     * 
     * @return Collection<array>
     */
    public function obtenerEppActivos(): Collection
    {
        \Illuminate\Support\Facades\Log::debug('📋 [EPP-SERVICE] Obteniendo EPPs activos sin epp_imagenes');
        
        $epps = $this->eppRepository->obtenerActivos();

        return $epps->map(fn($epp) => $this->formatearEppParaApi($epp));
    }

    /**
     * Obtener EPP por categoría (sin tabla epp_imagenes)
     * 
     * @return Collection<array>
     */
    public function obtenerEppPorCategoria(string $categoria): Collection
    {
        \Illuminate\Support\Facades\Log::debug('📋 [EPP-SERVICE] Obteniendo EPPs por categoría sin epp_imagenes', [
            'categoria' => $categoria,
        ]);
        
        // Validar que sea una categoría válida
        try {
            new CategoriaEpp($categoria);
        } catch (\InvalidArgumentException $e) {
            throw new \DomainException("Categoría inválida: {$categoria}");
        }

        $epps = $this->eppRepository->obtenerPorCategoria($categoria);

        return $epps->map(fn($epp) => $this->formatearEppParaApi($epp));
    }

    /**
     * Obtener EPP por ID (sin tabla epp_imagenes)
     */
    public function obtenerEppPorId(int $id): ?array
    {
        \Illuminate\Support\Facades\Log::debug('📋 [EPP-SERVICE] Obteniendo EPP por ID sin epp_imagenes', [
            'epp_id' => $id,
        ]);
        
        $epp = $this->eppRepository->obtenerPorId($id);

        if (!$epp) {
            return null;
        }

        return $this->formatearEppParaApi($epp);
    }

    /**
     * Obtener todas las categorías disponibles
     * 
     * @return Collection<string>
     */
    public function obtenerCategorias(): Collection
    {
        return $this->eppRepository->obtenerCategorias();
    }

    /**
     * Formatear agregado EPP para respuesta de API
     * Sin imágenes (van en pedido_epp_imagenes, no en epps)
     * NOTA: categoria fue removida de la tabla epps
     * 
     * @return array
     */
    private function formatearEppParaApi(EppAggregate $epp): array
    {
        return [
            'id' => $epp->id(),
            'nombre' => $epp->nombre(),
            'nombre_completo' => $epp->nombre(),
            'marca' => $epp->marca(),
            'descripcion' => $epp->descripcion(),
            'activo' => $epp->estaActivo(),
            'imagen' => null, // Las imágenes van en pedido_epp_imagenes
        ];
    }
}
