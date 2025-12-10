<?php

namespace App\Application\Services;

use App\Application\DTOs\VarianteDTO;
use App\Application\DTOs\TallaDTO;
use App\Models\PrendaVariante;
use App\Models\PrendaTalla;

class PrendaVariantesService
{
    public function __construct(
        private ColorGeneroMangaBrocheService $colorGeneroService,
        private PrendaTelasService $prendaTelasService,
    ) {}

    /**
     * Crear variante para una prenda
     */
    public function crear(int $prendaId, VarianteDTO $varianteDTO): PrendaVariante
    {
        // Obtener o crear manga y broche
        $manga = $this->colorGeneroService->obtenerOCrearManga($varianteDTO->tipo_manga_id);
        $broche = $this->colorGeneroService->obtenerOCrearBroche($varianteDTO->tipo_broche_id);

        // Crear variante
        $variante = PrendaVariante::create([
            'prenda_cotizacion_id' => $prendaId,
            'tipo_manga_id' => $manga?->id,
            'tipo_broche_id' => $broche?->id,
            'tiene_bolsillos' => $varianteDTO->tiene_bolsillos,
            'tiene_reflectivo' => $varianteDTO->tiene_reflectivo,
            'descripcion_adicional' => $varianteDTO->descripcion_adicional,
        ]);

        \Log::info('✅ Variante creada', [
            'variante_id' => $variante->id,
            'prenda_id' => $prendaId,
        ]);

        return $variante;
    }

    /**
     * Actualizar variante
     */
    public function actualizar(int $varianteId, VarianteDTO $varianteDTO): PrendaVariante
    {
        $variante = PrendaVariante::findOrFail($varianteId);

        $manga = $this->colorGeneroService->obtenerOCrearManga($varianteDTO->tipo_manga_id);
        $broche = $this->colorGeneroService->obtenerOCrearBroche($varianteDTO->tipo_broche_id);

        $variante->update([
            'tipo_manga_id' => $manga?->id,
            'tipo_broche_id' => $broche?->id,
            'tiene_bolsillos' => $varianteDTO->tiene_bolsillos,
            'tiene_reflectivo' => $varianteDTO->tiene_reflectivo,
            'descripcion_adicional' => $varianteDTO->descripcion_adicional,
        ]);

        return $variante;
    }

    /**
     * Registrar tallas para una prenda
     */
    public function registrarTallas(int $prendaId, array $tallasDTO): void
    {
        // Eliminar tallas anteriores
        PrendaTalla::where('prenda_cotizacion_id', $prendaId)->delete();

        // Registrar nuevas tallas
        foreach ($tallasDTO as $tallaDTO) {
            if ($tallaDTO instanceof TallaDTO) {
                $this->crearTalla($prendaId, $tallaDTO);
            }
        }

        \Log::info('✅ Tallas registradas', [
            'prenda_id' => $prendaId,
            'cantidad' => count($tallasDTO),
        ]);
    }

    /**
     * Crear una talla individual
     */
    private function crearTalla(int $prendaId, TallaDTO $tallaDTO): PrendaTalla
    {
        return PrendaTalla::create([
            'prenda_cotizacion_id' => $prendaId,
            'talla' => strtoupper($tallaDTO->talla),
            'cantidad' => $tallaDTO->cantidad,
        ]);
    }

    /**
     * Obtener tallas de una prenda
     */
    public function obtenerTallas(int $prendaId): array
    {
        return PrendaTalla::where('prenda_cotizacion_id', $prendaId)
            ->orderByRaw("FIELD(talla, 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL')")
            ->get()
            ->map(fn($talla) => [
                'id' => $talla->id,
                'talla' => $talla->talla,
                'cantidad' => $talla->cantidad,
            ])
            ->toArray();
    }

    /**
     * Actualizar talla
     */
    public function actualizarTalla(int $tallaId, TallaDTO $tallaDTO): PrendaTalla
    {
        $talla = PrendaTalla::findOrFail($tallaId);

        $talla->update([
            'talla' => strtoupper($tallaDTO->talla),
            'cantidad' => $tallaDTO->cantidad,
        ]);

        return $talla;
    }

    /**
     * Eliminar talla
     */
    public function eliminarTalla(int $tallaId): bool
    {
        return PrendaTalla::destroy($tallaId) > 0;
    }

    /**
     * Obtener variante con todas sus relaciones
     */
    public function obtenerVariante(int $varianteId): array
    {
        $variante = PrendaVariante::with([
            'tipoManga',
            'tipoBroche',
            'telas.color',
            'telas.tela',
        ])->findOrFail($varianteId);

        return [
            'id' => $variante->id,
            'tipo_manga' => $variante->tipoManga?->nombre,
            'tipo_broche' => $variante->tipoBroche?->nombre,
            'tiene_bolsillos' => $variante->tiene_bolsillos,
            'tiene_reflectivo' => $variante->tiene_reflectivo,
            'descripcion_adicional' => $variante->descripcion_adicional,
            'telas' => $this->prendaTelasService->obtenerTelasVariante($varianteId),
        ];
    }

    /**
     * Eliminar variante
     */
    public function eliminarVariante(int $varianteId): bool
    {
        return PrendaVariante::destroy($varianteId) > 0;
    }
}
