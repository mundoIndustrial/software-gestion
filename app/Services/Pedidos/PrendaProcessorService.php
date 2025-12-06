<?php

namespace App\Services\Pedidos;

use App\DTOs\PrendaCreacionDTO;

/**
 * Service para procesar datos de prendas
 * SRP: solo responsable de procesar y validar datos de prenda
 * DIP: depende de DTOs no de modelos directamente
 */
class PrendaProcessorService
{
    /**
     * Procesa una prenda para persistencia
     * Normaliza y valida datos
     */
    public function procesar(PrendaCreacionDTO $prenda): array
    {
        return [
            'nombre_producto' => $this->normalizarString($prenda->nombreProducto),
            'descripcion' => $this->normalizarString($prenda->descripcion),
            'especificaciones' => [
                'tela' => $this->normalizarString($prenda->tela),
                'tela_referencia' => $this->normalizarString($prenda->telaReferencia),
                'color' => $this->normalizarString($prenda->color),
                'genero' => $this->normalizarString($prenda->genero),
                'manga' => $this->normalizarString($prenda->manga),
                'broche' => $this->normalizarString($prenda->broche),
            ],
            'booleanos' => [
                'tiene_bolsillos' => (bool)$prenda->tieneBolsillos,
                'tiene_reflectivo' => (bool)$prenda->tieneReflectivo,
            ],
            'observaciones' => [
                'manga' => $this->normalizarString($prenda->mangaObs),
                'bolsillos' => $this->normalizarString($prenda->bolsillosObs),
                'broche' => $this->normalizarString($prenda->brocheObs),
                'reflectivo' => $this->normalizarString($prenda->reflectivoObs),
            ],
            'observaciones_generales' => $this->normalizarString($prenda->observaciones),
            'cantidades' => $this->procesarCantidades($prenda->cantidades),
        ];
    }

    /**
     * Procesa cantidades por talla
     */
    private function procesarCantidades(array $cantidades): array
    {
        return array_filter(
            $cantidades,
            fn($cantidad) => is_numeric($cantidad) && $cantidad > 0
        );
    }

    /**
     * Normaliza strings (trim, validación)
     */
    private function normalizarString(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $normalizado = trim($valor);
        return empty($normalizado) ? null : $normalizado;
    }
}
