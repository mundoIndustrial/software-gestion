<?php

namespace App\Repositories;

use App\Models\PrendaTela;
use App\Models\TelaPrenda;
use App\Models\ColorPrenda;

class PrendaTelaRepository
{
    /**
     * Obtener todas las telas de una variante con sus relaciones
     */
    public function obtenerTelasVariante(int $varianteId)
    {
        return PrendaTela::where('variante_prenda_id', $varianteId)
            ->with(['tela', 'color'])
            ->get()
            ->map(function($prendaTela) {
                return [
                    'id' => $prendaTela->id,
                    'color_id' => $prendaTela->color_id,
                    'color_nombre' => $prendaTela->color?->nombre ?? '',
                    'tela_id' => $prendaTela->tela_id,
                    'tela_nombre' => $prendaTela->tela?->nombre ?? '',
                    'tela_referencia' => $prendaTela->tela?->referencia ?? '',
                ];
            });
    }

    /**
     * Crear o actualizar tela de prenda
     */
    public function crearOActualizarTela(int $varianteId, ?int $colorId, ?int $telaId): PrendaTela
    {
        return PrendaTela::updateOrCreate(
            [
                'variante_prenda_id' => $varianteId,
                'color_id' => $colorId,
                'tela_id' => $telaId,
            ],
            [
                'variante_prenda_id' => $varianteId,
                'color_id' => $colorId,
                'tela_id' => $telaId,
            ]
        );
    }

    /**
     * Obtener nombre de tela por ID
     */
    public function obtenerNombreTela(?int $telaId): string
    {
        if (!$telaId) {
            return '';
        }

        return TelaPrenda::find($telaId)?->nombre ?? '';
    }

    /**
     * Obtener referencia de tela por ID
     */
    public function obtenerReferenciaTela(?int $telaId): string
    {
        if (!$telaId) {
            return '';
        }

        // La referencia ahora está en prenda_pedido_colores_telas
        // Este método devuelve vacío para mantener compatibilidad
        return '';
    }

    /**
     * Obtener nombre de color por ID
     */
    public function obtenerNombreColor(?int $colorId): string
    {
        if (!$colorId) {
            return '';
        }

        return ColorPrenda::find($colorId)?->nombre ?? '';
    }

    /**
     * Obtener código de color por ID
     */
    public function obtenerCodigoColor(?int $colorId): string
    {
        if (!$colorId) {
            return '';
        }

        return ColorPrenda::find($colorId)?->codigo ?? '';
    }

    /**
     * Obtener tela completa con todas sus propiedades
     */
    public function obtenerTela(?int $telaId): ?array
    {
        if (!$telaId) {
            return null;
        }

        $tela = TelaPrenda::find($telaId);
        
        if (!$tela) {
            return null;
        }

        return [
            'id' => $tela->id,
            'nombre' => $tela->nombre,
            'referencia' => $tela->referencia,
            'descripcion' => $tela->descripcion,
            'activo' => $tela->activo,
        ];
    }

    /**
     * Obtener color completo con todas sus propiedades
     */
    public function obtenerColor(?int $colorId): ?array
    {
        if (!$colorId) {
            return null;
        }

        $color = ColorPrenda::find($colorId);
        
        if (!$color) {
            return null;
        }

        return [
            'id' => $color->id,
            'nombre' => $color->nombre,
            'codigo' => $color->codigo,
            'activo' => $color->activo,
        ];
    }

    /**
     * Eliminar telas de una variante
     */
    public function eliminarTelasVariante(int $varianteId): void
    {
        PrendaTela::where('variante_prenda_id', $varianteId)->delete();
    }

    /**
     * Guardar múltiples telas para una variante
     */
    public function guardarMultiplesTelas(int $varianteId, array $telas): void
    {
        // Eliminar telas anteriores
        $this->eliminarTelasVariante($varianteId);

        // Guardar nuevas telas
        foreach ($telas as $telaData) {
            $this->crearOActualizarTela(
                $varianteId,
                $telaData['color_id'] ?? null,
                $telaData['tela_id'] ?? null
            );
        }
    }
}
