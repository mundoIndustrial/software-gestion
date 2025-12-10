<?php

namespace App\Application\Services;

use App\Models\ColorPrenda;
use App\Models\GeneroPrenda;
use App\Models\TipoManga;
use App\Models\TipoBroche;

class ColorGeneroMangaBrocheService
{
    /**
     * Obtener o crear color
     */
    public function obtenerOCrearColor(string $nombre): ColorPrenda
    {
        if (empty($nombre)) {
            return null;
        }

        $nombreNormalizado = ucfirst(strtolower(trim($nombre)));

        return ColorPrenda::firstOrCreate(
            ['nombre' => $nombreNormalizado],
            [
                'nombre' => $nombreNormalizado,
                'codigo' => strtoupper(str_replace(' ', '_', $nombreNormalizado)),
                'activo' => true,
            ]
        );
    }

    /**
     * Obtener o crear género
     */
    public function obtenerOCrearGenero(string $nombre): GeneroPrenda
    {
        if (empty($nombre)) {
            return null;
        }

        $nombreNormalizado = ucfirst(strtolower(trim($nombre)));

        return GeneroPrenda::firstOrCreate(
            ['nombre' => $nombreNormalizado],
            [
                'nombre' => $nombreNormalizado,
                'activo' => true,
            ]
        );
    }

    /**
     * Obtener o crear manga por ID o nombre
     */
    public function obtenerOCrearManga($idONombre): TipoManga
    {
        if (empty($idONombre)) {
            return null;
        }

        // Si es numérico, buscar por ID
        if (is_numeric($idONombre)) {
            $manga = TipoManga::find($idONombre);
            if ($manga) {
                return $manga;
            }
        }

        // Buscar por nombre
        $nombreNormalizado = ucfirst(strtolower(trim($idONombre)));
        return TipoManga::firstOrCreate(
            ['nombre' => $nombreNormalizado],
            [
                'nombre' => $nombreNormalizado,
                'activo' => true,
            ]
        );
    }

    /**
     * Obtener o crear broche por ID o nombre
     */
    public function obtenerOCrearBroche($idONombre): TipoBroche
    {
        if (empty($idONombre)) {
            return null;
        }

        // Si es numérico, buscar por ID
        if (is_numeric($idONombre)) {
            $broche = TipoBroche::find($idONombre);
            if ($broche) {
                return $broche;
            }
        }

        // Buscar por nombre
        $nombreNormalizado = ucfirst(strtolower(trim($idONombre)));
        return TipoBroche::firstOrCreate(
            ['nombre' => $nombreNormalizado],
            [
                'nombre' => $nombreNormalizado,
                'activo' => true,
            ]
        );
    }

    /**
     * Obtener todos los colores
     */
    public function obtenerColores(): array
    {
        return ColorPrenda::where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }

    /**
     * Obtener todos los géneros
     */
    public function obtenerGeneros(): array
    {
        return GeneroPrenda::where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }

    /**
     * Obtener todas las mangas
     */
    public function obtenerMangas(): array
    {
        return TipoManga::where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }

    /**
     * Obtener todos los broches
     */
    public function obtenerBroches(): array
    {
        return TipoBroche::where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }

    /**
     * Crear múltiples colores
     */
    public function crearColores(array $nombres): array
    {
        return array_map(
            fn($nombre) => $this->obtenerOCrearColor($nombre),
            $nombres
        );
    }

    /**
     * Crear múltiples géneros
     */
    public function crearGeneros(array $nombres): array
    {
        return array_map(
            fn($nombre) => $this->obtenerOCrearGenero($nombre),
            $nombres
        );
    }
}
