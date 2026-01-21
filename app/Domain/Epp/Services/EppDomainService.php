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
     * 
     * @return Collection<array>
     */
    public function buscarEppConImagenes(string $termino): Collection
    {
        $epps = $this->eppRepository->buscar($termino);

        return $epps->map(fn($epp) => $this->formatearEppParaApi($epp));
    }

    /**
     * Obtener todos los EPP activos con imágenes
     * 
     * @return Collection<array>
     */
    public function obtenerEppActivos(): Collection
    {
        $epps = $this->eppRepository->obtenerActivos();

        return $epps->map(fn($epp) => $this->formatearEppParaApi($epp));
    }

    /**
     * Obtener EPP por categoría
     * 
     * @return Collection<array>
     */
    public function obtenerEppPorCategoria(string $categoria): Collection
    {
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
     * Obtener EPP por ID
     */
    public function obtenerEppPorId(int $id): ?array
    {
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
     * Construye URLs de imágenes
     * 
     * @return array
     */
    private function formatearEppParaApi(EppAggregate $epp): array
    {
        $codigo = (string)$epp->codigo();
        $imagenPrincipal = $epp->imagenPrincipal();
        $urlImagenPrincipal = null;

        if ($imagenPrincipal) {
            try {
                $urlObj = $imagenPrincipal->construirUrl($codigo);
                $urlImagenPrincipal = (string)$urlObj;
            } catch (\Exception $e) {
                // Si hay error, usar ruta directa
                $urlImagenPrincipal = '/storage/epp/' . $codigo . '/' . $imagenPrincipal->archivo();
            }
        }

        $imagenes = [];
        try {
            $imagenes = array_map(
                fn($img) => [
                    'id' => $img->id(),
                    'archivo' => $img->archivo(),
                    'principal' => $img->esPrincipal(),
                    'orden' => $img->orden(),
                    'url' => '/storage/epp/' . $codigo . '/' . $img->archivo(),
                ],
                $epp->imagenes()
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(' Error formatando imágenes EPP', [
                'epp_id' => $epp->id(),
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'id' => $epp->id(),
            'codigo' => $codigo,
            'nombre' => $epp->nombre(),
            'nombre_completo' => $epp->nombre(),
            'categoria' => (string)$epp->categoria(),
            'descripcion' => $epp->descripcion(),
            'activo' => $epp->estaActivo(),
            'imagen_principal_url' => $urlImagenPrincipal,
            'imagenes' => $imagenes,
        ];
    }
}
