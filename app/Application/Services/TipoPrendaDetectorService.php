<?php

namespace App\Application\Services;

use App\Enums\TipoPrendaEnum;
use App\Models\TipoPrenda;

class TipoPrendaDetectorService
{
    /**
     * Detectar tipo de prenda por nombre
     */
    public function detectar(string $nombrePrenda): TipoPrendaEnum
    {
        $nombreNormalizado = strtoupper(trim($nombrePrenda));

        // Buscar coincidencias exactas primero
        foreach (TipoPrendaEnum::cases() as $tipo) {
            if ($tipo->value === $nombreNormalizado) {
                return $tipo;
            }
        }

        // Buscar por palabras clave
        $palabrasClave = TipoPrendaEnum::palabrasClave();

        foreach ($palabrasClave as $tipo => $palabras) {
            foreach ($palabras as $palabra) {
                if (stripos($nombreNormalizado, strtoupper($palabra)) !== false) {
                    return TipoPrendaEnum::from($tipo);
                }
            }
        }

        // Por defecto retornar OTRO
        return TipoPrendaEnum::OTRO;
    }

    /**
     * Validar si un tipo es válido
     */
    public function validar(string $tipo): bool
    {
        try {
            TipoPrendaEnum::from($tipo);
            return true;
        } catch (\ValueError) {
            return false;
        }
    }

    /**
     * Obtener o crear tipo de prenda
     */
    public function obtenerOCrear(TipoPrendaEnum $tipo): TipoPrenda
    {
        return TipoPrenda::firstOrCreate(
            ['codigo' => $tipo->value],
            [
                'nombre' => $tipo->label(),
                'codigo' => $tipo->value,
                'activo' => true,
            ]
        );
    }

    /**
     * Obtener todos los tipos disponibles
     */
    public function obtenerTodos(): array
    {
        return array_map(fn($tipo) => [
            'valor' => $tipo->value,
            'etiqueta' => $tipo->label(),
        ], TipoPrendaEnum::cases());
    }

    /**
     * Obtener palabras clave para un tipo
     */
    public function obtenerPalabrasClave(TipoPrendaEnum $tipo): array
    {
        $palabrasClave = TipoPrendaEnum::palabrasClave();
        return $palabrasClave[$tipo->value] ?? [];
    }
}
