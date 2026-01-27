<?php

namespace App\Application\Services;

use App\Application\DTOs\TelaDTO;
use App\Models\TelaPrenda;
use App\Models\PrendaTela;
use Illuminate\Http\UploadedFile;

class PrendaTelasService
{
    public function __construct(
        private ColorGeneroMangaBrocheService $colorGeneroService,
        private ImagenProcesadorService $imagenService,
    ) {}

    /**
     * Registrar telas múltiples para una variante
     */
    public function registrarTelas(int $varianteId, array $telasDTO): void
    {
        // Eliminar telas anteriores
        PrendaTela::where('variante_prenda_id', $varianteId)->delete();

        // Registrar nuevas telas
        foreach ($telasDTO as $telaDTO) {
            $this->crearTela($varianteId, $telaDTO);
        }
    }

    /**
     * Crear una tela individual
     */
    private function crearTela(int $varianteId, TelaDTO $telaDTO): PrendaTela
    {
        // Obtener o crear color
        $color = $this->colorGeneroService->obtenerOCrearColor($telaDTO->color);

        // Obtener o crear tela
        $tela = $this->obtenerOCrearTela($telaDTO);

        // Crear registro en tabla prenda_telas_cotizacion
        return PrendaTela::create([
            'variante_prenda_id' => $varianteId,
            'color_id' => $color?->id,
            'tela_id' => $tela?->id,
        ]);
    }

    /**
     * Obtener o crear tela
     * 
     * 🔄 Ahora SIEMPRE crea una NUEVA tela para esta variante
     * No reutiliza telas existentes - Cada variante tiene sus propias telas
     */
    public function obtenerOCrearTela(TelaDTO $telaDTO): ?TelaPrenda
    {
        if (empty($telaDTO->nombre)) {
            return null;
        }

        $nombreNormalizado = ucfirst(strtolower(trim($telaDTO->nombre)));

        // Crear NUEVA tela siempre
        return TelaPrenda::create([
            'nombre' => $nombreNormalizado,
            'referencia' => $telaDTO->referencia ?? '',
            'activo' => true,
        ]);
    }

    /**
     * Procesar foto de tela
     */
    public function procesarFotoTela(UploadedFile $archivo, int $prendaId): string
    {
        return $this->imagenService->procesarImagenTela($archivo, $prendaId);
    }

    /**
     * Obtener telas de una variante
     */
    public function obtenerTelasVariante(int $varianteId): array
    {
        return PrendaTela::where('variante_prenda_id', $varianteId)
            ->with(['color', 'tela'])
            ->get()
            ->map(fn($prendaTela) => [
                'id' => $prendaTela->id,
                'color' => $prendaTela->color?->nombre,
                'color_id' => $prendaTela->color_id,
                'tela' => $prendaTela->tela?->nombre,
                'tela_id' => $prendaTela->tela_id,
                'referencia' => $prendaTela->tela?->referencia,
            ])
            ->toArray();
    }

    /**
     * Actualizar tela
     */
    public function actualizarTela(int $telaId, TelaDTO $telaDTO): PrendaTela
    {
        $prendaTela = PrendaTela::findOrFail($telaId);

        $color = $this->colorGeneroService->obtenerOCrearColor($telaDTO->color);
        $tela = $this->obtenerOCrearTela($telaDTO);

        $prendaTela->update([
            'color_id' => $color?->id,
            'tela_id' => $tela?->id,
        ]);

        return $prendaTela;
    }

    /**
     * Eliminar tela
     */
    public function eliminarTela(int $telaId): bool
    {
        return PrendaTela::destroy($telaId) > 0;
    }

    /**
     * Obtener todas las telas disponibles
     */
    public function obtenerTodasLasTelas(): array
    {
        return TelaPrenda::where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }

    /**
     * Buscar telas por nombre
     */
    public function buscarTelas(string $termino): array
    {
        return TelaPrenda::where('activo', true)
            ->where('nombre', 'like', "%{$termino}%")
            ->orWhere('referencia', 'like', "%{$termino}%")
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }
}
